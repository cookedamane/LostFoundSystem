<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'action', 'details', 'ip_address'];

    public static function record(string $action, ?string $details = null): void
    {
        static::create(['user_id' => Auth::id(), 'action' => $action, 'details' => $details, 'ip_address' => request()->ip()]);
    }
}
