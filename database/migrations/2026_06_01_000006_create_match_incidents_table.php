<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_incidents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_id');
            $table->unsignedBigInteger('match_id')->nullable();
            $table->unsignedBigInteger('reported_by_id')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->string('type', 32);
            $table->string('severity', 16);
            $table->string('status', 16)->default('open');
            $table->text('description');
            $table->text('resolution')->nullable();
            $table->unsignedBigInteger('resolved_by_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('competition_id')->references('id')->on('competitions')->cascadeOnDelete();
            $table->foreign('match_id')->references('id')->on('matches')->nullOnDelete();
            $table->foreign('reported_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_to_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('resolved_by_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['competition_id', 'status']);
            $table->index('match_id');
            $table->index('assigned_to_id');
        });

        Schema::create('match_tiebreaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('match_id')->unique();
            $table->string('mode', 16);
            $table->unsignedBigInteger('winner_player_id')->nullable();
            $table->unsignedTinyInteger('score_a')->nullable();
            $table->unsignedTinyInteger('score_b')->nullable();
            $table->unsignedBigInteger('decided_by_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('match_id')->references('id')->on('matches')->cascadeOnDelete();
            $table->foreign('winner_player_id')->references('id')->on('players')->nullOnDelete();
            $table->foreign('decided_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_tiebreaks');
        Schema::dropIfExists('match_incidents');
    }
};
