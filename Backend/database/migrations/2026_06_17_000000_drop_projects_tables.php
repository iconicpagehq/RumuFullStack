<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('deployment_logs');
        Schema::dropIfExists('deployments');
        Schema::dropIfExists('project_freelancer');
        Schema::dropIfExists('project_realtor');
        Schema::dropIfExists('project_databases');
        Schema::dropIfExists('github_connections');
        Schema::dropIfExists('projects');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Property Sites feature removed — tables are not recreated.
    }
};
