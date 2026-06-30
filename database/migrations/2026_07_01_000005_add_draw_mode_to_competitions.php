<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->enum('draw_mode', ['automatic', 'manual_pools', 'manual_bracket', 'fully_manual'])
                ->default('automatic')
                ->after('structure');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('draw_mode');
        });
    }
};
