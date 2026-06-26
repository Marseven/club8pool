<?php

/**
 * Summer Edition — Configuration déclarative de l'événement.
 *
 * Source : Communiqué officiel MRTECH / Organisation.
 * Utilisé par : database/seeders/SummerEditionSeeder.php
 *
 * Ce fichier est la source de vérité pour toutes les données connues.
 * Les champs marqués TODO nécessitent une confirmation de l'organisation.
 */

return [

    // =========================================================================
    // COMPÉTITION
    // =========================================================================

    'competition' => [
        'name'                   => 'Summer Edition',
        'slug'                   => 'summer-edition',
        'discipline'             => '8-ball',      // TODO: confirmer 8-ball vs 9-ball
        'format'                 => 'pools',
        'structure'              => 'pools_knockout',
        'status'                 => 'draft',
        'player_slots'           => 48,
        'pool_count'             => 8,
        'pool_size'              => 6,
        'qualifiers_per_pool'    => 4,
        'seed_strategy'          => 'manual',
        'seeded_players_count'   => 8,
        'draw_randomize_unseeded'=> true,
        'race_to'                => 7,             // fallback global
        'pool_race_to'           => 4,
        'knockout_race_to'       => 7,             // fallback pour rounds non configurés
        'prize_pool'             => 1120000,
        'venue'                  => "L'Icône",
        'city'                   => 'Libreville',
        'starts_on'              => '2026-07-04',
        'ends_on'                => '2026-07-12',
    ],

    // =========================================================================
    // FORMAT ET RÈGLES
    // =========================================================================

    'format' => [
        'shot_clock_enabled'             => true,
        'shot_clock'                     => 30,    // secondes par tir (poules)
        'shot_clock_late_seconds'        => 30,    // TODO: confirmer (la même en phase finale ?)
        'shot_clock_late_rule'           => 'never',
        'shot_clock_extensions_per_player' => 0,  // TODO: confirmer nb d'extensions
        'tie_break_mode'                 => 'shootout', // TODO: confirmer règle ex-aequo poule
        'rack_mode'                      => 'template',
        'alternate_break'                => true,  // TODO: confirmer break alterné
        'allow_draw'                     => false,
        'enable_warnings'                => true,
        'push_out'                       => false,
        'push_out_enabled'               => false,
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
    // ⚠️  Noms partiels — à compléter avec prénoms/FGB avant jour J.
    // =========================================================================

    'top_seeds' => [
        ['seed' => 1, 'display_name' => 'Amauris',  'pool' => 'A'],
        ['seed' => 2, 'display_name' => 'Paolo',    'pool' => 'B'],
        ['seed' => 3, 'display_name' => 'Zouzou',   'pool' => 'C'],
        ['seed' => 4, 'display_name' => 'Zack',     'pool' => 'D'],
        ['seed' => 5, 'display_name' => 'Youssef',  'pool' => 'E'],
        ['seed' => 6, 'display_name' => 'Mohamed',  'pool' => 'F'],
        ['seed' => 7, 'display_name' => 'Bobby',    'pool' => 'G'],
        ['seed' => 8, 'display_name' => 'Toto',     'pool' => 'H'],
    ],

    // =========================================================================
    // CALENDRIER
    // Dates juillet 2026 confirmées par les jours de semaine du communiqué.
    // =========================================================================

    'schedule' => [
        [
            'label'      => 'Poules A, B, C',
            'phase'      => 'pools',
            'pools'      => ['A', 'B', 'C'],
            'date'       => '2026-07-04',
            'starts_at'  => '13:00',
            'ends_at'    => '2026-07-05 01:00',
            'tables'     => 2,
        ],
        [
            'label'      => 'Poules D, E, F',
            'phase'      => 'pools',
            'pools'      => ['D', 'E', 'F'],
            'date'       => '2026-07-05',
            'starts_at'  => '13:00',
            'ends_at'    => '2026-07-06 00:00',
            'tables'     => 2,
        ],
        [
            'label'      => 'Poules G, H',
            'phase'      => 'pools',
            'pools'      => ['G', 'H'],
            'date'       => '2026-07-06',
            'starts_at'  => '18:00',
            'ends_at'    => '2026-07-07 01:00',
            'tables'     => 2,
        ],
        [
            'label'      => '1/16e de finale',
            'phase'      => 'knockout',
            'round'      => 'R32',
            'date'       => '2026-07-07',
            'starts_at'  => '18:00',
            'ends_at'    => '2026-07-08 01:00',
            'tables'     => 2,
        ],
        [
            'label'      => '1/8e de finale',
            'phase'      => 'knockout',
            'round'      => 'R16',
            'date'       => '2026-07-08',
            'starts_at'  => '18:00',
            'ends_at'    => '2026-07-09 01:00',
            'tables'     => 2,
        ],
        [
            'label'      => 'Final 8 — QF / SF / Petite finale / Finale',
            'phase'      => 'knockout',
            'rounds'     => ['QF', 'SF', '3P', 'F'],
            'date'       => '2026-07-12',
            'starts_at'  => '14:00',
            'ends_at'    => '2026-07-13 00:00',
            'tables'     => 2,
        ],
    ],

    // =========================================================================
    // DOTATION
    // =========================================================================

    'prizes' => [
        'total'    => 1120000,
        'currency' => 'XAF',
        'breakdown' => [
            ['rank' => 1,   'amount' => 500000, 'extras' => ['Trophée', 'Médaille Or']],
            ['rank' => 2,   'amount' => 250000, 'extras' => ['Médaille Argent']],
            ['rank' => 3,   'amount' => 150000, 'extras' => ['Médaille Bronze']],
            ['rank' => 4,   'amount' => 100000, 'extras' => ['Médaille']],
            ['rank' => '5-8', 'amount_each' => 30000, 'players' => 4, 'total' => 120000],
        ],
    ],

    // =========================================================================
    // INSCRIPTION / PAIEMENT
    // =========================================================================

    'payment' => [
        'method'       => 'Mobile Money',
        'contact_name' => 'Dimitri',
        'phone'        => '077 79 10 57',
    ],

    // =========================================================================
    // INFORMATIONS MANQUANTES — À COLLECTER AVANT LE JOUR J
    // =========================================================================

    'missing_information' => [
        'Noms de famille des 8 têtes de série (seuls les prénoms/pseudos sont connus)',
        'Numéros de carte FGB des 8 têtes de série',
        'Liste des 40 autres joueurs inscrits (noms, FGB, clubs)',
        'Confirmation de la discipline (8-ball / 9-ball / 10-ball)',
        'Règle de shot clock en phase finale (30s comme en poules ?)',
        'Nombre d\'extensions de shot clock autorisées (0 ou 1 ?)',
        'Règle d\'ex-aequo en poule (shootout / race_to_one / points particuliers ?)',
        'Break alterné ou break fixe ?',
        'Push-out rule activée ou non ?',
        'Entrée du public : gratuite ou payante ?',
        'Date exacte de clôture des inscriptions',
    ],

];
