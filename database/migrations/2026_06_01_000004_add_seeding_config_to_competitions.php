<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->enum('seed_strategy', ['random', 'manual', 'rating', 'hybrid'])
                ->default('random')
                ->after('qualifiers_per_pool');
            $table->unsignedTinyInteger('seeded_players_count')->nullable()->after('seed_strategy');
            $table->boolean('draw_randomize_unseeded')->default(true)->after('seeded_players_count');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->integer('seed_rating')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('seed_rating');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['seed_strategy', 'seeded_players_count', 'draw_randomize_unseeded']);
        });
    }
};
