<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'slug']);
        });

        Schema::create('inventory_towers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['inventory_project_id', 'name']);
        });

        Schema::create('inventory_wings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_tower_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['inventory_tower_id', 'name']);
        });

        Schema::create('inventory_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_tower_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_wing_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('floor_number');
            $table->string('layout_image')->nullable();
            $table->timestamps();

            $table->unique(['inventory_tower_id', 'inventory_wing_id', 'floor_number'], 'inv_floors_tower_wing_floor_unique');
        });

        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_floor_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number');
            $table->enum('layout', ['studio', '1_bhk', '2_bhk', '3_bhk', '4_bhk', '4+_bhk'])->nullable();
            $table->decimal('carpet_area', 10, 2)->nullable();
            $table->decimal('built_up_area', 10, 2)->nullable();
            $table->string('facing')->nullable();
            $table->decimal('base_price', 15, 2)->nullable();
            $table->enum('availability_status', ['available', 'hold', 'booked', 'sold'])->default('available');
            $table->string('layout_image')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['inventory_floor_id', 'unit_number'], 'inv_units_floor_number_unique');
        });

        Schema::create('inventory_unit_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_unit_id')->constrained()->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('inventory_unit_id')->nullable()->after('campaign_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_unit_id');
        });

        Schema::dropIfExists('inventory_unit_status_logs');
        Schema::dropIfExists('inventory_units');
        Schema::dropIfExists('inventory_floors');
        Schema::dropIfExists('inventory_wings');
        Schema::dropIfExists('inventory_towers');
        Schema::dropIfExists('inventory_projects');
    }
};
