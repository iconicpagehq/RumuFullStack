<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive migration: devices that can place calls from the Rumu app.
     *
     * One row per user + device. Used to show "last synced from" in the app,
     * remember the recorder source (MIUI, Samsung, SAF, ...), and let support
     * verify why a recording did or did not arrive.
     */
    public function up(): void
    {
        if (Schema::hasTable('mobile_devices')) {
            return;
        }

        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_uuid', 64);
            $table->string('platform', 16)->default('android');
            $table->string('model', 128)->nullable();
            $table->string('os_version', 32)->nullable();
            $table->string('app_version', 32)->nullable();
            $table->string('recorder_source', 64)->nullable();  // miui|samsung|saf|custom
            $table->boolean('recording_auto_sync')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_devices');
    }
};
