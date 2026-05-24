<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('club_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fgb_card')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('cue')->nullable();
            $table->string('address')->nullable();
            $table->string('flag', 4)->default('🇬🇦');
            $table->unsignedInteger('rating')->default(1500);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
