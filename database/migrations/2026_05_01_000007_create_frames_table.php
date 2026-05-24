<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('frames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('frame_number');
            $table->enum('winner', ['A', 'B'])->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('foul_a')->default(false);
            $table->boolean('foul_b')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frames');
    }
};
