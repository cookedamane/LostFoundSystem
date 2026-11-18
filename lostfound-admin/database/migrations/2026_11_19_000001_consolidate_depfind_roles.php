<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Temporarily allow both schemas so current staff accounts can be
            // preserved while their department role is consolidated to Staff.
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','staff','officer','security','registrar','it_admin','user') NOT NULL DEFAULT 'user'");
        }

        DB::table('users')->whereIn('role', ['officer', 'security', 'registrar', 'it_admin'])->update(['role' => 'staff']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','staff','user') NOT NULL DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','staff','officer','security','registrar','it_admin','user') NOT NULL DEFAULT 'user'");
        }

        DB::table('users')->where('role', 'staff')->update(['role' => 'officer']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','officer','security','registrar','it_admin','user') NOT NULL DEFAULT 'user'");
        }
    }
};
