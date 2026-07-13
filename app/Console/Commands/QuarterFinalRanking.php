<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\GameMatch;
use Illuminate\Console\Command;

/**
 * Classement final des 8 quart-de-finalistes (1er → 8e).
 *
 * Méthode :
 *   1er  = vainqueur de la finale
 *   2e   = finaliste (perdant de la finale)
 *   3e   = vainqueur du match pour la 3e place (petite finale)
 *   4e   = perdant du match pour la 3e place
 *          (si pas de petite finale jouée : les 2 perdants de demi partagent 3e-4e,
 *           départagés au différentiel de manches)
 *   5e-8e = les 4 perdants de quart, départagés au différentiel de manches
 *
 * Départage à niveau égal : (manches gagnées − perdues) sur tout le tournoi,
 * puis manches gagnées, puis nom.
 */
class QuarterFinalRanking extends Command
{
    protected $signature   = 'bracket:qf-ranking {--competition=} {--json}';
    protected $description = 'Print the final 1st→8th ranking of the 8 quarter-finalists';

    public function handle(): int
    {
        $competition = $this->option('competition')
            ? Competition::findOrFail($this->option('competition'))
            : (Competition::current() ?? Competition::orderByDesc('starts_on')->firstOrFail());

        $ko = GameMatch::with(['playerA', 'playerB'])
            ->where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->get();

        $qf = $ko->where('round', 'QF');
        if ($qf->isEmpty()) {
            $this->error("Aucun quart de finale trouvé pour « {$competition->name} » (#{$competition->id}).");
            return 1;
        }

        // Les 8 joueurs des quarts.
        $qfPlayerIds = collect();
        foreach ($qf as $m) {
            if ($m->player_a_id) $qfPlayerIds->push($m->player_a_id);
            if ($m->player_b_id) $qfPlayerIds->push($m->player_b_id);
        }
        $qfPlayerIds = $qfPlayerIds->unique()->values();

        // Tallies de manches sur TOUT le tournoi (pool + knockout) pour départage.
        $tally = $this->frameTally($competition->id);

        $provisional = false;
        $tierOf = [];   // player_id => tier (plus petit = meilleur)

        $final = $ko->firstWhere('round', 'F');
        $third = $ko->firstWhere('round', '3P');
        $sf    = $ko->where('round', 'SF');

        // 1er / 2e — finale
        if ($final && $final->status === 'done' && $final->player_a_id && $final->player_b_id) {
            [$w, $l] = $this->winnerLoser($final);
            $tierOf[$w] = 1;
            $tierOf[$l] = 2;
        } else {
            $provisional = true;
        }

        // 3e / 4e — petite finale, sinon perdants de demi
        if ($third && $third->status === 'done' && $third->player_a_id && $third->player_b_id) {
            [$w, $l] = $this->winnerLoser($third);
            $tierOf[$w] = 3;
            $tierOf[$l] = 4;
        } else {
            // Pas de 3P décidée → les perdants de demi partagent 3e-4e (départagés au tally)
            foreach ($sf as $m) {
                if ($m->status !== 'done' || ! $m->player_a_id || ! $m->player_b_id) { $provisional = true; continue; }
                [, $l] = $this->winnerLoser($m);
                if (! isset($tierOf[$l])) $tierOf[$l] = 3; // 3-4 partagé
            }
        }

        // 5e-8e — perdants de quart
        foreach ($qf as $m) {
            if ($m->status !== 'done' || ! $m->player_a_id || ! $m->player_b_id) { $provisional = true; continue; }
            [, $l] = $this->winnerLoser($m);
            if (! isset($tierOf[$l])) $tierOf[$l] = 5; // 5-8 partagé
        }

        // Joueurs de quart dont le sort est encore inconnu → tier 9 (à jouer)
        foreach ($qfPlayerIds as $pid) {
            if (! isset($tierOf[$pid])) { $tierOf[$pid] = 9; $provisional = true; }
        }

        // Tri : tier asc, puis diff manches desc, puis manches gagnées desc, puis nom.
        $rows = $qfPlayerIds->map(function ($pid) use ($tierOf, $tally) {
            $t = $tally[$pid] ?? ['won' => 0, 'lost' => 0];
            return [
                'player_id' => $pid,
                'name'      => $this->playerName($pid),
                'tier'      => $tierOf[$pid],
                'won'       => $t['won'],
                'lost'      => $t['lost'],
                'diff'      => $t['won'] - $t['lost'],
            ];
        })->sort(function ($a, $b) {
            return [$a['tier'], -$a['diff'], -$a['won'], $a['name']]
               <=> [$b['tier'], -$b['diff'], -$b['won'], $b['name']];
        })->values();

        if ($this->option('json')) {
            $this->line($rows->map(fn ($r, $i) => ['rank' => $i + 1] + $r)->toJson(JSON_UNESCAPED_UNICODE));
            return 0;
        }

        // Sortie lisible (copiable dans WhatsApp).
        $this->newLine();
        $this->line("🏆 {$competition->name} — Classement des 8 quart-de-finalistes");
        if ($provisional) {
            $this->warn('⚠ Phase finale incomplète — classement PROVISOIRE (certains matchs non joués).');
        }
        $this->newLine();

        $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
        foreach ($rows as $i => $r) {
            $pos   = $i + 1;
            $badge = $medals[$pos] ?? "{$pos}.";
            $diff  = ($r['diff'] > 0 ? '+' : '') . $r['diff'];
            $flag  = $r['tier'] === 9 ? '  (encore en lice)' : '';
            $this->line(sprintf(
                '%s %-18s  manches %d-%d (%s)%s',
                $badge, $r['name'], $r['won'], $r['lost'], $diff, $flag
            ));
        }
        $this->newLine();

        return 0;
    }

    /** @return array{0:int,1:int} [winnerId, loserId] */
    private function winnerLoser(GameMatch $m): array
    {
        return $m->score_a > $m->score_b
            ? [$m->player_a_id, $m->player_b_id]
            : [$m->player_b_id, $m->player_a_id];
    }

    /** Total manches gagnées/perdues par joueur sur toute la compétition. */
    private function frameTally(int $competitionId): array
    {
        $tally = [];
        $matches = GameMatch::where('competition_id', $competitionId)
            ->where('status', 'done')
            ->get(['player_a_id', 'player_b_id', 'score_a', 'score_b']);

        foreach ($matches as $m) {
            if ($m->player_a_id) {
                $tally[$m->player_a_id]['won']  = ($tally[$m->player_a_id]['won']  ?? 0) + (int) $m->score_a;
                $tally[$m->player_a_id]['lost'] = ($tally[$m->player_a_id]['lost'] ?? 0) + (int) $m->score_b;
            }
            if ($m->player_b_id) {
                $tally[$m->player_b_id]['won']  = ($tally[$m->player_b_id]['won']  ?? 0) + (int) $m->score_b;
                $tally[$m->player_b_id]['lost'] = ($tally[$m->player_b_id]['lost'] ?? 0) + (int) $m->score_a;
            }
        }
        return $tally;
    }

    private function playerName(int $pid): string
    {
        static $cache = [];
        if (! isset($cache[$pid])) {
            $p = \App\Models\Player::find($pid);
            $cache[$pid] = $p ? trim($p->first_name . ' ' . $p->last_name) : "#{$pid}";
        }
        return $cache[$pid];
    }
}
