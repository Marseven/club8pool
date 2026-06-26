<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Competition;
use App\Services\PlayerRatingService;

class RecalculateRatingsCommand extends Command
{
    protected $signature = 'c8p:recalculate-ratings {competition? : ID or slug of competition}';
    protected $description = 'Recalculate player ratings from match results';

    public function handle(): int
    {
        $arg = $this->argument('competition');
        if ($arg) {
            $comp = Competition::where('id', $arg)->orWhere('slug', $arg)->firstOrFail();
            $competitions = collect([$comp]);
        } else {
            $competitions = Competition::where('status', 'in_progress')
                ->orWhere('status', 'finished')
                ->get();
        }

        $service = new PlayerRatingService();
        $total = 0;

        foreach ($competitions as $comp) {
            $this->line("Processing: {$comp->name}");
            $count = $service->recalculateForCompetition($comp->id);
            $this->info("  → {$count} matches rated");
            $total += $count;
        }

        $this->info("Done. Total matches processed: {$total}");

        return Command::SUCCESS;
    }
}
