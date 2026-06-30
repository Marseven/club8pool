<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedInteger('race_to_override')->nullable();
            $table->text('race_to_override_reason')->nullable();
            $table->foreignId('race_to_overridden_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('race_to_overridden_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'race_to_overridden_by');
            $table->dropColumn([
                'race_to_override',
                'race_to_override_reason',
                'race_to_overridden_by',
                'race_to_overridden_at',
            ]);
        });
    }
};
