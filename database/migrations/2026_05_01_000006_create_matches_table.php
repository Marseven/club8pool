<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->enum('round', ['R32', 'R16', 'QF', 'SF', 'F', 'GF', 'EXH'])->default('R16');
            $table->unsignedInteger('round_position')->default(0);
            $table->foreignId('player_a_id')->nullable()->constrained('players')->nullOnDelete();
            $table->foreignId('player_b_id')->nullable()->constrained('players')->nullOnDelete();
            $table->unsignedInteger('score_a')->default(0);
            $table->unsignedInteger('score_b')->default(0);
            $table->foreignId('pool_table_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('referee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['scheduled', 'live', 'done', 'disputed', 'pending'])->default('scheduled');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('referee_note')->nullable();
            $table->json('frames')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
