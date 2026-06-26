# Match Statistics Engine

## Tables

### `match_events`

Append-only event log. One row per recorded event during a match (foul, safety, break-and-run, etc.). Records are never updated or deleted — corrections are handled by appending a compensating event.

Key columns: `match_id`, `event_type`, `player_id`, `created_at`, `note`.

### `player_competition_statistics`

Aggregated stats per player per competition. Derived from `match_events` and match results. Rebuilt on demand via the recalculation command.

Key columns: `player_id`, `competition_id`, `frames_won`, `frames_lost`, `matches_played`, `matches_won`, `foul_count`, `safety_count`, `break_and_run_count`, `miss_count`, `is_stale`.

---

## Recalculation

```bash
# Recalculate stats for all competitions
php artisan c8p:recalculate-statistics

# Recalculate for a specific competition
php artisan c8p:recalculate-statistics nationale-2025
```

The command truncates `player_competition_statistics` for the target competition(s) and rebuilds from `match_events` and match results. Safe to run at any time — idempotent.

---

## Stats tracked

| Column | Description |
|--------|-------------|
| `frames_won` | Total frames won across all matches in the competition |
| `frames_lost` | Total frames lost |
| `matches_played` | Total matches played (status = `done`) |
| `matches_won` | Matches won |
| `foul_count` | Total foul events recorded |
| `safety_count` | Total safety shots recorded |
| `break_and_run_count` | Total break-and-run events |
| `miss_count` | Total miss calls by the referee |

---

## `is_stale` flag

Set to `true` automatically when a match score is corrected after it was closed (via Admin → Match → Override Result).

The admin statistics panel shows a warning badge next to stale rows. To clear the flag and rebuild accurate numbers, run the recalculation command for the affected competition.
