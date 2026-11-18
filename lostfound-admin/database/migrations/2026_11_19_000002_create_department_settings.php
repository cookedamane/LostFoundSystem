<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_settings', function (Blueprint $table) {
            $table->string('setting_key', 60)->primary();
            $table->text('setting_value');
        });

        DB::table('department_settings')->insert([
            ['setting_key' => 'department_name', 'setting_value' => config('depfind.department', 'Department')],
            ['setting_key' => 'department_locations', 'setting_value' => json_encode(config('depfind.locations', ['Department Building']))],
            ['setting_key' => 'pickup_location', 'setting_value' => config('depfind.pickup_location', 'Department Lost and Found Desk')],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('department_settings');
    }
};
