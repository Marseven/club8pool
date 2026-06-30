<?php

/**
 * SummerE2ESeeder — Club 8 Pool
 *
 * Tournoi fictif complet, miroir exact de la configuration Summer Edition,
 * pour les tests de bout en bout (QA, CI, arbitres, comptes joueurs).
 *
 * Configuration :
 *   · 8 poules × 6 joueurs = 48 joueurs  (qualifiers_per_pool = 4)
 *   · Race-to poules : 4  |  KO : R32/R16→7, QF/SF→9, 3P→5, F→11
 *   · Bracket : R32 (32 qualifiés)
 *
 * État généré :
 *   · Phase de poules    TERMINÉE  (120 matchs DONE)
 *   · R32                TERMINÉE  (16 matchs DONE)
 *   · R16                EN COURS  (4 DONE + 1 LIVE + 3 scheduled)
 *   · QF / SF / 3P / F  À VENIR   (scheduled, sans joueurs — admin génère)
 *
 * Comptes :
 *   · 48 joueurs — login = prénom en minuscule, mdp = 1234567
 *   · Arbitre Ali E2E (PIN : 1234)  |  Sam E2E (PIN : 5678)
 *
 * Usage (idempotent) :
 *   php artisan db:seed --class=SummerE2ESeeder
 */

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\PlayerRating;
use App\Models\Pool;
use App\Models\PoolTable;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SummerE2ESeeder extends Seeder
{
    private const SLUG = 'summer-cup-e2e-2026';

    /**
     * 48 joueurs fictifs — 6 par poule (A→H).
     * Triés par rating décroissant dans chaque groupe → classement déterministe.
     * Les prénoms sont tous uniques pour garantir des login_slug distincts.
     *
     * Format : [prénom, nom, fgb_card, rating]
     */
    private array $playerData = [
        // Poule A (indices 0–5)
        ['Dimitri',  'MOUSSAVOU',  'E2E-P001', 1820],
        ['Frederic', 'OLLAME',     'E2E-P002', 1640],
        ['Serge',    'KOUMBA',     'E2E-P003', 1520],
        ['Michel',   'IBINGA',     'E2E-P004', 1410],
        ['Fabrice',  'NZOUBA',     'E2E-P005', 1320],
        ['Alexis',   'MOUELE',     'E2E-P006', 1210],
        // Poule B (indices 6–11)
        ['Guy',      'NKOULOU',    'E2E-P007', 1790],
        ['Eric',     'MONGO',      'E2E-P008', 1620],
        ['Rodrigue', 'BOUANGA',    'E2E-P009', 1500],
        ['Patrick',  'MEZUI',      'E2E-P010', 1390],
        ['Claude',   'MBOUMBA',    'E2E-P011', 1300],
        ['Joseph',   'NDONG',      'E2E-P012', 1180],
        // Poule C (indices 12–17)
        ['Alain',    'OBAME',      'E2E-P013', 1760],
        ['Valery',   'NGUEMA',     'E2E-P014', 1600],
        ['Bruno',    'OVONO',      'E2E-P015', 1480],
        ['Joel',     'ELLA',       'E2E-P016', 1370],
        ['Thierry',  'MOUBERI',    'E2E-P017', 1280],
        ['Constant', 'ASSEKO',     'E2E-P018', 1160],
        // Poule D (indices 18–23)
        ['Sylvain',  'NDOMBI',     'E2E-P019', 1740],
        ['Romain',   'ESSONO',     'E2E-P020', 1580],
        ['Calvin',   'AKOUE',      'E2E-P021', 1460],
        ['Steeve',   'MINKUE',     'E2E-P022', 1350],
        ['Yves',     'BIYOGHE',    'E2E-P023', 1260],
        ['Jude',     'ONDO',       'E2E-P024', 1150],
        // Poule E (indices 24–29)
        ['Armel',    'NDONG',      'E2E-P025', 1720],
        ['Kevin',    'BONGO',      'E2E-P026', 1560],
        ['Nathan',   'MOUYAMA',    'E2E-P027', 1440],
        ['Franck',   'OBIANG',     'E2E-P028', 1330],
        ['Axel',     'MBOULA',     'E2E-P029', 1240],
        ['Steve',    'KOUMBA',     'E2E-P030', 1130],
        // Poule F (indices 30–35)
        ['Cedric',   'NKOGHO',     'E2E-P031', 1700],
        ['Jordan',   'ESSAMA',     'E2E-P032', 1540],
        ['Merlin',   'ALLOGHO',    'E2E-P033', 1420],
        ['Stephane', 'BOUROBOU',   'E2E-P034', 1310],
        ['Bienvenu', 'MENGUE',     'E2E-P035', 1220],
        ['Herve',    'NKOMBE',     'E2E-P036', 1100],
        // Poule G (indices 36–41)
        ['Lionel',   'MOUSSAVOU',  'E2E-P037', 1680],
        ['Wilfried', 'BIYEKO',     'E2E-P038', 1520],
        ['Samuel',   'KOMBILA',    'E2E-P039', 1400],
        ['Theo',     'MOUNDOUNGA', 'E2E-P040', 1290],
        ['Roger',    'NKOYI',      'E2E-P041', 1200],
        ['Justin',   'BEKA',       'E2E-P042', 1090],
        // Poule H (indices 42–47)
        ['Ricky',    'OVONO',      'E2E-P043', 1660],
        ['Florent',  'NDJOYE',     'E2E-P044', 1500],
        ['Dede',     'LEBAMA',     'E2E-P045', 1380],
        ['Willy',    'BOUSSOUGOU', 'E2E-P046', 1270],
        ['Martin',   'NDONG',      'E2E-P047', 1170],
        ['Cesar',    'OGANDAGA',   'E2E-P048', 1060],
    ];

    public function run(): void
    {
        // ── 1. Compétition ────────────────────────────────────────────────────
        $comp = Competition::updateOrCreate(
            ['slug' => self::SLUG],
            [
                'name'                             => 'Summer Cup E2E 2026',
                'discipline'                       => '8-ball',
                'format'                           => 'pools',
                'structure'                        => 'pools_knockout',
                'race_to'                          => 7,
                'pool_race_to'                     => 4,
                'knockout_race_to'                 => 7,
                'status'                           => 'in_progress',
                'player_slots'                     => 48,
                'pool_count'                       => 8,
                'pool_size'                        => 6,
                'qualifiers_per_pool'              => 4,
                'shot_clock_enabled'               => true,
                'shot_clock'                       => 30,
                'shot_clock_late_seconds'          => 30,
                'shot_clock_late_rule'             => 'never',
                'shot_clock_extensions_per_player' => 0,
                'seed_strategy'                    => 'rating',
                'seeded_players_count'             => 8,
                'draw_randomize_unseeded'          => true,
                'alternate_break'                  => true,
                'allow_draw'                       => false,
                'enable_warnings'                  => true,
                'push_out'                         => false,
                'push_out_enabled'                 => false,
                'tiebreak_race'                    => 3,
                'tie_break_mode'                   => 'shootout',
                'rack_mode'                        => 'template',
                'venue'                            => "L'Icône",
                'city'                             => 'Libreville',
                'entry_fee'                        => 5000,
                'deposit'                          => 0,
                'prize_pool'                       => 1120000,
                'starts_on'                        => '2026-07-04',
                'ends_on'                          => '2026-07-12',
                'settings'                         => [
                    'round_race_to'         => ['R32' => 7, 'R16' => 7, 'QF' => 9, 'SF' => 9, '3P' => 5, 'F' => 11],
                    'has_third_place_match' => true,
                    'online_registration'   => false,
                    'prize_breakdown'       => [
                        '1st'     => ['label' => 'Champion',        'amount' => 500000,  'currency' => 'XAF'],
                        '2nd'     => ['label' => 'Finaliste',       'amount' => 250000,  'currency' => 'XAF'],
                        '3rd'     => ['label' => 'Troisième place', 'amount' => 150000,  'currency' => 'XAF'],
                        '4th'     => ['label' => 'Quatrième place', 'amount' => 100000,  'currency' => 'XAF'],
                        '5th-8th' => ['label' => '5e–8e place', 'amount_each' => 30000, 'players' => 4, 'currency' => 'XAF'],
                    ],
                    'schedule' => [
                        'timezone' => 'Africa/Libreville',
                        'days' => [
                            ['date' => '2026-07-04', 'label' => 'Sam 4 juillet — Poules A, B, C',  'items' => [['phase' => 'pools', 'pool_range' => ['A', 'C'], 'starts_at' => null, 'ends_at' => null]]],
                            ['date' => '2026-07-05', 'label' => 'Dim 5 juillet — Poules D, E, F',  'items' => [['phase' => 'pools', 'pool_range' => ['D', 'F'], 'starts_at' => null, 'ends_at' => null]]],
                            ['date' => '2026-07-06', 'label' => 'Lun 6 juillet — Poules G, H',     'items' => [['phase' => 'pools', 'pool_range' => ['G', 'H'], 'starts_at' => null, 'ends_at' => null]]],
                            ['date' => '2026-07-07', 'label' => 'Mar 7 juillet — R32',             'items' => [['phase' => 'knockout', 'rounds' => ['R32'], 'starts_at' => null, 'ends_at' => null]]],
                            ['date' => '2026-07-08', 'label' => 'Mer 8 juillet — R16',             'items' => [['phase' => 'knockout', 'rounds' => ['R16'], 'starts_at' => null, 'ends_at' => null]]],
                            ['date' => '2026-07-12', 'label' => 'Sam 12 juillet — Final 8',        'items' => [['phase' => 'knockout', 'rounds' => ['QF', 'SF', '3P', 'F'], 'starts_at' => null, 'ends_at' => null]]],
                        ],
                    ],
                ],
            ]
        );
        $this->command->info("Compétition : {$comp->name} [ID {$comp->id}]");

        // ── 2. Tables de jeu ─────────────────────────────────────────────────
        $tables = [];
        foreach (['Table 1', 'Table 2'] as $name) {
            $tables[] = PoolTable::firstOrCreate(
                ['competition_id' => $comp->id, 'name' => $name],
                ['location' => "L'Icône", 'status' => 'idle']
            );
        }
        $this->command->info('2 tables OK.');

        // ── 3. Joueurs avec comptes ───────────────────────────────────────────
        $players    = [];
        $usedSlugs  = [];

        foreach ($this->playerData as [$firstName, $lastName, $fgbCard, $rating]) {
            $base = strtolower((string) preg_replace('/[^a-z0-9]/i', '', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $firstName) ?: $firstName));
            $slug = $base ?: 'joueur';
            $n = 2;
            while (
                in_array($slug, $usedSlugs, true) ||
                Player::where('login_slug', $slug)->where('fgb_card', '!=', $fgbCard)->exists()
            ) {
                $slug = $base . '-' . $n++;
            }
            $usedSlugs[] = $slug;

            $players[] = Player::updateOrCreate(
                ['fgb_card' => $fgbCard],
                [
                    'first_name'                => $firstName,
                    'last_name'                 => $lastName,
                    'phone'                     => '',
                    'email'                     => '',
                    'address'                   => null,
                    'birthdate'                 => null,
                    'cue'                       => null,
                    'rating'                    => $rating,
                    'wins'                      => 0,
                    'losses'                    => 0,
                    'login_name'                => $firstName,
                    'login_slug'                => $slug,
                    'password'                  => Hash::make('1234567'),
                    'must_change_password'      => false,
                    'is_player_account_enabled' => true,
                ]
            );
        }
        $this->command->info('48 joueurs OK  (login = prénom minuscule, mdp = 1234567).');

        // ── 4. Poules & inscriptions ──────────────────────────────────────────
        $letters     = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $pools       = [];   // $pools['A'] = Pool
        $poolPlayers = [];   // $poolPlayers['A'] = [Player×6]  (index 0 = meilleur)

        foreach ($letters as $pi => $letter) {
            $pool                 = Pool::firstOrCreate(
                ['competition_id' => $comp->id, 'name' => $letter],
                ['position' => $pi, 'size' => 6]
            );
            $pools[$letter]       = $pool;
            $poolPlayers[$letter] = array_slice($players, $pi * 6, 6);

            foreach ($poolPlayers[$letter] as $slot => $player) {
                Registration::firstOrCreate(
                    ['competition_id' => $comp->id, 'player_id' => $player->id],
                    [
                        'pool_id'       => $pool->id,
                        'pool_slot'     => $slot + 1,
                        'seed'          => $pi * 6 + $slot + 1,
                        'seed_rating'   => $player->rating,
                        'status'        => 'confirmed',
                        'registered_at' => now()->subHours(48),
                    ]
                );
            }
        }
        $this->command->info('8 poules + 48 inscriptions confirmées OK.');

        // ── 5. Matchs de poule — round-robin 6 joueurs (C(6,2)=15 / poule) ──
        // Règle déterministe : indice i < j ⟹ players[i] gagne (rating plus élevé).
        // Score gagnant = 4 (pool_race_to) ; perdant = max(0, 4 − (j−i)).
        $allPairs = [
            [0,1],[0,2],[0,3],[0,4],[0,5],
            [1,2],[1,3],[1,4],[1,5],
            [2,3],[2,4],[2,5],
            [3,4],[3,5],
            [4,5],
        ];
        $poolMatchCount = 0;

        foreach ($letters as $pi => $letter) {
            $pool = $pools[$letter];
            $pp   = $poolPlayers[$letter];

            foreach ($allPairs as $pos => [$ia, $ib]) {
                if (GameMatch::where('competition_id', $comp->id)
                        ->where('pool_id', $pool->id)
                        ->where('player_a_id', $pp[$ia]->id)
                        ->where('player_b_id', $pp[$ib]->id)
                        ->exists()) {
                    continue;
                }

                $loserScore = max(0, 4 - ($ib - $ia));
                $endedAt    = now()->subHours(10 - $pi)->subMinutes($pos * 5);

                GameMatch::create([
                    'competition_id' => $comp->id,
                    'pool_id'        => $pool->id,
                    'phase'          => 'pool',
                    'round'          => 'R16',   // convention système pour les matchs de poule
                    'round_position' => $pos,
                    'player_a_id'    => $pp[$ia]->id,
                    'player_b_id'    => $pp[$ib]->id,
                    'score_a'        => 4,
                    'score_b'        => $loserScore,
                    'status'         => 'done',
                    'is_draw'        => false,
                    'started_at'     => (clone $endedAt)->subMinutes(30),
                    'ended_at'       => $endedAt,
                    'warning_a'      => false,
                    'warning_b'      => false,
                ]);
                $poolMatchCount++;
            }
        }
        $this->command->info("{$poolMatchCount} matchs de poule créés (tous DONE).");

        // ── 6. Qualifiés (4 premiers de chaque poule = 32 qualifiés) ─────────
        // Classement déterministe : indice 0 = 1er, ..., indice 3 = 4ème.
        //
        // Seed globale :
        //   Seeds 1–8   : rang 1 de chaque poule  (A1, B1, C1, D1, E1, F1, G1, H1)
        //   Seeds 9–16  : rang 2 de chaque poule  (A2, B2, …, H2)
        //   Seeds 17–24 : rang 3 de chaque poule
        //   Seeds 25–32 : rang 4 de chaque poule
        $qualifiers = [];
        for ($rank = 0; $rank < 4; $rank++) {
            foreach ($letters as $letter) {
                $qualifiers[] = $poolPlayers[$letter][$rank];
            }
        }
        // $qualifiers[0]  = A1  (seed 1)
        // $qualifiers[7]  = H1  (seed 8)
        // $qualifiers[8]  = A2  (seed 9)
        // $qualifiers[15] = H2  (seed 16)
        // …

        // ── 7. R32 — 16 matchs (seed i vs seed 31−i), tous DONE ─────────────
        // Score (race_to 7) : gagnant=7, perdant ∝ écart de seed.
        $r32Scores = [
            [7,1],[7,2],[7,2],[7,3],[7,3],[7,4],[7,4],[7,5],  // positions 0–7
            [7,4],[7,4],[7,5],[7,5],[7,5],[7,6],[7,6],[7,6],  // positions 8–15
        ];

        for ($pos = 0; $pos < 16; $pos++) {
            $pa = $qualifiers[$pos];
            $pb = $qualifiers[31 - $pos];

            if (GameMatch::where('competition_id', $comp->id)
                    ->where('phase', 'knockout')->where('round', 'R32')->where('round_position', $pos)
                    ->exists()) {
                continue;
            }

            [$sa, $sb] = $r32Scores[$pos];
            GameMatch::create([
                'competition_id' => $comp->id,
                'phase'          => 'knockout',
                'round'          => 'R32',
                'round_position' => $pos,
                'player_a_id'    => $pa->id,
                'player_b_id'    => $pb->id,
                'score_a'        => $sa,
                'score_b'        => $sb,
                'status'         => 'done',
                'is_draw'        => false,
                'started_at'     => now()->subHours(5)->subMinutes($pos * 8),
                'ended_at'       => now()->subHours(4)->subMinutes($pos * 6),
                'warning_a'      => false,
                'warning_b'      => false,
            ]);
        }
        $this->command->info('16 matchs R32 créés (tous DONE).');

        // Vainqueurs R32 : les 16 têtes de série (qualifiers[0..15]) avancent.
        $r32Winners = array_slice($qualifiers, 0, 16);
        // r32Winners[0..7]   = A1,B1,C1,D1,E1,F1,G1,H1  (seeds 1-8)
        // r32Winners[8..15]  = A2,B2,C2,D2,E2,F2,G2,H2  (seeds 9-16)

        // ── 8. R16 — 8 matchs (winner[i] vs winner[15−i]) ────────────────────
        // R16-0 : A1 vs H2  → DONE  7-3
        // R16-1 : B1 vs G2  → DONE  7-4
        // R16-2 : C1 vs F2  → DONE  7-5
        // R16-3 : D1 vs E2  → DONE  7-6
        // R16-4 : E1 vs D2  → LIVE  3-1  (arbitre Ali affecté)
        // R16-5 : F1 vs C2  → scheduled
        // R16-6 : G1 vs B2  → scheduled
        // R16-7 : H1 vs A2  → scheduled
        $r16Spec = [
            ['done',      7, 3],
            ['done',      7, 4],
            ['done',      7, 5],
            ['done',      7, 6],
            ['live',      3, 1],
            ['scheduled', 0, 0],
            ['scheduled', 0, 0],
            ['scheduled', 0, 0],
        ];

        for ($pos = 0; $pos < 8; $pos++) {
            $pa = $r32Winners[$pos];
            $pb = $r32Winners[15 - $pos];

            if (GameMatch::where('competition_id', $comp->id)
                    ->where('phase', 'knockout')->where('round', 'R16')->where('round_position', $pos)
                    ->exists()) {
                continue;
            }

            [$status, $sa, $sb] = $r16Spec[$pos];

            $attrs = [
                'competition_id' => $comp->id,
                'phase'          => 'knockout',
                'round'          => 'R16',
                'round_position' => $pos,
                'player_a_id'    => $pa->id,
                'player_b_id'    => $pb->id,
                'score_a'        => $sa,
                'score_b'        => $sb,
                'status'         => $status,
                'is_draw'        => false,
                'warning_a'      => false,
                'warning_b'      => false,
            ];

            if ($status === 'done') {
                $attrs['started_at'] = now()->subHours(2)->subMinutes($pos * 12);
                $attrs['ended_at']   = now()->subHours(1)->subMinutes($pos * 8);
            } elseif ($status === 'live') {
                $attrs['started_at']    = now()->subMinutes(28);
                $attrs['pool_table_id'] = $tables[0]->id;
            } else {
                $attrs['scheduled_at'] = now()->addHours($pos - 3);
            }

            GameMatch::create($attrs);
        }
        $this->command->info('8 matchs R16 créés (4 DONE, 1 LIVE, 3 scheduled).');

        // ── 9. QF / SF / 3P / F — squelettes scheduled (joueurs non encore connus) ─
        foreach ([['QF', 4], ['SF', 2], ['3P', 1], ['F', 1]] as [$round, $count]) {
            for ($pos = 0; $pos < $count; $pos++) {
                if (GameMatch::where('competition_id', $comp->id)
                        ->where('phase', 'knockout')->where('round', $round)->where('round_position', $pos)
                        ->exists()) {
                    continue;
                }
                GameMatch::create([
                    'competition_id' => $comp->id,
                    'phase'          => 'knockout',
                    'round'          => $round,
                    'round_position' => $pos,
                    'player_a_id'    => null,
                    'player_b_id'    => null,
                    'score_a'        => 0,
                    'score_b'        => 0,
                    'status'         => 'scheduled',
                    'scheduled_at'   => now()->addDays(4),
                    'is_draw'        => false,
                    'warning_a'      => false,
                    'warning_b'      => false,
                ]);
            }
        }
        $this->command->info('QF/SF/3P/F créés (scheduled, joueurs TBD).');

        // ── 10. Arbitres ──────────────────────────────────────────────────────
        $ali = User::firstOrCreate(
            ['email' => 'ali.e2e@club8pool.local'],
            [
                'name'              => 'Ali E2E',
                'password'          => Hash::make('E2EPass2026!'),
                'fgb_card'          => 'E2E-ARB-01',
                'pin'               => Hash::make('1234'),
                'role'              => 'referee',
                'title'             => 'Arbitre E2E',
                'is_referee_active' => true,
                'login_slug'        => 'ali-e2e',
            ]
        );

        User::firstOrCreate(
            ['email' => 'sam.e2e@club8pool.local'],
            [
                'name'              => 'Sam E2E',
                'password'          => Hash::make('E2EPass2026!'),
                'fgb_card'          => 'E2E-ARB-02',
                'pin'               => Hash::make('5678'),
                'role'              => 'referee',
                'title'             => 'Arbitre E2E',
                'is_referee_active' => true,
                'login_slug'        => 'sam-e2e',
            ]
        );

        // Affecter Ali au match LIVE
        GameMatch::where('competition_id', $comp->id)
            ->where('round', 'R16')
            ->where('status', 'live')
            ->whereNull('referee_id')
            ->update(['referee_id' => $ali->id]);

        // Admin global (créé seulement si aucun n'existe)
        if (! User::where('role', 'admin')->exists()) {
            User::firstOrCreate(
                ['email' => 'admin@club8pool.com'],
                [
                    'name'     => 'Admin E2E',
                    'password' => Hash::make('AdminE2E2026!'),
                    'role'     => 'admin',
                    'title'    => 'Administrateur',
                ]
            );
        }
        $this->command->info('Arbitres Ali (PIN 1234) & Sam (PIN 5678) OK.');

        // ── 11. Ratings ELO ───────────────────────────────────────────────────
        foreach ($players as $player) {
            PlayerRating::firstOrCreate(
                ['player_id' => $player->id, 'discipline' => '8-ball'],
                [
                    'rating'        => $player->rating,
                    'games_played'  => 0,
                    'frames_won'    => 0,
                    'frames_lost'   => 0,
                    'robustness'    => 0,
                    'provisional'   => true,
                    'last_match_at' => null,
                ]
            );
        }
        $this->command->info('48 ratings ELO OK.');

        // ── Résumé ────────────────────────────────────────────────────────────
        $matchTotal = GameMatch::where('competition_id', $comp->id)->count();
        $liveMatch  = GameMatch::where('competition_id', $comp->id)->where('status', 'live')->first();

        $this->command->newLine();
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('SummerE2ESeeder — TERMINÉ');
        $this->command->line("  Compétition ID : {$comp->id}");
        $this->command->line('  URL admin      : /admin/competitions/' . $comp->id);
        $this->command->line("  Joueurs : 48  |  Poules : 8×6  |  Matchs total : {$matchTotal}");
        $this->command->line('  Comptes joueur : login = prénom en minuscule, mdp = 1234567');
        if ($liveMatch) {
            $pa = $liveMatch->playerA?->name ?? '?';
            $pb = $liveMatch->playerB?->name ?? '?';
            $this->command->line("  Match LIVE     : R16 pos #{$liveMatch->round_position} — {$pa} {$liveMatch->score_a}-{$liveMatch->score_b} {$pb}  (arbitre: Ali E2E)");
        }
        $this->command->line('  Arbitres       : Ali E2E (PIN 1234)  |  Sam E2E (PIN 5678)');
        $this->command->line('  État bracket   : Poules ✓  R32 ✓  R16 en cours (4 DONE, 1 LIVE, 3 sch.)  QF→F scheduled');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }
}
