<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\GameMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddThirdPlaceMatch extends Command
{
    protected $signature   = 'bracket:add-third-place {--competition=}';
    protected $description = 'Add a 3rd-place (petite finale) match between the two SF losers to an existing bracket';

    public function handle(): int
    {
        $competition = $this->option('competition')
            ? Competition::findOrFail($this->option('competition'))
            : (Competition::current() ?? Competition::orderByDesc('starts_on')->firstOrFail());

        $this->line("Competition: {$competition->name} (#{$competition->id})");

        // The bracket must have a semi-final round for a 3rd-place match to make sense.
        $sf = GameMatch::where('competition_id', $competition->id)
            ->where('phase', 'knockout')
            ->where('round', 'SF')
            ->orderBy('round_position')
            ->get();

        if ($sf->isEmpty()) {
            $this->error('No SF (demi-finale) round found — cannot add a 3rd-place match.');
            return 1;
        }

        DB::transaction(function () use ($competition, $sf) {
            // 1) Persist the flag so future regenerations keep the 3P match.
            $settings = $competition->settings ?? [];
            if (! ($settings['has_third_place_match'] ?? false)) {
                $settings['has_third_place_match'] = true;
                $competition->update(['settings' => $settings]);
                $this->info('Enabled settings.has_third_place_match.');
            }

            // 2) Create the 3P placeholder if missing.
            $third = GameMatch::where('competition_id', $competition->id)
                ->where('phase', 'knockout')
                ->where('round', '3P')
                ->where('round_position', 0)
                ->first();

            if ($third) {
                $this->line('3P match already exists (#' . $third->id . ') — reusing.');
            } else {
                $third = GameMatch::create([
                    'competition_id' => $competition->id,
                    'phase'          => 'knockout',
                    'round'          => '3P',
                    'round_position' => 0,
                    'score_a'        => 0,
                    'score_b'        => 0,
                    'status'         => 'pending',
                ]);
                $this->info('Created 3P match (#' . $third->id . ').');
            }

            // 3) If a SF is already decided, drop its loser into the 3P match.
            //    SF pos 0 loser → player_a, SF pos 1 loser → player_b.
            foreach ($sf as $m) {
                if ($m->status !== 'done') continue;
                $loserId = $m->score_a > $m->score_b ? $m->player_b_id : $m->player_a_id;
                if (! $loserId) continue;

                $side = $m->round_position === 0 ? 'player_a_id' : 'player_b_id';
                if ($third->{$side} !== $loserId) {
                    $third->{$side} = $loserId;
                }
            }

            // Schedule the 3P once both slots are filled.
            if ($third->player_a_id && $third->player_b_id && $third->status === 'pending') {
                $third->status = 'scheduled';
            }
            $third->save();

            $third->loadMissing(['playerA', 'playerB']);
            $a = $third->playerA?->first_name ?? 'TBD';
            $b = $third->playerB?->first_name ?? 'TBD';
            $this->info("3P: {$a} vs {$b} [{$third->status}]");
        });

        $this->info('Done.');
        return 0;
    }
}
