<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'freelancer', 'realtor') NOT NULL DEFAULT 'realtor'");
        }

        DB::table('users')->where('role', 'freelancer')->update(['role' => 'realtor']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'realtor') NOT NULL DEFAULT 'realtor'");
        }

        DB::table('notifications')
            ->where('type', 'freelancer_registered')
            ->update(['type' => 'realtor_registered']);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'freelancer', 'realtor') NOT NULL DEFAULT 'freelancer'");
        }

        DB::table('users')->where('role', 'realtor')->update(['role' => 'freelancer']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'freelancer') NOT NULL DEFAULT 'freelancer'");
        }

        DB::table('notifications')
            ->where('type', 'realtor_registered')
            ->update(['type' => 'freelancer_registered']);
    }
};
