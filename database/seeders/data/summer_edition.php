<?php

/**
 * Summer Edition — Configuration déclarative de l'événement.
 *
 * Source : Communiqué officiel MRTECH / Organisation.
 * Utilisé par : database/seeders/SummerEditionSeeder.php
 *
 * Ce fichier est la source de vérité pour toutes les données connues.
 * Les champs null nécessitent une confirmation de l'organisation.
 */

return [

    // =========================================================================
    // COMPÉTITION
    // =========================================================================

    'competition' => [
        'name'                    => 'Summer Edition',
        'slug'                    => 'summer-edition',
        'discipline'              => '8-ball',      // TODO: confirmer 8-ball vs 9-ball
        'format'                  => 'pools',
        'structure'               => 'pools_knockout',
        'status'                  => 'draft',
        'player_slots'            => 48,
        'pool_count'              => 8,
        'pool_size'               => 6,
        'qualifiers_per_pool'     => 4,
        'seed_strategy'           => 'manual',
        'seeded_players_count'    => 8,
        'draw_randomize_unseeded' => true,
        'race_to'                 => 7,    // fallback global
        'pool_race_to'            => 4,
        'knockout_race_to'        => 7,    // fallback pour rounds non configurés
        'knockout_mapping_strategy' => 'pool_cross_ac_bd_eg_fh',
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
        'shot_clock'                       => 30,       // secondes par tir (poules)
        'shot_clock_late_seconds'          => 30,       // TODO: confirmer en phase finale
        'shot_clock_late_rule'             => 'never',
        'shot_clock_extensions_per_player' => 0,        // TODO: confirmer nb d'extensions
        'tie_break_mode'                   => 'shootout', // TODO: confirmer règle ex-aequo
        'rack_mode'                        => 'template',
        'alternate_break'                  => true,     // TODO: confirmer break alterné
        'allow_draw'                       => false,
        'enable_warnings'                  => true,
        'push_out'                         => false,
        'push_out_enabled'                 => false,
    ],

    // =========================================================================
    // RACE-TO PAR TOUR (phase finale)
    // Stocké dans settings['round_race_to'] sur la compétition.
    // =========================================================================

    'round_race_to' => [
        'R32' => 7,   // 1/16e de finale
        'R16' => 7,   // 1/8e de finale
        'QF'  => 9,   // 1/4 de finale
        'SF'  => 9,   // 1/2 finale
        '3P'  => 5,   // Petite finale
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
    // POULES
    // =========================================================================

    'pools' => [
        ['name' => 'A', 'position' => 0, 'size' => 6],
        ['name' => 'B', 'position' => 1, 'size' => 6],
        ['name' => 'C', 'position' => 2, 'size' => 6],
        ['name' => 'D', 'position' => 3, 'size' => 6],
        ['name' => 'E', 'position' => 4, 'size' => 6],
        ['name' => 'F', 'position' => 5, 'size' => 6],
        ['name' => 'G', 'position' => 6, 'size' => 6],
        ['name' => 'H', 'position' => 7, 'size' => 6],
    ],

    // =========================================================================
    // TÊTES DE SÉRIE (liste officielle fournie par l'organisation)
    // ⚠️  Noms partiels — à compléter avec noms de famille / FGB avant jour J.
    // =========================================================================

    'top_seeds' => [
        ['seed' => 1, 'display_name' => 'Amauris', 'pool' => 'A'],
        ['seed' => 2, 'display_name' => 'Paolo',   'pool' => 'B'],
        ['seed' => 3, 'display_name' => 'Zouzou',  'pool' => 'C'],
        ['seed' => 4, 'display_name' => 'Zack',    'pool' => 'D'],
        ['seed' => 5, 'display_name' => 'Youssef', 'pool' => 'E'],
        ['seed' => 6, 'display_name' => 'Mohamed', 'pool' => 'F'],
        ['seed' => 7, 'display_name' => 'Bobby',   'pool' => 'G'],
        ['seed' => 8, 'display_name' => 'Toto',    'pool' => 'H'],
    ],

    // =========================================================================
    // DOTATION
    // Montants issus du communiqué officiel (source : MRTECH).
    // null = information non communiquée.
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
    // CALENDRIER
    // Dates juillet 2026 confirmées par les jours de semaine du communiqué.
    // Horaires exacts non communiqués : starts_at / ends_at à null.
    // =========================================================================

    'schedule' => [
        'timezone'              => 'Africa/Libreville',
        'registration_deadline' => null,   // TODO: à confirmer
        'days' => [
            [
                'date'  => '2026-07-04',
                'label' => 'Samedi 4 juillet 2026',
                'items' => [
                    [
                        'phase'      => 'pools',
                        'label'      => 'Phase de poules — Poules A, B, C',
                        'pool_range' => ['A', 'C'],
                        'starts_at'  => null,
                        'ends_at'    => null,
                    ],
                ],
            ],
            [
                'date'  => '2026-07-05',
                'label' => 'Dimanche 5 juillet 2026',
                'items' => [
                    [
                        'phase'      => 'pools',
                        'label'      => 'Phase de poules — Poules D, E, F',
                        'pool_range' => ['D', 'F'],
                        'starts_at'  => null,
                        'ends_at'    => null,
                    ],
                ],
            ],
            [
                'date'  => '2026-07-06',
                'label' => 'Lundi 6 juillet 2026',
                'items' => [
                    [
                        'phase'      => 'pools',
                        'label'      => 'Phase de poules — Poules G, H',
                        'pool_range' => ['G', 'H'],
                        'starts_at'  => null,
                        'ends_at'    => null,
                    ],
                ],
            ],
            [
                'date'  => '2026-07-07',
                'label' => 'Mardi 7 juillet 2026',
                'items' => [
                    [
                        'phase'     => 'knockout',
                        'label'     => '1/16e de finale (R32)',
                        'rounds'    => ['R32'],
                        'starts_at' => null,
                        'ends_at'   => null,
                    ],
                ],
            ],
            [
                'date'  => '2026-07-08',
                'label' => 'Mercredi 8 juillet 2026',
                'items' => [
                    [
                        'phase'     => 'knockout',
                        'label'     => '1/8e de finale (R16)',
                        'rounds'    => ['R16'],
                        'starts_at' => null,
                        'ends_at'   => null,
                    ],
                ],
            ],
            [
                'date'  => '2026-07-12',
                'label' => 'Samedi 12 juillet 2026',
                'items' => [
                    [
                        'phase'     => 'knockout',
                        'label'     => 'Final 8 — QF / SF / Petite finale / Finale',
                        'rounds'    => ['QF', 'SF', '3P', 'F'],
                        'starts_at' => null,
                        'ends_at'   => null,
                    ],
                ],
            ],
        ],
    ],

    // =========================================================================
    // INSCRIPTION / PAIEMENT
    // Informations issues du communiqué (contact Dimitri).
    // null = non communiqué.
    // =========================================================================

    'payment' => [
        'registration_fee' => null,   // montant inscription non communiqué
        'currency'         => 'XAF',
        'methods'          => [
            [
                'type'         => 'mobile_money',
                'provider'     => null,   // opérateur non précisé
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
    // INFORMATIONS MANQUANTES — À COLLECTER AVANT LE JOUR J
    // =========================================================================

    'missing_information' => [
        'Noms de famille des 8 têtes de série (seuls les prénoms/pseudos sont connus)',
        'Numéros de carte FGB des 8 têtes de série',
        'Liste des 40 autres joueurs inscrits (noms, FGB, clubs)',
        'Clubs de chaque joueur inscrit',
        'Confirmation de la discipline (8-ball / 9-ball / 10-ball)',
        'Format de break : alterné ou fixe ?',
        'Push-out rule activée ou non ?',
        'Règle de shot clock en phase finale (30s comme en poules ?)',
        "Nombre d'extensions de shot clock autorisées (0 ou 1 ?)",
        "Règle d'ex-aequo en poule (shootout / race_to_one / points particuliers ?)",
        "Montant de l'inscription (registration_fee)",
        'Opérateur Mobile Money exact (Airtel Money / Moov / autre)',
        "Contacts complets de l'organisation (email, autres responsables)",
        'Horaires exacts de chaque phase (par jour)',
        'Date et heure exactes de clôture des inscriptions',
        'Entrée du public : gratuite ou payante ?',
    ],

];
