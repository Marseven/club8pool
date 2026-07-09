<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\GameMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSE2026BracketOrder extends Command
{
    protected $signature   = 'bracket:fix-se2026-order {--competition=}';
    protected $description = 'Fix R16 positions and QF player assignments for SE2026 bracket';

    public function handle(): int
    {
        $competition = $this->option('competition')
            ? Competition::findOrFail($this->option('competition'))
            : (Competition::current() ?? Competition::orderByDesc('starts_on')->firstOrFail());

        $this->line("Competition: {$competition->name} (#{$competition->id})");

        DB::transaction(function () use ($competition) {
            // Left side: fix QF pairings already created from played R16 matches
            $this->fixLeftQF($competition);

            // Right side: fix R16 round_positions so future QF creation is correct
            $this->fixRightR16($competition);

            // Right side: fix QF pairings if those matches were already created
            $this->fixRightQF($competition);

            // Left/right SF: fix SF pairings if QF was already played
            $this->fixLeftSF($competition);
            $this->fixRightSF($competition);
        });

        $this->info('Done.');
        return 0;
    }

    // ── Left QF ───────────────────────────────────────────────────────────────
    // Swap player_b(QF[0]) ↔ player_a(QF[1])
    // Wrong : QF[0]=Kass/Bobo  QF[1]=Salif/Serge
    // Right : QF[0]=Kass/Salif QF[1]=Bobo/Serge
    private function fixLeftQF(Competition $competition): void
    {
        $qf0 = $this->qf($competition, 0);
        $qf1 = $this->qf($competition, 1);

        if (! $qf0 || ! $qf1) {
            $this->warn('QF[0/1] not found — skipping left-QF fix.');
            return;
        }

        $this->before('QF', $qf0, $qf1);
        [$qf0->player_b_id,     $qf1->player_a_id    ] = [$qf1->player_a_id,     $qf0->player_b_id    ];
        [$qf0->player_b_source, $qf1->player_a_source] = [$qf1->player_a_source, $qf0->player_b_source];
        $qf0->save();
        $qf1->save();
        $this->after('QF', $qf0, $qf1);
    }

    // ── Right R16 positions ───────────────────────────────────────────────────
    // Cycle: pos7(AMA/DAN)→5, pos5(MIT/MOH)→6, pos6(PAO/PHI)→7
    // Wrong : pos5=MIT/MOH  pos6=PAO/PHI  pos7=AMA/DAN
    // Right : pos5=AMA/DAN  pos6=MIT/MOH  pos7=PAO/PHI
    private function fixRightR16(Competition $competition): void
    {
        $r5 = $this->r16($competition, 5);
        $r6 = $this->r16($competition, 6);
        $r7 = $this->r16($competition, 7);

        if (! $r5 || ! $r6 || ! $r7) {
            $this->warn('R16[5/6/7] not found — skipping right-R16 fix.');
            return;
        }

        $p = fn ($m) => "{$m->playerA?->first_name} vs {$m->playerB?->first_name}";
        $this->line("Before R16 pos5={$p($r5)} pos6={$p($r6)} pos7={$p($r7)}");

        // Temp position avoids unique-constraint collision during cycle
        $r5->round_position = 999; $r5->save();
        $r7->round_position = 5;   $r7->save();
        $r5->round_position = 6;   $r5->save();
        $r6->round_position = 7;   $r6->save();

        $this->info("After  R16 pos5={$p($r7->fresh())} pos6={$p($r5->fresh())} pos7={$p($r6->fresh())}");
    }

    // ── Right QF ──────────────────────────────────────────────────────────────
    // 3-way cycle on right side if QF[2/3] were created before the R16 fix.
    // Wrong : QF[2]=W(YOU/DIM)/W(MIT/MOH)  QF[3]=W(PAO/PHI)/W(AMA/DAN)
    // Right : QF[2]=W(YOU/DIM)/W(AMA/DAN)  QF[3]=W(MIT/MOH)/W(PAO/PHI)
    //
    // Cycle: QF[2].player_b → QF[3].player_a → QF[3].player_b → QF[2].player_b
    private function fixRightQF(Competition $competition): void
    {
        $qf2 = $this->qf($competition, 2);
        $qf3 = $this->qf($competition, 3);

        if (! $qf2 || ! $qf3) {
            $this->warn('QF[2/3] not found — skipping right-QF fix (expected if right R16 not played yet).');
            return;
        }

        $this->before('QF', $qf2, $qf3);

        $tmpId     = $qf2->player_b_id;
        $tmpSource = $qf2->player_b_source;

        $qf2->player_b_id     = $qf3->player_b_id;
        $qf2->player_b_source = $qf3->player_b_source;
        $qf2->save();

        $qf3->player_b_id     = $qf3->player_a_id;
        $qf3->player_b_source = $qf3->player_a_source;
        $qf3->player_a_id     = $tmpId;
        $qf3->player_a_source = $tmpSource;
        $qf3->save();

        $this->after('QF', $qf2, $qf3);
    }

    // ── Left SF ───────────────────────────────────────────────────────────────
    // Swap player_b(SF[0]) ↔ player_a(SF[0]) only if QF was already played.
    // Wrong : SF[0]=W(Kass/Bobo)/W(Salif/Serge)
    // Right : SF[0]=W(Kass/Salif)/W(Bobo/Serge)
    // Note: once QF is fixed above, this only matters if SF was auto-created
    //       from the wrong QF. Same swap pattern as left-QF.
    private function fixLeftSF(Competition $competition): void
    {
        $sf = $this->sf($competition, 0);

        if (! $sf) {
            $this->warn('SF[0] not found — skipping left-SF fix.');
            return;
        }

        $this->line("Before SF[0]: {$sf->playerA?->first_name} vs {$sf->playerB?->first_name}");

        // SF[0] has correct player_a (left-top QF winner) but wrong player_b
        // (it came from wrong QF[1] which had Salif instead of Bobo/Serge winner).
        // After left-QF fix this won't re-occur, but if SF was already created we
        // need to swap player_b of SF[0] with correct winner.
        // Since we can't re-derive who is correct without knowing QF outcomes,
        // we just warn the admin to verify manually.
        $this->warn('SF[0] exists — if it was created before the QF fix, verify its players manually.');
    }

    // ── Right SF ──────────────────────────────────────────────────────────────
    private function fixRightSF(Competition $competition): void
    {
        $sf = $this->sf($competition, 1);

        if (! $sf) {
            $this->warn('SF[1] not found — skipping right-SF fix.');
            return;
        }

        $this->line("Before SF[1]: {$sf->playerA?->first_name} vs {$sf->playerB?->first_name}");
        $this->warn('SF[1] exists — if it was created before the QF/R16 fix, verify its players manually.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function qf(Competition $c, int $pos): ?GameMatch
    {
        return GameMatch::where('competition_id', $c->id)
            ->where('phase', 'knockout')->where('round', 'QF')
            ->where('round_position', $pos)->first();
    }

    private function r16(Competition $c, int $pos): ?GameMatch
    {
        return GameMatch::where('competition_id', $c->id)
            ->where('phase', 'knockout')->where('round', 'R16')
            ->where('round_position', $pos)->first();
    }

    private function sf(Competition $c, int $pos): ?GameMatch
    {
        return GameMatch::where('competition_id', $c->id)
            ->where('phase', 'knockout')->where('round', 'SF')
            ->where('round_position', $pos)->first();
    }

    private function before(string $round, GameMatch $m0, GameMatch $m1): void
    {
        $this->line("Before {$round}[{$m0->round_position}]: {$m0->playerA?->first_name} vs {$m0->playerB?->first_name}");
        $this->line("Before {$round}[{$m1->round_position}]: {$m1->playerA?->first_name} vs {$m1->playerB?->first_name}");
    }

    private function after(string $round, GameMatch $m0, GameMatch $m1): void
    {
        $this->info("After  {$round}[{$m0->round_position}]: {$m0->fresh()->playerA?->first_name} vs {$m0->fresh()->playerB?->first_name}");
        $this->info("After  {$round}[{$m1->round_position}]: {$m1->fresh()->playerA?->first_name} vs {$m1->fresh()->playerB?->first_name}");
    }
}
