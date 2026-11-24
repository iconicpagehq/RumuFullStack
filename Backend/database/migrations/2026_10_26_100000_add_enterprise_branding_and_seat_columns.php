<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $table->string('logo_path')->nullable();
            $table->string('rera_number')->nullable();
            $table->json('working_hours')->nullable();
            $table->unsignedInteger('extra_managers')->default(0);
            $table->unsignedInteger('extra_employees')->default(0);
        });

        Schema::table('enterprise_memberships', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'rera_number',
                'working_hours',
                'extra_managers',
                'extra_employees',
            ]);
        });

        Schema::table('enterprise_memberships', function (Blueprint $table) {
            $table->dropColumn('removed_at');
        });
    }
};
