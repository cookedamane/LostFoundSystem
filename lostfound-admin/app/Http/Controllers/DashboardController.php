<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\DepartmentSetting;
use App\Models\Item;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $locations = DepartmentSetting::locations();
        $items = fn () => Item::whereIn('location', $locations);
        $byStatus = $items()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('dashboard', [
            'stats' => [
                'Found items on display' => $items()->where('type', 'found')->where('status', 'listed')->count(),
                'Lost reports to review' => $items()->where('type', 'lost')->where('status', 'pending')->count(),
                'Claims to review' => Claim::where('status', 'pending')->whereHas('item', fn ($q) => $q->whereIn('location', $locations))->count(),
                'Items returned' => $items()->where('status', 'returned')->count(),
                // Account deletion anonymizes retained rows for audit history;
                // do not include those deleted accounts in the dashboard total.
                'Registered users' => User::where('role', 'user')
                    ->where('email', 'not like', 'deleted-%@example.invalid')
                    ->count(),
            ],
            'byStatus' => $byStatus,
            'maxStatus' => max(1, $byStatus->max() ?? 1),
            'pendingClaims' => Claim::with(['item', 'user'])->where('status', 'pending')->whereHas('item', fn ($q) => $q->whereIn('location', $locations))->latest()->take(5)->get(),
            'pendingLost' => Item::with('reporter')->whereIn('location', $locations)->where('type', 'lost')->where('status', 'pending')->latest()->take(5)->get(),
        ]);
    }
}
