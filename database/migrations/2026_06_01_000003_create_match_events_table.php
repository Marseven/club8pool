<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id');
            $table->unsignedBigInteger('match_id');
            $table->unsignedBigInteger('frame_id')->nullable();
            $table->unsignedTinyInteger('frame_number')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('player_id')->nullable();
            $table->string('event_type', 32);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->foreign('competition_id')->references('id')->on('competitions')->cascadeOnDelete();
            $table->foreign('match_id')->references('id')->on('matches')->cascadeOnDelete();
            $table->foreign('frame_id')->references('id')->on('frames')->nullOnDelete();
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('player_id')->references('id')->on('players')->nullOnDelete();

            $table->index(['competition_id', 'event_type']);
            $table->index(['match_id', 'event_type']);
            $table->index(['player_id', 'event_type']);
            $table->index(['match_id', 'frame_number']);
        });

        Schema::create('player_competition_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedInteger('matches_played')->default(0);
            $table->unsignedInteger('matches_won')->default(0);
            $table->unsignedInteger('matches_lost')->default(0);
            $table->unsignedInteger('frames_won')->default(0);
            $table->unsignedInteger('frames_lost')->default(0);
            $table->unsignedInteger('breaks_taken')->default(0);
            $table->unsignedInteger('break_wins')->default(0);
            $table->unsignedInteger('break_and_runs')->default(0);
            $table->unsignedInteger('safeties')->default(0);
            $table->unsignedInteger('fouls')->default(0);
            $table->unsignedInteger('misses')->default(0);
            $table->unsignedInteger('warnings')->default(0);
            $table->unsignedInteger('shot_clock_violations')->default(0);
            $table->unsignedInteger('avg_match_duration_seconds')->nullable();
            $table->boolean('is_stale')->default(false);
            $table->timestamps();

            $table->foreign('competition_id')->references('id')->on('competitions')->cascadeOnDelete();
            $table->foreign('player_id')->references('id')->on('players')->cascadeOnDelete();

            $table->unique(['competition_id', 'player_id']);
            $table->index('competition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_competition_statistics');
        Schema::dropIfExists('match_events');
    }
};
