<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('discipline', ['8-ball', '10-ball', 'snooker', 'blackball'])->default('8-ball');
            $table->enum('format', ['single_elim', 'double_elim', 'pools', 'round_robin', 'simple', 'teams'])->default('single_elim');
            $table->unsignedInteger('player_slots')->default(16);
            $table->unsignedInteger('race_to')->default(7);
            $table->unsignedInteger('shot_clock')->default(30);
            $table->boolean('alternate_break')->default(true);
            $table->boolean('push_out')->default(false);
            $table->unsignedInteger('frame_pause')->default(60);
            $table->unsignedInteger('tiebreak_race')->default(9);
            $table->string('venue')->default('Le Cadre, Libreville');
            $table->string('city')->default('Libreville');
            $table->unsignedBigInteger('entry_fee')->default(25000);
            $table->unsignedBigInteger('deposit')->default(10000);
            $table->unsignedBigInteger('prize_pool')->default(1400000);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->dateTime('registration_closes_at')->nullable();
            $table->enum('status', ['draft', 'registration', 'in_progress', 'finished'])->default('draft');
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
