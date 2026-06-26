# Tournament Operations Runbook — Club 8 Pool

This document covers the full lifecycle of a Club 8 Pool tournament from initial setup to post-event closeout. It is intended for tournament directors and technical staff.

---

## 1. Pre-Competition Setup Timeline

### T-7 days
- [ ] Create the competition in the admin panel (see section 2).
- [ ] Confirm format: pool phase + knockout, knockout only, or round robin.
- [ ] Set the competition slug (used in public URLs and Artisan commands).
- [ ] Assign an organizer and tournament director.

### T-3 days
- [ ] Import the player list via Excel (see section 3).
- [ ] Review imported players — check for duplicates, missing surnames.
- [ ] Set seeding values if required for the draw.

### T-1 day
- [ ] Run the draw (see section 4).
- [ ] Assign referees to tables (see section 7).
- [ ] Distribute referee PIN credentials.
- [ ] Run `php artisan c8p:health-check` and resolve any failures.
- [ ] Verify TV scoreboard displays correctly (see section 9).

### T-0 (day of event)
- [ ] Run `php artisan c8p:health-check` one final time.
- [ ] Confirm all referees have logged in to the mobile app successfully.
- [ ] Confirm the public live page loads.
- [ ] Brief referees on the incident procedure (see section 8).

---

## 2. Creating a Competition

Navigate to **Admin → Competitions → New**.

| Field | Description | Example |
|---|---|---|
| Name | Display name | Nationale Open 2025 |
| Slug | URL-safe identifier (auto-generated, editable) | nationale-open-2025 |
| Format | `pool_then_knockout`, `knockout_only`, `round_robin` | pool_then_knockout |
| Start date | Date of first match | 2025-11-15 |
| Venue | Location name | Salle des fêtes, Lyon |
| Status | `draft`, `in_progress`, `finished` | draft |
| Pool size | Players per pool (pool phase only) | 4 or 5 |
| Race to | Frames per match in pool phase | 3 |
| Knockout race to | Frames per match in knockout phase | 5 (final: 7) |
| Advance per pool | How many qualify to knockout | 2 |
| Seeding method | `manual`, `rating`, `random` | rating |

Set status to `draft` while preparing. Change to `in_progress` only when you are ready for scoring to begin — this triggers the public live view.

---

## 3. Player Import via Excel

**Template:** Download the import template from Admin → Players → Import → Download Template.

Required columns:

| Column | Required | Format |
|---|---|---|
| `first_name` | Yes | Text |
| `last_name` | Yes | Text |
| `club` | No | Text |
| `license_number` | No | Text |
| `rating` | No | Integer (overrides system rating if provided) |
| `seed` | No | Integer (used for draw seeding) |
| `phone` | No | +33XXXXXXXXX |

**Import process:**
1. Admin → Competitions → [Competition] → Players → Import Excel.
2. Upload the `.xlsx` file.
3. Review the preview — the system highlights duplicate names and missing required fields.
4. Confirm import.

**Notes:**
- Players are created if they do not already exist (matched by `license_number` if provided, otherwise by `first_name + last_name`).
- Duplicate detection is case-insensitive.
- The import is wrapped in a database transaction — a validation failure rolls back the entire batch.

---

## 4. Draw and Seeding Strategies

### Seeding methods

**Rating-based (`rating`):**
Players are sorted by their current Elo rating descending. The top seed is placed in Pool A, the next in Pool B, etc., cycling through pools. Provisional players are treated as 1500 unless they have an imported rating.

**Manual (`manual`):**
Assign seed numbers in the import sheet. The draw respects these seeds. Players without a seed number are placed randomly after seeded players.

**Random (`random`):**
All players are placed randomly regardless of rating or seed.

### Running the draw

1. Admin → Competitions → [Competition] → Draw → Generate.
2. Review the generated pools.
3. If a correction is needed (e.g., two players from the same club are in the same pool), use the **swap** function to exchange two players between pools.
4. Click **Confirm Draw** — this locks the pools and generates the match schedule.

The draw cannot be re-generated once confirmed. If a major error occurs, contact an admin to reset the draw (this also deletes all match records for the competition).

---

## 5. Pool Phase Management

Once the draw is confirmed and the competition is `in_progress`, referees score matches from their tablet.

### Standings rules (in priority order)

1. Points (win = 2 pts, loss = 0, walkover = 1)
2. Head-to-head result between tied players
3. Frame differential (frames won minus frames lost)
4. Frames won
5. Random draw (if still tied)

### Advancing to knockout

When all pool matches are complete, go to Admin → Competitions → [Competition] → Advance to Knockout. The system:

1. Reads standings for each pool.
2. Takes the top N players (configured in `advance_per_pool`).
3. Seeds the knockout bracket (pool winners seeded above pool runners-up, protecting them from meeting until the final if possible).
4. Generates the knockout bracket.

Confirm the bracket before proceeding — the same no-undo rule applies.

---

## 6. Knockout Bracket Generation

The bracket is single-elimination. Byes are inserted if the number of qualifiers is not a power of 2.

- 8 qualifiers → 8-player bracket, no byes.
- 6 qualifiers → 8-player bracket, 2 byes (top 2 seeds get a bye in R1).
- 12 qualifiers → 16-player bracket, 4 byes.

Byes are assigned to the highest seeds first. A bye counts as a walkover win for the seeded player.

### Bronze match

A third-place match is optional and configurable per competition. Enable it before confirming the bracket.

---

## 7. Referee Assignment and Mobile App Setup

### Assigning referees to tables

Admin → Competitions → [Competition] → Referees → Assign.

Drag-and-drop referees from the available list to table slots. One referee per table per session. A referee can be reassigned mid-event by a head referee or tournament director.

### Referee onboarding (day of event)

1. Give the referee their PIN on a physical slip (do not send via SMS or email in plain text — or ensure the PIN is immediately changed).
2. Referee opens the app URL on their tablet browser.
3. They select their name from the list and enter their PIN.
4. On success, the app shows their assigned matches.

### PIN reset

If a referee forgets their PIN:
- Admin → Referees → [Referee] → Reset PIN.
- A new PIN is generated. Show the new PIN to the referee in person.

---

## 8. Handling Incidents

### Who can do what

| Incident | Who resolves |
|---|---|
| Incorrect frame entry (catch it immediately) | Referee — delete the last frame entry from the match screen |
| Incorrect frame entry (after match closed) | Head referee or tournament director — use Admin → Match → Override |
| Player dispute / protest | Head referee or tournament director |
| Referee replacement mid-match | Head referee or tournament director — reassign via admin panel |
| Walkover (player no-show) | Tournament director — mark as walkover from match detail |
| Technical issue with tablet | Referee continues on paper; tournament director enters frames manually after the match |

### Overriding a completed match result

1. Admin → Competitions → [Competition] → Matches → [Match].
2. Click **Override Result**.
3. Enter the correct frame-by-frame breakdown.
4. Add a note explaining the reason (required — written to audit log).
5. Confirm. Standings and bracket are recalculated automatically.

---

## 9. TV Scoreboard Setup

The public scoreboard is available at:

```
/live/{competition-slug}
```

This page auto-refreshes every 10 seconds via polling (no additional setup required). For TV display:

1. Open the URL in Chrome on the TV computer.
2. Press F11 for full screen.
3. The page adapts automatically to 1080p and 4K resolutions.
4. During pool phase: pool standings cards are shown.
5. During knockout phase: the bracket card is shown.

No login is required. The live page is public and read-only.

### Customising the display

To show only specific tables (for multi-screen setups), append a query parameter:

```
/live/nationale-2025?tables=1,2,3
```

---

## 10. Post-Competition

### Export results

Admin → Competitions → [Competition] → Export.

Available exports:
- **Full results** (XLSX): all match scores, standings, bracket results.
- **Player statistics** (XLSX): frames won/lost, win rate per player.
- **Rating changes** (XLSX): before/after Elo ratings for all participants.

### Verify and commit ratings

Run the ratings recalculation to ensure all events are consistent:

```bash
php artisan c8p:recalculate-ratings nationale-2025
```

Then sync statistics:

```bash
php artisan c8p:recalculate-statistics nationale-2025
```

### Check frame consistency

Before closing the competition:

```bash
# Dry run — see any discrepancies without fixing
php artisan c8p:sync-frames nationale-2025 --dry-run

# Fix discrepancies (frames table is source of truth)
php artisan c8p:sync-frames nationale-2025
```

### Close the competition

Admin → Competitions → [Competition] → Mark as Finished.

This sets `status = finished` and freezes the competition — no further scoring is possible without an admin override.

### Revoke referee tokens

After the event, revoke all Sanctum tokens for referees to prevent unauthorised post-event access:

```sql
-- Revoke tokens for all referees (run in tinker or via admin panel)
DELETE FROM personal_access_tokens WHERE tokenable_type = 'App\Models\Referee';
```

Or via Tinker:

```bash
php artisan tinker
>>> \App\Models\Referee::all()->each->tokens()->each->delete();
```

---

## 11. Deployment Checklist

Run this checklist for every production deployment.

```bash
# 1. Pull latest code
git pull origin main

# 2. Install PHP dependencies (no dev packages)
composer install --no-dev --optimize-autoloader

# 3. Run database migrations
php artisan migrate --force

# 4. Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Build frontend assets
npm ci
npm run build

# 6. Ensure storage symlink exists
php artisan storage:link

# 7. Set correct permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 8. Run health check
php artisan c8p:health-check
```

If `c8p:health-check` exits with a non-zero code, do not proceed with the competition. Resolve all reported issues first.

### Rolling back a failed migration

```bash
php artisan migrate:rollback --step=1
```

Always test migrations on a staging environment before running `--force` in production.
