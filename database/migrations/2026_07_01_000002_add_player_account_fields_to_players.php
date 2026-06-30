<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->string('login_name', 100)->nullable()->after('losses');
            $table->string('login_slug', 100)->nullable()->unique()->after('login_name');
            $table->string('password')->nullable()->after('login_slug');
            $table->rememberToken()->after('password');
            $table->boolean('must_change_password')->default(true)->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('must_change_password');
            $table->string('profile_photo_path')->nullable()->after('last_login_at');
            $table->boolean('is_player_account_enabled')->default(true)->after('profile_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn([
                'login_name',
                'login_slug',
                'password',
                'remember_token',
                'must_change_password',
                'last_login_at',
                'profile_photo_path',
                'is_player_account_enabled',
            ]);
        });
    }
};
