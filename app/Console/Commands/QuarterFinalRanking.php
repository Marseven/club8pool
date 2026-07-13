<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Services\QuarterFinalRankingService;
use Illuminate\Console\Command;

class QuarterFinalRanking extends Command
{
    protected $signature   = 'bracket:qf-ranking {--competition=} {--json}';
    protected $description = 'Print the final 1st→8th ranking of the 8 quarter-finalists';

    public function handle(QuarterFinalRankingService $service): int
    {
        $competition = $this->option('competition')
            ? Competition::findOrFail($this->option('competition'))
            : (Competition::current() ?? Competition::orderByDesc('starts_on')->firstOrFail());

        $result = $service->compute($competition);

        if (! $result['has_qf']) {
            $this->error("Aucun quart de finale trouvé pour « {$competition->name} » (#{$competition->id}).");
            return 1;
        }

        if ($this->option('json')) {
            $this->line(json_encode($result['rows'], JSON_UNESCAPED_UNICODE));
            return 0;
        }

        $this->newLine();
        $this->line("🏆 {$competition->name} — Classement des 8 quart-de-finalistes");
        if ($result['provisional']) {
            $this->warn('⚠ Phase finale incomplète — classement PROVISOIRE (certains matchs non joués).');
        }
        $this->newLine();

        $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
        foreach ($result['rows'] as $r) {
            $badge = $medals[$r['rank']] ?? "{$r['rank']}.";
            $diff  = ($r['diff'] > 0 ? '+' : '') . $r['diff'];
            $flag  = $r['in_play'] ? '  (encore en lice)' : '';
            $this->line(sprintf('%s %-18s  manches %d-%d (%s)%s', $badge, $r['name'], $r['won'], $r['lost'], $diff, $flag));
        }
        $this->newLine();

        return 0;
    }
}
