<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentSetting extends Model
{
    protected $table = 'department_settings';
    protected $primaryKey = 'setting_key';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $fillable = ['setting_key', 'setting_value'];

    public static function value(string $key, ?string $fallback = null): ?string
    {
        try {
            return static::query()->whereKey($key)->value('setting_value') ?? $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public static function locations(): array
    {
        $decoded = json_decode(static::value('department_locations', json_encode(config('depfind.locations', ['Department Building']))), true);
        return is_array($decoded)
            ? array_values(array_unique(array_filter(array_map(static fn ($v) => is_string($v) ? trim($v) : '', $decoded))))
            : config('depfind.locations', ['Department Building']);
    }
}
