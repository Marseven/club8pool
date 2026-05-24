<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('name', 4);
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('size')->default(7);
            $table->timestamps();

            $table->unique(['competition_id', 'name']);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('pool_id')->nullable()->after('competition_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('pool_slot')->nullable()->after('pool_id');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->enum('structure', ['knockout', 'pools_knockout', 'pools_only', 'round_robin'])
                  ->default('knockout')->after('format');
            $table->unsignedInteger('pool_count')->default(0)->after('player_slots');
            $table->unsignedInteger('pool_size')->default(7)->after('pool_count');
            $table->unsignedInteger('qualifiers_per_pool')->default(2)->after('pool_size');
            $table->boolean('allow_draw')->default(false)->after('alternate_break');
            $table->boolean('enable_warnings')->default(false)->after('allow_draw');
        });

        Schema::table('matches', function (Blueprint $table) {
            $table->enum('phase', ['pool', 'knockout'])->default('knockout')->after('round');
            $table->foreignId('pool_id')->nullable()->after('phase')->constrained()->nullOnDelete();
            $table->boolean('warning_a')->default(false)->after('score_b');
            $table->boolean('warning_b')->default(false)->after('warning_a');
            $table->boolean('is_draw')->default(false)->after('warning_b');
            $table->text('note')->nullable()->after('referee_note');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['pool_id']);
            $table->dropColumn(['phase', 'pool_id', 'warning_a', 'warning_b', 'is_draw', 'note']);
        });
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['structure', 'pool_count', 'pool_size', 'qualifiers_per_pool', 'allow_draw', 'enable_warnings']);
        });
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['pool_id']);
            $table->dropColumn(['pool_id', 'pool_slot']);
        });
        Schema::dropIfExists('pools');
    }
};
