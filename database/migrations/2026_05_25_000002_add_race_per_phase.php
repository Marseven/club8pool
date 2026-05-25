<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedInteger('pool_race_to')->nullable()->after('race_to');
            $table->unsignedInteger('knockout_race_to')->nullable()->after('pool_race_to');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['pool_race_to', 'knockout_race_to']);
        });
    }
};
