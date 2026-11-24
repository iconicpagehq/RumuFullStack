<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive migration: calls now start in the Android app on the device SIM.
     *
     * The app creates the call metadata first and uploads the handset recording
     * afterwards, so we need a client id for retries, the device that made the
     * call, the consent timestamp, and a record of how the audio was matched.
     *
     * Existing telephony columns (`provider`, `provider_call_id`) and tables
     * (`call_numbers`, `user_call_profiles`, `call_webhook_events`) are kept
     * untouched for legacy data. Nothing is dropped or renamed.
     */
    private const COLUMNS = [
        'client_call_id',
        'device_uuid',
        'consent_confirmed_at',
        'uploaded_at',
        'matched_via',
        'client_metadata',
    ];

    public function up(): void
    {
        $columns = [
            'client_call_id' => fn (Blueprint $table) => $table->string('client_call_id', 64)->nullable(),
            'device_uuid' => fn (Blueprint $table) => $table->string('device_uuid', 64)->nullable(),
            'consent_confirmed_at' => fn (Blueprint $table) => $table->timestamp('consent_confirmed_at')->nullable(),
            'uploaded_at' => fn (Blueprint $table) => $table->timestamp('uploaded_at')->nullable(),
            'matched_via' => fn (Blueprint $table) => $table->string('matched_via', 16)->nullable(),
            'client_metadata' => fn (Blueprint $table) => $table->json('client_metadata')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('call_logs', $column)) {
                continue;
            }

            Schema::table('call_logs', function (Blueprint $table) use ($definition) {
                $definition($table);
            });
        }

        $indexes = [
            ['call_logs_client_call_id_unique', ['client_call_id'], true],
            ['call_logs_device_uuid_index', ['device_uuid'], false],
            // The base table only has (lead_id, created_at); call history is
            // ordered by started_at, so this composite index is not covered.
            ['call_logs_lead_id_started_at_index', ['lead_id', 'started_at'], false],
        ];

        foreach ($indexes as [$name, $columnsToIndex, $unique]) {
            if (Schema::hasIndex('call_logs', $name)) {
                continue;
            }

            Schema::table('call_logs', function (Blueprint $table) use ($name, $columnsToIndex, $unique) {
                if ($unique) {
                    $table->unique($columnsToIndex, $name);
                } else {
                    $table->index($columnsToIndex, $name);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('call_logs', 'call_logs_client_call_id_unique')) {
            Schema::table('call_logs', function (Blueprint $table) {
                $table->dropUnique('call_logs_client_call_id_unique');
            });
        }

        foreach (['call_logs_device_uuid_index', 'call_logs_lead_id_started_at_index'] as $index) {
            if (Schema::hasIndex('call_logs', $index)) {
                Schema::table('call_logs', function (Blueprint $table) use ($index) {
                    $table->dropIndex($index);
                });
            }
        }

        foreach (self::COLUMNS as $column) {
            if (Schema::hasColumn('call_logs', $column)) {
                Schema::table('call_logs', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
