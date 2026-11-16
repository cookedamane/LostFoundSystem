<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','officer','security','registrar','it_admin','user') NOT NULL DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->whereNotIn('role', ['admin', 'user'])->update(['role' => 'admin']);
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','user') NOT NULL DEFAULT 'user'");
        }
    }
};
