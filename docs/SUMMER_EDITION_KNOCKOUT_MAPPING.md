# Summer Edition — Mapping KO R32

## Format

8 poules (A–H) × 6 joueurs, 4 qualifiés/poule → **32 qualifiés → R32 (16 matchs)**

Stratégie : `pool_cross_ac_bd_eg_fh` (stockée dans `competitions.knockout_mapping_strategy`)

---

## Tableau officiel

### Moitié haute (positions 0–7)

| Position | Match |
|----------|-------|
| 0 | **A1** vs C4 |
| 1 | **A2** vs C3 |
| 2 | **A3** vs C2 |
| 3 | **A4** vs C1 |
| 4 | **B1** vs D4 |
| 5 | **B2** vs D3 |
| 6 | **B3** vs D2 |
| 7 | **B4** vs D1 |

→ QF moitié haute : vainqueurs A/C vs vainqueurs B/D

### Moitié basse (positions 8–15)

| Position | Match |
|----------|-------|
| 8  | **E1** vs G4 |
| 9  | **E2** vs G3 |
| 10 | **E3** vs G2 |
| 11 | **E4** vs G1 |
| 12 | **F1** vs H4 |
| 13 | **F2** vs H3 |
| 14 | **F3** vs H2 |
| 15 | **F4** vs H1 |

→ QF moitié basse : vainqueurs E/G vs vainqueurs F/H

---

## Principe des duels

Pour chaque paire de poules X/Y : **X(i) vs Y(5-i)**

- 1er de X vs 4e de Y
- 2e de X vs 3e de Y
- 3e de X vs 2e de Y
- 4e de X vs 1e de Y

---

## Race-to par tour

| Tour | Race-to |
|------|---------|
| Poules | 4 |
| R32 | 7 |
| R16 | 7 |
| QF | 9 |
| SF | 9 |
| 3P | 5 |
| F | 11 |

---

## Implémentation

### 1. Colonne de configuration

`competitions.knockout_mapping_strategy = 'pool_cross_ac_bd_eg_fh'`

Définie par migration `2026_07_03_000001` et renseignée dans `SummerEditionSeeder` via `database/seeders/data/summer_edition.php`.

### 2. Service de mapping

`app/Services/PoolKnockoutMappingService.php`

```php
$service = new PoolKnockoutMappingService();
$pairs = $service->buildPairs($qualifiers); // 16 paires ordonnées
```

**Entrée** : tableau `$qualifiers` retourné par `KnockoutGenerator::qualifiers()` —
clés = noms de poules ('A'...'H'), valeurs = tableau des 4 qualifiés classés 1→4.

**Sortie** : 16 paires `[playerA, playerB]` chacune enrichie d'un champ `source` ('A1', 'C4', etc.)

**Erreurs** : lève `InvalidArgumentException` si une poule est absente ou a < 4 qualifiés.

### 3. Dispatch dans KnockoutController

`app/Http/Controllers/Admin/KnockoutController::showCompetition()` détecte la stratégie :

```php
if ($knockoutStrategy === PoolKnockoutMappingService::STRATEGY) {
    $pairs = (new PoolKnockoutMappingService())->buildPairs($qualifiers);
} else {
    // Seeding standard
    $orderedFlat = (new SeedingService())->orderQualifiers($competition, $qualifiers);
    $pairs = $generator->seedPairs($qualifiers, $orderedFlat);
}
```

### 4. Stockage des source labels

`KnockoutGenerator::generate()` stocke `player_a_source` / `player_b_source` sur chaque match R32 :

```sql
SELECT player_a_source, player_b_source FROM matches WHERE round = 'R32' ORDER BY round_position;
-- A1|C4, A2|C3, A3|C2, A4|C1, B1|D4, ...
```

---

## Procédure admin — Génération du bracket

> **Prérequis** : toutes les poules terminées, aucun ex-aequo non résolu.

1. Aller sur `/admin/competitions/{id}/phase-finale`
2. La page affiche les 16 paires calculées automatiquement
3. Vérifier le tableau (source labels visibles : "A1 vs C4", etc.)
4. Cliquer **Générer le bracket**
5. Les 31 matchs (R32 + R16 + QF + SF + F) sont créés en transaction

> La génération KO est une **action admin explicite et irréversible**. Elle supprime les éventuels matchs KO précédents avant de recréer.

---

## Tests

| Fichier | Type | Couvre |
|---------|------|--------|
| `tests/Unit/Services/PoolKnockoutMappingServiceTest.php` | Unit | 16 tests — positions, labels, validation |
| `tests/Feature/Competition/KnockoutR32MappingTest.php` | Feature | Stockage source labels, seeder, intégration complète |
