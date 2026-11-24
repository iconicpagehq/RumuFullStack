<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('enterprise_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('enterprise_id')->constrained()->nullOnDelete();
            $table->decimal('budget', 15, 2)->nullable()->after('status');
            $table->date('start_date')->nullable()->after('budget');
            $table->date('end_date')->nullable()->after('start_date');

            $table->index(['enterprise_id', 'status']);
        });

        Schema::table('inventory_projects', function (Blueprint $table) {
            $table->foreignId('enterprise_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->index(['enterprise_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enterprise_id');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropConstrainedForeignId('enterprise_id');
            $table->dropColumn(['budget', 'start_date', 'end_date']);
        });
    }
};
