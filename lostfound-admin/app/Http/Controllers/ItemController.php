<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\DepartmentSetting;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:150',
            'category_id' => 'required|exists:categories,id',
            'location' => ['required', 'string', Rule::in(DepartmentSetting::locations())],
            'date_event' => 'required|date|before_or_equal:today',
            'description' => 'required|string|min:5|max:2000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    private function storePhoto($file): string
    {
        $dir = config('lostfound.upload_path');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = Str::random(24) . '.' . $file->extension();
        $file->move($dir, $name);
        return $name;
    }

    private function deletePhoto(?string $name): void
    {
        if ($name) {
            @unlink(rtrim(config('lostfound.upload_path'), '/\\') . DIRECTORY_SEPARATOR . basename($name));
        }
    }

    public function index(Request $r)
    {
        $q = Item::with('category')->whereIn('location', DepartmentSetting::locations())->latest();
        if ($r->filled('q')) {
            $term = '%' . $r->input('q') . '%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('location', 'like', $term));
        }
        foreach (['type', 'status', 'category_id'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->input($f));
            }
        }
        return view('items.index', [
            'items' => $q->paginate(10)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('items.form', ['item' => new Item(['date_event' => now()]), 'categories' => Category::orderBy('name')->get(), 'locations' => DepartmentSetting::locations()]);
    }

    public function store(Request $r)
    {
        $data = $r->validate($this->rules());
        unset($data['photo']);
        $data += ['type' => 'found', 'status' => 'listed', 'reported_by' => $r->user()->id];
        if ($r->hasFile('photo')) {
            $data['photo_path'] = $this->storePhoto($r->file('photo'));
        }
        $item = Item::create($data);
        ActivityLog::record('item_created', "Found item #{$item->id}: {$item->name}");
        return redirect()->route('items.show', $item)->with('ok', 'Found item registered and now visible in the app.');
    }

    public function show(Item $item)
    {
        abort_unless(in_array($item->location, DepartmentSetting::locations(), true), 404);
        return view('items.show', ['item' => $item->load(['category', 'reporter', 'claims.user'])]);
    }

    public function edit(Item $item)
    {
        abort_unless(in_array($item->location, DepartmentSetting::locations(), true), 404);
        return view('items.form', ['item' => $item, 'categories' => Category::orderBy('name')->get(), 'locations' => array_values(array_unique([...DepartmentSetting::locations(), $item->location]))]);
    }

    public function update(Request $r, Item $item)
    {
        abort_unless(in_array($item->location, DepartmentSetting::locations(), true), 404);
        $rules = $this->rules();
        // Keep a historic location editable, but do not allow it on new items.
        $rules['location'] = ['required', 'string', Rule::in(array_values(array_unique([...DepartmentSetting::locations(), $item->location])))];
        $data = $r->validate($rules + [
            'status' => 'required|in:' . implode(',', Item::STATUSES),
            'admin_note' => 'nullable|string|max:1000',
        ]);
        unset($data['photo']);
        if ($r->hasFile('photo')) {
            $this->deletePhoto($item->photo_path);
            $data['photo_path'] = $this->storePhoto($r->file('photo'));
        }
        $item->update($data);
        ActivityLog::record('item_updated', "Item #{$item->id}");
        return redirect()->route('items.show', $item)->with('ok', 'Item updated.');
    }

    public function setStatus(Request $r, Item $item)
    {
        abort_unless(in_array($item->location, DepartmentSetting::locations(), true), 404);
        $data = $r->validate(['status' => 'required|in:' . implode(',', Item::STATUSES), 'admin_note' => 'nullable|string|max:1000']);
        if ($data['status'] === 'rejected' && blank($data['admin_note'] ?? null)) {
            return back()->withErrors(['admin_note' => 'Tell the user why the report was rejected.']);
        }
        $item->update($data);
        ActivityLog::record('item_status', "Item #{$item->id} set to {$data['status']}");
        return back()->with('ok', 'Status updated. The user will see it in the app.');
    }

    public function destroy(Item $item)
    {
        abort_unless(in_array($item->location, DepartmentSetting::locations(), true), 404);
        $this->deletePhoto($item->photo_path);
        ActivityLog::record('item_deleted', "Item #{$item->id}: {$item->name}");
        $item->delete();
        return redirect()->route('items.index')->with('ok', 'Item deleted.');
    }

    // CSV columns: name, category, location, date, description
    public function import(Request $r)
    {
        $r->validate(['csv' => 'required|file|mimes:csv,txt|max:2048']);
        $h = fopen($r->file('csv')->getRealPath(), 'r');
        $head = fgetcsv($h) ?: [];
        $head = array_map(fn ($x) => strtolower(trim(ltrim((string) $x, "\xEF\xBB\xBF"))), $head);
        $missing = array_diff(['name', 'category', 'location', 'date', 'description'], $head);
        if ($missing) {
            return back()->withErrors(['csv' => 'Missing column(s): ' . implode(', ', $missing)]);
        }
        $allowedLocations = DepartmentSetting::locations();
        $ok = $skipped = 0;
        while (($row = fgetcsv($h)) !== false) {
            if (count($row) !== count($head)) { $skipped++; continue; }
            $d = array_combine($head, array_map('trim', $row));
            try {
                $date = \Carbon\Carbon::parse($d['date'])->toDateString();
            } catch (\Throwable) {
                $skipped++; continue;
            }
            if (strlen($d['name']) < 2 || ! in_array($d['location'], $allowedLocations, true) || strlen($d['description']) < 5 || $date > now()->toDateString()) {
                $skipped++; continue;
            }
            $cat = Category::firstOrCreate(['name' => Str::limit($d['category'] ?: 'Others', 80, '')]);
            Item::create(['type' => 'found', 'name' => Str::limit($d['name'], 150, ''), 'description' => Str::limit($d['description'], 2000, ''),
                'category_id' => $cat->id, 'location' => Str::limit($d['location'], 150, ''), 'date_event' => $date,
                'status' => 'listed', 'reported_by' => $r->user()->id]);
            $ok++;
        }
        fclose($h);
        ActivityLog::record('items_imported', "$ok imported, $skipped skipped");
        return back()->with('ok', "Imported $ok item(s). Skipped $skipped invalid row(s).");
    }
}
