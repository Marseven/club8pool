<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seed')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'paid', 'rejected'])->default('pending');
            $table->dateTime('registered_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
