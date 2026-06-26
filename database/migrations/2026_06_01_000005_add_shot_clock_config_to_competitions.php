<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->boolean('shot_clock_enabled')->default(true);
            $table->unsignedTinyInteger('shot_clock_late_seconds')->default(15);
            $table->enum('shot_clock_late_rule', ['never', 'hill', 'last_two_racks', 'custom'])->default('never');
            $table->unsignedTinyInteger('shot_clock_extensions_per_player')->default(1);
            $table->enum('tie_break_mode', ['none', 'shootout', 'race_to_one'])->default('none');
            $table->enum('rack_mode', ['triangle', 'template', 'tapping'])->default('triangle');
            $table->boolean('push_out_enabled')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn([
                'shot_clock_enabled',
                'shot_clock_late_seconds',
                'shot_clock_late_rule',
                'shot_clock_extensions_per_player',
                'tie_break_mode',
                'rack_mode',
                'push_out_enabled',
            ]);
        });
    }
};
