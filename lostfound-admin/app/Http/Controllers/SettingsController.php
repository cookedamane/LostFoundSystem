<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DepartmentSetting;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'department' => DepartmentSetting::value('department_name', config('depfind.department', 'Department')),
            'locations' => implode("\n", DepartmentSetting::locations()),
            'pickup' => DepartmentSetting::value('pickup_location', config('depfind.pickup_location', 'Department Lost and Found Desk')),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'department_name' => 'required|string|min:2|max:120',
            'locations' => 'required|string|max:3000',
            'pickup_location' => 'required|string|min:2|max:150',
        ]);
        $locations = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['locations'])))));
        if (!$locations || count($locations) > 30 || collect($locations)->contains(fn ($location) => mb_strlen($location) < 2 || mb_strlen($location) > 150)) {
            return back()->withInput()->withErrors(['locations' => 'Enter 1–30 department locations, each 2–150 characters long.']);
        }
        $removed = array_diff(DepartmentSetting::locations(), $locations);
        $activeAtRemovedLocations = Item::whereIn('location', $removed)->whereIn('status', ['pending', 'listed', 'claimed'])->distinct()->pluck('location');
        if ($activeAtRemovedLocations->isNotEmpty()) {
            return back()->withInput()->withErrors(['locations' => 'Resolve active reports and claims at these locations before removing them: ' . $activeAtRemovedLocations->join(', ') . '.']);
        }

        DB::transaction(function () use ($data, $locations) {
            foreach ([
                'department_name' => $data['department_name'],
                'department_locations' => json_encode($locations, JSON_UNESCAPED_UNICODE),
                'pickup_location' => $data['pickup_location'],
            ] as $key => $value) {
                DepartmentSetting::updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
            }
            ActivityLog::record('department_settings_updated', 'Updated department identity, allowed locations, or pickup point');
        });

        return back()->with('ok', 'Department settings saved. New reports and found-item entries now use these locations.');
    }
}
