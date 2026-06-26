<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->string('discipline', 16)->default('8-ball');
            $table->integer('rating')->default(1500);
            $table->integer('games_played')->default(0);
            $table->integer('frames_won')->default(0);
            $table->integer('frames_lost')->default(0);
            $table->integer('robustness')->default(0);
            $table->boolean('provisional')->default(true);
            $table->timestamp('last_match_at')->nullable();
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();

            $table->unique(['player_id', 'discipline']);
            $table->index(['discipline', 'rating']);
        });

        Schema::create('rating_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('match_id')->unique();
            $table->unsignedBigInteger('competition_id');
            $table->string('discipline', 16)->default('8-ball');
            $table->unsignedBigInteger('player_a_id');
            $table->unsignedBigInteger('player_b_id');
            $table->integer('rating_a_before');
            $table->integer('rating_b_before');
            $table->integer('rating_a_after');
            $table->integer('rating_b_after');
            $table->decimal('expected_a', 5, 4);
            $table->decimal('expected_b', 5, 4);
            $table->unsignedTinyInteger('score_a');
            $table->unsignedTinyInteger('score_b');
            $table->decimal('margin_factor', 4, 3)->default(1.000);
            $table->unsignedTinyInteger('k_factor_a');
            $table->unsignedTinyInteger('k_factor_b');
            $table->timestamp('created_at')->nullable();

            $table->foreign('match_id')->references('id')->on('matches')->cascadeOnDelete();
            $table->foreign('competition_id')->references('id')->on('competitions')->cascadeOnDelete();
            $table->foreign('player_a_id')->references('id')->on('players')->cascadeOnDelete();
            $table->foreign('player_b_id')->references('id')->on('players')->cascadeOnDelete();

            $table->index(['competition_id', 'discipline']);
            $table->index('player_a_id');
            $table->index('player_b_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rating_events');
        Schema::dropIfExists('player_ratings');
    }
};
