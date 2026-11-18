<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Claim;
use App\Models\DepartmentSetting;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function range(Request $r): array
    {
        $r->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        return [$r->input('from'), $r->input('to')];
    }

    private function items(Request $r)
    {
        [$from, $to] = $this->range($r);
        return Item::with('category')->whereIn('location', DepartmentSetting::locations())->when($from, fn ($q) => $q->whereDate('date_event', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date_event', '<=', $to))->orderBy('date_event', 'desc');
    }

    public function index(Request $r)
    {
        $items = $this->items($r)->get();
        [$from, $to] = $this->range($r);
        $locations = DepartmentSetting::locations();
        $claims = Claim::whereHas('item', fn ($q) => $q->whereIn('location', $locations))->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        return view('reports.index', [
            'items' => $items,
            'byType' => $items->groupBy('type')->map->count(),
            'byStatus' => $items->groupBy('status')->map->count(),
            'byCategory' => $items->groupBy(fn ($i) => $i->category->name)->map->count()->sortDesc(),
            'claimStats' => (clone $claims)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'from' => $from, 'to' => $to,
        ]);
    }

    public function export(Request $r, string $what)
    {
        abort_unless(in_array($what, ['items', 'claims']), 404);
        ActivityLog::record('export_' . $what);

        return response()->streamDownload(function () use ($r, $what) {
            $out = fopen('php://output', 'w');
            if ($what === 'items') {
                fputcsv($out, ['ID', 'Type', 'Name', 'Category', 'Location', 'Date', 'Status', 'Description']);
                foreach ($this->items($r)->get() as $i) {
                    fputcsv($out, [$i->id, $i->type, $i->name, $i->category->name, $i->location, $i->date_event->toDateString(), $i->status, $i->description]);
                }
            } else {
                fputcsv($out, ['ID', 'Item', 'Claimant', 'Student ID', 'Status', 'Submitted', 'Reviewed', 'Remarks']);
                foreach (Claim::with(['item', 'user'])->whereHas('item', fn ($q) => $q->whereIn('location', DepartmentSetting::locations()))->latest()->get() as $c) {
                    fputcsv($out, [$c->id, $c->item->name, $c->user->name, $c->user->student_id, $c->status, $c->created_at, $c->reviewed_at, $c->admin_remarks]);
                }
            }
            fclose($out);
        }, "lost_found_{$what}_" . now()->format('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function backup()
    {
        ActivityLog::record('backup');
        $name = 'lost_found_backup_' . now()->format('Ymd_His') . '.sql';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            $pdo = DB::getPdo();
            fwrite($out, "-- DepFind Department Lost and Found backup\nSET FOREIGN_KEY_CHECKS=0;\n");
            foreach (['users', 'categories', 'items', 'claims', 'activity_logs', 'department_settings'] as $t) {
                fwrite($out, "DELETE FROM `$t`;\n");
                DB::table($t)->orderBy($t === 'department_settings' ? 'setting_key' : 'id')->chunk(200, function ($rows) use ($out, $t, $pdo) {
                    foreach ($rows as $row) {
                        $row = (array) $row;
                        $cols = implode(',', array_map(fn ($c) => "`$c`", array_keys($row)));
                        $vals = implode(',', array_map(fn ($v) => is_null($v) ? 'NULL' : $pdo->quote((string) $v), $row));
                        fwrite($out, "INSERT INTO `$t` ($cols) VALUES ($vals);\n");
                    }
                });
            }
            fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($out);
        }, $name, ['Content-Type' => 'application/sql']);
    }

    public function activity()
    {
        $events = DB::table('activity_logs')
            ->leftJoin('users', 'users.id', '=', 'activity_logs.user_id')
            ->select('activity_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->latest('activity_logs.created_at')
            ->paginate(25);

        return view('activity.index', compact('events'));
    }
}
