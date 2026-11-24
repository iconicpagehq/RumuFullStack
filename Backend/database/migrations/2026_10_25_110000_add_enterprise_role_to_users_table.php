<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'realtor', 'enterprise') NOT NULL DEFAULT 'realtor'");
    }

    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Reverting the enum would truncate enterprise accounts, so only narrow it when none exist.
        if (DB::table('users')->where('role', 'enterprise')->exists()) {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'realtor') NOT NULL DEFAULT 'realtor'");
    }
};
