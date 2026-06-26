<?php

namespace App\Console\Commands;

use App\Models\Competition;
use Illuminate\Console\Command;

class CloseExpiredCompetitions extends Command
{
    protected $signature   = 'c8p:close-expired';
    protected $description = 'Mark as finished any competition whose ends_on date has passed.';

    public function handle(): int
    {
        $before = Competition::whereNotNull('ends_on')
            ->whereDate('ends_on', '<', now()->toDateString())
            ->whereNotIn('status', ['finished'])
            ->count();

        Competition::autoCloseExpired();

        $this->info("$before competition(s) marked as finished.");

        return self::SUCCESS;
    }
}
