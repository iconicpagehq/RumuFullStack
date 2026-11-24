<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('header_color', 7)->default('#6b7280')->after('sort_order');
        });

        $defaults = [
            'basic' => '#475569',
            'pro' => '#9333ea',
            'mansoon' => '#9ca3af',
        ];

        foreach (DB::table('plans')->get() as $plan) {
            $color = $defaults[$plan->key] ?? '#6b7280';
            DB::table('plans')->where('id', $plan->id)->update(['header_color' => $color]);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('header_color');
        });
    }
};
