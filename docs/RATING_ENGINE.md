# Rating Engine — Club 8 Pool

## 1. Introduction

Club 8 Pool uses a FargoRate-inspired Elo rating system to track player skill over time. The system is simpler than true FargoRate (which uses a larger, centralised dataset and a more complex normalisation process) but follows the same core principles: expected score, actual score, and a dynamic K-factor.

Ratings are calculated per-player across all competitions they participate in. A player's rating reflects their cumulative record, not their performance in a single event.

---

## 2. Initial Rating

- Every new player starts with a rating of **1500**.
- All players start with `provisional = true`.
- Provisional status is lifted after **20 rated matches** (see section 8).

---

## 3. Expected Score Formula

The expected score for player A against player B is:

```
E_A = 1 / (1 + 10^((R_B - R_A) / 400))
```

Where `R_A` and `R_B` are the current ratings of player A and B respectively.

This is the standard Elo formula. A player rated 400 points above their opponent has a 90.9% expected win probability.

---

## 4. Actual Score

| Result for A | Actual score (S_A) |
|---|---|
| Win | 1.0 |
| Loss | 0.0 |
| Draw (if format allows) | 0.5 |

Draws are rare in billiard formats but are supported in the model.

---

## 5. K-Factor

The K-factor controls how much a single match can move a player's rating.

| Condition | K |
|---|---|
| Player is provisional (`provisional = true`) | 40 |
| Player has played fewer than 20 rated matches | 40 |
| Player has played 20–100 rated matches | 24 |
| Player has played more than 100 rated matches | 16 |

A higher K means ratings respond faster to new results. The floor of 16 ensures established players' ratings remain stable.

---

## 6. Margin Factor

The margin factor amplifies or dampens the rating change based on the frame score differential. A convincing 5–0 victory should move the rating more than a narrow 3–2 win in a race-to-3.

```
frameDiff   = abs(winner_frames - loser_frames)
marginFactor = min(1.0 + (frameDiff * 0.15), 1.75)
```

| Frame diff | Margin factor |
|---|---|
| 0 (draw) | 1.00 |
| 1 | 1.15 |
| 2 | 1.30 |
| 3 | 1.45 |
| 4 | 1.60 |
| 5+ | 1.75 (cap) |

---

## 7. Update Formula

```
ΔR = round(K × marginFactor × (S_A - E_A))
```

The delta is rounded to the nearest integer. Player A's new rating:

```
R_A_new = R_A + ΔR
R_B_new = R_B - ΔR
```

The sum of rating points in the system is conserved (zero-sum).

### Example

Player A (R=1600) beats Player B (R=1520) 3–1 in a race-to-3.

```
E_A = 1 / (1 + 10^((1520 - 1600) / 400))
    = 1 / (1 + 10^(-0.2))
    = 1 / (1 + 0.631)
    = 0.613

K = 24 (both established players)
frameDiff = 3 - 1 = 2
marginFactor = 1 + (2 × 0.15) = 1.30

ΔR = round(24 × 1.30 × (1.0 - 0.613))
   = round(24 × 1.30 × 0.387)
   = round(12.07)
   = 12

R_A_new = 1600 + 12 = 1612
R_B_new = 1520 - 12 = 1508
```

---

## 8. Provisional Status

A player is `provisional = true` until they have accumulated **20 rated matches**.

Provisional players:
- Use K=40 regardless of game count.
- Are displayed with a `(P)` indicator in rating lists.
- Are excluded from ranking-based seeding in tournament draws until established.

The provisional flag is updated automatically by `PlayerRatingService::applyMatchResult()` after each match.

---

## 9. Idempotency

Each match produces exactly **one `RatingEvent` record** (keyed by `match_id + player_id`). If `applyMatchResult()` is called twice for the same match:

1. The service checks whether a `RatingEvent` already exists for this match + player pair.
2. If it exists, the call is a no-op — no second delta is applied.

This makes the `c8p:recalculate-ratings` command safe to run multiple times. It rolls back existing events for the competition and replays them from chronological order.

---

## 10. Recalculation Command

```bash
# Recalculate ratings for all in_progress and finished competitions
php artisan c8p:recalculate-ratings

# Recalculate for one competition (by ID or slug)
php artisan c8p:recalculate-ratings 42
php artisan c8p:recalculate-ratings nationale-2025
```

The command:
1. Loads all completed matches in chronological order (`played_at ASC`).
2. Deletes existing `RatingEvent` rows for the scope.
3. Replays each match, applying deltas in order.

This ensures ratings are consistent even if match results were edited retroactively.

---

## 11. Database Tables

### `player_ratings`

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `player_id` | bigint | Foreign key → `players.id` |
| `rating` | int | Current Elo rating |
| `games_played` | int | Total rated matches |
| `provisional` | boolean | True until 20 games |
| `updated_at` | timestamp | Last recalculation |

### `rating_events`

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `player_id` | bigint | Foreign key → `players.id` |
| `match_id` | bigint | Foreign key → `game_matches.id` |
| `rating_before` | int | Rating before this match |
| `rating_after` | int | Rating after this match |
| `delta` | int | Change applied (signed) |
| `k_factor` | int | K used for this event |
| `margin_factor` | decimal(4,2) | Margin factor used |
| `created_at` | timestamp | When the event was written |

The `(match_id, player_id)` pair has a unique index to enforce idempotency.

---

## 12. Limitations and Future Improvements

**Current limitations:**

- Ratings are computed within the Club 8 Pool system only. They are not portable to external FargoRate or World Standardised Rating systems.
- The K-factor schedule is simplified. True FargoRate uses a continuous "robustness" parameter derived from the full match history.
- Margin factor is linear. FargoRate's equivalent is more nuanced and accounts for match length (race-to-3 vs race-to-7 have different expected variance).
- No floor or ceiling on ratings. A theoretical floor of ~800 could be added to prevent very long losing streaks producing non-sensical values.
- No confidence intervals are displayed. FargoRate shows a "robustness" band; our system only shows a point estimate.

**Planned improvements:**

- Cross-competition rating continuity (currently ratings accumulate correctly but the UI only shows per-competition snapshots).
- Decay factor for inactivity (no rating change for 12+ months).
- Import of historical match data to bootstrap accurate starting ratings.
