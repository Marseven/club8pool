<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // matches indexes
        Schema::table('matches', function (Blueprint $table) {
            try {
                $table->index(['competition_id', 'status'], 'matches_comp_status');
            } catch (\Throwable $e) {
                // index already exists
            }

            try {
                $table->index(['competition_id', 'phase', 'round'], 'matches_comp_phase_round');
            } catch (\Throwable $e) {
                // index already exists
            }

            try {
                $table->index(['competition_id', 'phase', 'status'], 'matches_comp_phase_status');
            } catch (\Throwable $e) {
                // index already exists
            }

            try {
                $table->index(['pool_id', 'status'], 'matches_pool_status');
            } catch (\Throwable $e) {
                // index already exists
            }

            try {
                $table->index(['referee_id', 'status'], 'matches_referee_status');
            } catch (\Throwable $e) {
                // index already exists
            }
        });

        // registrations indexes
        Schema::table('registrations', function (Blueprint $table) {
            try {
                $table->index(['competition_id', 'status'], 'reg_comp_status');
            } catch (\Throwable $e) {
                // index already exists
            }

            if (Schema::hasColumn('registrations', 'pool_id')) {
                try {
                    $table->index('pool_id', 'reg_pool');
                } catch (\Throwable $e) {
                    // index already exists
                }
            }
        });

        // frames indexes
        Schema::table('frames', function (Blueprint $table) {
            try {
                $table->index(['match_id', 'frame_number'], 'frames_match_frame');
            } catch (\Throwable $e) {
                // index already exists
            }
        });
    }

    public function down(): void
    {
        Schema::table('frames', function (Blueprint $table) {
            try {
                $table->dropIndex('frames_match_frame');
            } catch (\Throwable $e) {
                //
            }
        });

        Schema::table('registrations', function (Blueprint $table) {
            try {
                $table->dropIndex('reg_pool');
            } catch (\Throwable $e) {
                //
            }

            try {
                $table->dropIndex('reg_comp_status');
            } catch (\Throwable $e) {
                //
            }
        });

        Schema::table('matches', function (Blueprint $table) {
            foreach ([
                'matches_referee_status',
                'matches_pool_status',
                'matches_comp_phase_status',
                'matches_comp_phase_round',
                'matches_comp_status',
            ] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable $e) {
                    //
                }
            }
        });
    }
};
