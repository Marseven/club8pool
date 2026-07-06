<?php

/**
 * Summer Edition — Configuration officielle de l'événement.
 *
 * Source : Calendrier officiel "Icône 8 Ball Championship – Summer Edition 2026"
 *          (document PDF du 04/07/2026, signé MRTECH / Organisation).
 * Utilisé par : database/seeders/SummerEditionSeeder.php
 */

return [

    // =========================================================================
    // COMPÉTITION
    // =========================================================================

    'competition' => [
        'name'                    => 'Summer Edition',
        'slug'                    => 'summer-edition',
        'discipline'              => '8-ball',
        'format'                  => 'pools',
        'structure'               => 'pools_knockout',
        'status'                  => 'draft',
        'player_slots'            => 40,
        'pool_count'              => 8,
        'pool_size'               => 5,
        'qualifiers_per_pool'     => 2,
        'seed_strategy'           => 'manual',
        'seeded_players_count'    => 8,
        'draw_randomize_unseeded' => true,
        'race_to'                 => 9,    // fallback global (R16/QF/SF)
        'pool_race_to'            => 5,
        'knockout_race_to'        => 9,    // fallback pour rounds non configurés
        'knockout_mapping_strategy' => 'pool_cross_ab_cd_ef_gh',
        'prize_pool'              => 1120000,
        'venue'                   => "L'Icône",
        'city'                    => 'Libreville',
        'starts_on'               => '2026-07-04',
        'ends_on'                 => '2026-07-12',
    ],

    // =========================================================================
    // FORMAT ET RÈGLES
    // =========================================================================

    'format' => [
        'shot_clock_enabled'               => true,
        'shot_clock'                       => 30,
        'shot_clock_first_shot'            => 45,
        'shot_clock_late_seconds'          => 30,
        'shot_clock_late_rule'             => 'never',
        'shot_clock_extensions_per_player' => 0,
        'tie_break_mode'                   => 'shootout',
        'rack_mode'                        => 'template',
        'alternate_break'                  => true,
        'allow_draw'                       => false,
        'enable_warnings'                  => true,
        'push_out'                         => false,
        'push_out_enabled'                 => false,
    ],

    // =========================================================================
    // RACE-TO PAR TOUR (phase finale)
    // Stocké dans settings['round_race_to'] sur la compétition.
    // 8 paires → R16 → QF → SF → F
    // =========================================================================

    'round_race_to' => [
        'R16' => 9,   // 1/16e de finale (premier tour KO)
        'QF'  => 9,   // Quart de finale
        'SF'  => 9,   // Demi-finale
        'F'   => 11,  // Finale
    ],

    // =========================================================================
    // TABLES DE JEU
    // =========================================================================

    'tables' => [
        ['name' => 'Table 1', 'location' => "L'Icône"],
        ['name' => 'Table 2', 'location' => "L'Icône"],
    ],

    // =========================================================================
    // POULES (5 joueurs × 8 poules = 40 joueurs)
    // =========================================================================

    'pools' => [
        ['name' => 'A', 'position' => 0, 'size' => 5],
        ['name' => 'B', 'position' => 1, 'size' => 5],
        ['name' => 'C', 'position' => 2, 'size' => 5],
        ['name' => 'D', 'position' => 3, 'size' => 5],
        ['name' => 'E', 'position' => 4, 'size' => 5],
        ['name' => 'F', 'position' => 5, 'size' => 5],
        ['name' => 'G', 'position' => 6, 'size' => 5],
        ['name' => 'H', 'position' => 7, 'size' => 5],
    ],

    // =========================================================================
    // TÊTES DE SÉRIE (liste officielle — prénoms/pseudos confirmés)
    // =========================================================================

    'top_seeds' => [
        ['seed' => 1, 'display_name' => 'Appolinaire', 'pool' => 'A'],
        ['seed' => 2, 'display_name' => 'Kass',        'pool' => 'B'],
        ['seed' => 3, 'display_name' => 'Benson',      'pool' => 'C'],
        ['seed' => 4, 'display_name' => 'Bobby',       'pool' => 'D'],
        ['seed' => 5, 'display_name' => 'Aziz',        'pool' => 'E'],
        ['seed' => 6, 'display_name' => 'Dimitri',     'pool' => 'F'],
        ['seed' => 7, 'display_name' => 'Attiss',      'pool' => 'G'],
        ['seed' => 8, 'display_name' => 'Paolo',       'pool' => 'H'],
    ],

    // =========================================================================
    // DOTATION
    // =========================================================================

    'prize_breakdown' => [
        '1st' => [
            'label'    => 'Champion',
            'amount'   => 500000,
            'currency' => 'XAF',
            'extras'   => ['Trophée', 'Médaille Or'],
        ],
        '2nd' => [
            'label'    => 'Finaliste',
            'amount'   => 250000,
            'currency' => 'XAF',
            'extras'   => ['Médaille Argent'],
        ],
        '3rd' => [
            'label'    => 'Troisième place',
            'amount'   => 150000,
            'currency' => 'XAF',
            'extras'   => ['Médaille Bronze'],
        ],
        '4th' => [
            'label'    => 'Quatrième place',
            'amount'   => 100000,
            'currency' => 'XAF',
            'extras'   => ['Médaille'],
        ],
        '5th-8th' => [
            'label'        => '5e–8e place',
            'amount_each'  => 30000,
            'players'      => 4,
            'amount_total' => 120000,
            'currency'     => 'XAF',
        ],
    ],

    // =========================================================================
    // CALENDRIER OFFICIEL (source : PDF du 04/07/2026)
    // =========================================================================

    'schedule' => [
        'timezone'              => 'Africa/Libreville',
        'registration_deadline' => null,
        'days' => [
            [
                'date'  => '2026-07-04',
                'label' => 'Samedi 4 juillet 2026',
                'items' => [
                    [
                        'phase'      => 'pools',
                        'label'      => 'Groupe A — Race to 5',
                        'pools'      => ['A'],
                        'starts_at'  => '14:00',
                    ],
                    [
                        'phase'      => 'pools',
                        'label'      => 'Groupe C — Race to 5',
                        'pools'      => ['C'],
                        'starts_at'  => '18:30',
                    ],
                ],
            ],
            [
                'date'  => '2026-07-05',
                'label' => 'Dimanche 5 juillet 2026',
                'items' => [
                    [
                        'phase'      => 'pools',
                        'label'      => 'Groupe B — Race to 5',
                        'pools'      => ['B'],
                        'starts_at'  => '14:00',
                    ],
                    [
                        'phase'      => 'pools',
                        'label'      => 'Groupe D — Race to 5',
                        'pools'      => ['D'],
                        'starts_at'  => '18:30',
                    ],
                ],
            ],
            [
                'date'  => '2026-07-06',
                'label' => 'Lundi 6 juillet 2026',
                'items' => [
                    [
                        'phase'      => 'pools',
                        'label'      => 'Groupes E & G — Race to 5',
                        'pools'      => ['E', 'G'],
                        'starts_at'  => '18:30',
                    ],
                ],
            ],
            [
                'date'  => '2026-07-07',
                'label' => 'Mardi 7 juillet 2026',
                'items' => [
                    [
                        'phase'      => 'pools',
                        'label'      => 'Groupes F & H — Race to 5',
                        'pools'      => ['F', 'H'],
                        'starts_at'  => '18:30',
                    ],
                ],
            ],
            [
                'date'  => '2026-07-12',
                'label' => 'Samedi 12 juillet 2026',
                'items' => [
                    [
                        'phase'     => 'knockout',
                        'label'     => '1/16e, QF, SF, Finale — Race to 9/9/9/11',
                        'rounds'    => ['R16', 'QF', 'SF', 'F'],
                        'starts_at' => null,
                    ],
                ],
            ],
        ],
    ],

    // =========================================================================
    // PAIEMENT
    // =========================================================================

    'payment' => [
        'registration_fee' => null,
        'currency'         => 'XAF',
        'methods'          => [
            [
                'type'         => 'mobile_money',
                'provider'     => null,
                'phone'        => '077 79 10 57',
                'account_name' => 'Dimitri',
            ],
            [
                'type'         => 'cash',
                'location'     => null,
                'contact_name' => 'Dimitri',
                'phone'        => '077 79 10 57',
            ],
        ],
        'contacts' => [
            [
                'role'  => 'Organisation',
                'name'  => 'Dimitri',
                'phone' => '077 79 10 57',
                'email' => null,
            ],
        ],
    ],

    // =========================================================================
    // INFORMATIONS MANQUANTES
    // =========================================================================

    'missing_information' => [
        'Noms de famille de tous les joueurs (seuls les pseudos sont connus)',
        'Numéros de carte FGB de tous les joueurs',
        'Clubs de chaque joueur inscrit',
        'Montant de l\'inscription (registration_fee)',
        'Opérateur Mobile Money exact (Airtel Money / Moov / autre)',
        'Identité du joueur X2 (Groupe F)',
        'Identité du joueur X3 (Groupe G)',
        'Identité du joueur X4 (Groupe H)',
    ],

];
