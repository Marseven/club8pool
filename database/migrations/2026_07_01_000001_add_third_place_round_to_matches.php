<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: extend the round ENUM to include '3P' (third-place / petite finale).
        // SQLite (used in tests) treats ENUM as string and ignores this statement.
        try {
            DB::statement(
                "ALTER TABLE `matches` MODIFY `round`
                 ENUM('R32','R16','QF','SF','3P','F','GF','EXH')
                 NOT NULL DEFAULT 'R16'"
            );
        } catch (\Throwable) {
            // SQLite: ENUM is stored as VARCHAR — no modification needed.
        }
    }

    public function down(): void
    {
        try {
            DB::statement(
                "ALTER TABLE `matches` MODIFY `round`
                 ENUM('R32','R16','QF','SF','F','GF','EXH')
                 NOT NULL DEFAULT 'R16'"
            );
        } catch (\Throwable) {
            // SQLite: no-op
        }
    }
};
