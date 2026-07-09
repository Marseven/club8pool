<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\GameMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSE2026BracketOrder extends Command
{
    protected $signature   = 'bracket:fix-se2026-order {--competition=}';
    protected $description = 'Fix R16 round_position order and QF player assignments for SE2026 bracket';

    public function handle(): int
    {
        $competition = $this->option('competition')
            ? Competition::findOrFail($this->option('competition'))
            : (Competition::current() ?? Competition::orderByDesc('starts_on')->firstOrFail());

        $this->line("Competition: {$competition->name} (#{$competition->id})");

        DB::transaction(function () use ($competition) {
            $this->fixLeftQF($competition);
            $this->fixRightR16($competition);
        });

        $this->info('Done.');
        return 0;
    }

    // Swap player_b of QF[0] ↔ player_a of QF[1]
    // Before : QF[0]=Kass/Bobo, QF[1]=Salif/Serge
    // After  : QF[0]=Kass/Salif, QF[1]=Bobo/Serge
    private function fixLeftQF(Competition $competition): void
    {
        $base = GameMatch::where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->where('round', 'QF');

        $qf0 = (clone $base)->where('round_position', 0)->first();
        $qf1 = (clone $base)->where('round_position', 1)->first();

        if (! $qf0 || ! $qf1) {
            $this->warn('QF[0] or QF[1] not found — skipping left-QF fix.');
            return;
        }

        $this->line("Before QF[0]: {$qf0->playerA?->first_name} vs {$qf0->playerB?->first_name}");
        $this->line("Before QF[1]: {$qf1->playerA?->first_name} vs {$qf1->playerB?->first_name}");

        [$qf0->player_b_id,     $qf1->player_a_id    ] = [$qf1->player_a_id,     $qf0->player_b_id    ];
        [$qf0->player_b_source, $qf1->player_a_source] = [$qf1->player_a_source, $qf0->player_b_source];
        $qf0->save();
        $qf1->save();

        $this->info("After  QF[0]: {$qf0->fresh()->playerA?->first_name} vs {$qf0->fresh()->playerB?->first_name}");
        $this->info("After  QF[1]: {$qf1->fresh()->playerA?->first_name} vs {$qf1->fresh()->playerB?->first_name}");
    }

    // Cycle R16 right positions: 5→6→7→5
    // Before : pos5=MIT/MOH, pos6=PAO/PHI, pos7=AMA/DAN
    // After  : pos5=AMA/DAN, pos6=MIT/MOH, pos7=PAO/PHI
    private function fixRightR16(Competition $competition): void
    {
        $base = GameMatch::where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->where('round', 'R16');

        $r5 = (clone $base)->where('round_position', 5)->first();
        $r6 = (clone $base)->where('round_position', 6)->first();
        $r7 = (clone $base)->where('round_position', 7)->first();

        if (! $r5 || ! $r6 || ! $r7) {
            $this->warn('R16 pos 5/6/7 not all found — skipping right-R16 fix.');
            return;
        }

        $showPair = fn ($m) => "{$m->playerA?->first_name} vs {$m->playerB?->first_name}";
        $this->line("Before pos5={$showPair($r5)}, pos6={$showPair($r6)}, pos7={$showPair($r7)}");

        // Use 999 as temp to avoid unique constraint violation during cycle
        $r5->round_position = 999; $r5->save();
        $r7->round_position = 5;   $r7->save();
        $r5->round_position = 6;   $r5->save();
        $r6->round_position = 7;   $r6->save();

        $this->info("After  pos5={$showPair($r7->fresh())}, pos6={$showPair($r5->fresh())}, pos7={$showPair($r6->fresh())}");
    }
}
