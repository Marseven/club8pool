<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Competition;
use App\Models\GameMatch;

class SyncFramesCommand extends Command
{
    protected $signature = 'c8p:sync-frames
        {competition? : Competition ID or slug}
        {--dry-run : Show issues without fixing}';

    protected $description = 'Detect and fix inconsistencies between matches.frames JSON and the frames table';

    public function handle(): int
    {
        $arg = $this->argument('competition');
        $query = GameMatch::with('frameRecords');

        if ($arg) {
            $comp = Competition::where('id', $arg)->orWhere('slug', $arg)->first();
            if (! $comp) {
                $this->error('Competition not found.');
                return Command::FAILURE;
            }
            $query->where('competition_id', $comp->id);
        }

        $matches = $query->get();
        $issues = 0;

        foreach ($matches as $match) {
            $frameCount     = $match->frameRecords->count();
            $scoreFromFramesA = $match->frameRecords->where('winner', 'A')->count();
            $scoreFromFramesB = $match->frameRecords->where('winner', 'B')->count();

            $discrepancyA = $match->score_a !== $scoreFromFramesA;
            $discrepancyB = $match->score_b !== $scoreFromFramesB;

            if ($discrepancyA || $discrepancyB) {
                $issues++;
                $this->warn(
                    "Match #{$match->id}: " .
                    "score_a={$match->score_a} vs frames_A={$scoreFromFramesA}, " .
                    "score_b={$match->score_b} vs frames_B={$scoreFromFramesB}"
                );

                if (! $this->option('dry-run') && $frameCount > 0) {
                    // Trust the frames table as source of truth
                    $match->update([
                        'score_a' => $scoreFromFramesA,
                        'score_b' => $scoreFromFramesB,
                    ]);
                    $this->line('  → Fixed');
                }
            }
        }

        if ($issues === 0) {
            $this->info("No inconsistencies found in {$matches->count()} matches.");
        } else {
            $suffix = $this->option('dry-run') ? ' (dry run — not fixed)' : ' fixed';
            $this->warn("{$issues} inconsistencies{$suffix}.");
        }

        return Command::SUCCESS;
    }
}
