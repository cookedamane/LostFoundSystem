<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Item;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $byStatus = Item::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('dashboard', [
            'stats' => [
                'Found items on display' => Item::where('type', 'found')->where('status', 'listed')->count(),
                'Lost reports to review' => Item::where('type', 'lost')->where('status', 'pending')->count(),
                'Claims to review' => Claim::where('status', 'pending')->count(),
                'Items returned' => Item::where('status', 'returned')->count(),
                'Registered users' => User::where('role', 'user')->count(),
            ],
            'byStatus' => $byStatus,
            'maxStatus' => max(1, $byStatus->max() ?? 1),
            'pendingClaims' => Claim::with(['item', 'user'])->where('status', 'pending')->latest()->take(5)->get(),
            'pendingLost' => Item::with('reporter')->where('type', 'lost')->where('status', 'pending')->latest()->take(5)->get(),
        ]);
    }
}
