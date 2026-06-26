<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Services\MatchStatisticsService;

class RecalculateStatisticsCommand extends Command
{
    protected $signature = 'c8p:recalculate-statistics {competition? : ID or slug}';
    protected $description = 'Recalculate player competition statistics from match events';

    public function handle(): int
    {
        $arg = $this->argument('competition');
        $query = GameMatch::where('status', 'done')
            ->whereNotNull('player_a_id')
            ->whereNotNull('player_b_id');

        if ($arg) {
            $comp = Competition::where('id', $arg)->orWhere('slug', $arg)->firstOrFail();
            $query->where('competition_id', $comp->id);
            $this->line("Processing: {$comp->name}");
        }

        $matches = $query->get();

        $this->withProgressBar($matches, function ($match) {
            MatchStatisticsService::aggregateForMatch($match);
        });

        $this->newLine();
        $this->info("Done. {$matches->count()} matches processed.");

        return Command::SUCCESS;
    }
}
