<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive migration: store each membership's reporting line.
     *
     * SQLite accepts added columns but cannot add foreign keys to an existing
     * table, so the constraint is only created on drivers that support it.
     * The application still validates every reporting line server-side.
     */
    public function up(): void
    {
        Schema::table('enterprise_memberships', function (Blueprint $table) {
            $table->unsignedBigInteger('reports_to_membership_id')->nullable();
            $table->index(['enterprise_id', 'reports_to_membership_id'], 'ent_memberships_enterprise_reports_idx');
            $table->index('reports_to_membership_id', 'ent_memberships_reports_idx');
        });

        Schema::table('enterprise_invitations', function (Blueprint $table) {
            $table->unsignedBigInteger('reports_to_membership_id')->nullable();
            $table->index('reports_to_membership_id', 'ent_invitations_reports_idx');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('enterprise_memberships', function (Blueprint $table) {
                $table->foreign('reports_to_membership_id', 'ent_memberships_reports_fk')
                    ->references('id')
                    ->on('enterprise_memberships')
                    ->nullOnDelete();
            });

            Schema::table('enterprise_invitations', function (Blueprint $table) {
                $table->foreign('reports_to_membership_id', 'ent_invitations_reports_fk')
                    ->references('id')
                    ->on('enterprise_memberships')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('enterprise_invitations', function (Blueprint $table) {
                $table->dropForeign('ent_invitations_reports_fk');
            });

            Schema::table('enterprise_memberships', function (Blueprint $table) {
                $table->dropForeign('ent_memberships_reports_fk');
            });
        }

        Schema::table('enterprise_invitations', function (Blueprint $table) {
            $table->dropIndex('ent_invitations_reports_idx');
            $table->dropColumn('reports_to_membership_id');
        });

        Schema::table('enterprise_memberships', function (Blueprint $table) {
            $table->dropIndex('ent_memberships_reports_idx');
            $table->dropIndex('ent_memberships_enterprise_reports_idx');
            $table->dropColumn('reports_to_membership_id');
        });
    }
};
