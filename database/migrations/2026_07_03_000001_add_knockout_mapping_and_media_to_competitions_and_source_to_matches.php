<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('knockout_mapping_strategy', 50)->nullable();
            $table->string('cover_image_path', 512)->nullable();
            $table->string('poster_image_path', 512)->nullable();
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->string('player_a_source', 10)->nullable();
            $table->string('player_b_source', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['knockout_mapping_strategy', 'cover_image_path', 'poster_image_path']);
        });
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['player_a_source', 'player_b_source']);
        });
    }
};
