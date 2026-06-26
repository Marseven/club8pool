# API Arbitre Mobile — Référence

Base URL: `/api/referee/`

## Authentication

**Login**

```
POST /api/referee/login
Content-Type: application/json

{ "pin": "1234", "referee_id": 5 }
```

Returns a Sanctum bearer token:

```json
{ "token": "1|abc123..." }
```

All other endpoints require the token in the `Authorization` header:

```
Authorization: Bearer 1|abc123...
```

---

## Endpoints

### Identity

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/referee/me` | Returns the authenticated referee's profile and assigned tables |

### Match queue

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/referee/queue` | Returns the list of matches pending referee action (scheduled or live), enriched with `shot_clock_config`, rack/tie-break settings, and `player_rating_summary` |
| `GET` | `/referee/available` | Returns matches not yet claimed by any referee |
| `GET` | `/referee/tables` | Returns the list of tables with their current match status |

### Match detail and scoring

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/referee/matches/{match}` | Full match detail: players, score, frame list, events, shot clock config, allowed events |
| `POST` | `/referee/matches/{match}/claim` | Referee claims an available match |
| `POST` | `/referee/matches/{match}/table` | Assign or change the table for a match |
| `POST` | `/referee/matches/{match}/start` | Mark the match as live (starts the clock) |
| `POST` | `/referee/matches/{match}/frame` | Record a frame result |
| `POST` | `/referee/matches/{match}/undo-frame` | Undo the last frame entry (only before match is closed) |
| `POST` | `/referee/matches/{match}/end` | Close the match and lock the final score |
| `POST` | `/referee/matches/{match}/sign` | Referee signs off on the completed match result |

### Officiating actions

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/referee/matches/{match}/warning` | Issue a warning to a player |
| `POST` | `/referee/matches/{match}/events` | Record a match event (see event types below) |
| `POST` | `/referee/matches/{match}/incident` | Create a formal incident report |
| `POST` | `/referee/matches/{match}/tie-break` | Resolve a tie-break situation |

**Total: 17 endpoints** (1 unauthenticated, 16 authenticated)

---

## Events enregistrables (POST /matches/{id}/events)

Request body:

```json
{
  "event_type": "foul",
  "player_id": 12,
  "note": "Optional free-text note"
}
```

Valid `event_type` values:

| Value | Description |
|-------|-------------|
| `foul` | Foul committed by a player |
| `safety` | Safety shot played |
| `warning` | Formal warning issued (also use the dedicated `/warning` endpoint) |
| `miss` | Miss called by referee |
| `break_and_run` | Player ran the rack from the break |
| `shot_clock_extension` | Player used a shot clock extension |
| `shot_clock_violation` | Shot clock expired, foul awarded |
| `re_rack` | Rack restarted (e.g. illegal break) |
| `timeout` | Player called a timeout |
| `coaching_request` | Player requested coaching |
| `other` | Any other notable event (requires `note`) |

---

## Nouveaux champs (v2 — rétrocompatibles)

The `show()` and `queue()` responses now include the following additional fields. Older clients that do not read these fields are unaffected.

### `shot_clock_config`

```json
{
  "enabled": true,
  "seconds": 30,
  "late_seconds": 15,
  "late_rule": "warning",
  "extensions_per_player": 1
}
```

### `rack_mode`, `tie_break_mode`, `push_out_enabled`

```json
{
  "rack_mode": "alternate",
  "tie_break_mode": "lag",
  "push_out_enabled": true
}
```

### `player_rating_summary`

```json
{
  "a": { "rating": 1543, "games_played": 42, "provisional": false },
  "b": { "rating": 1480, "games_played": 8,  "provisional": true  }
}
```

`a` corresponds to `player_a` and `b` to `player_b` in the match payload.

### `allowed_events`

Array of `event_type` strings valid for the current match phase. Use this to conditionally show/hide event buttons in the referee UI.

```json
{
  "allowed_events": ["foul", "safety", "miss", "break_and_run", "shot_clock_extension", "shot_clock_violation", "re_rack", "timeout", "coaching_request", "other"]
}
```
