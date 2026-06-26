# Changelog — Club 8 Pool

All notable changes to this project will be documented in this file.
Format inspired by [Keep a Changelog](https://keepachangelog.com/).

## [0.9.0-rc1] — 2026-06-26 — Club 8 Pool International Standards

### Security
- Player PII (phone, email, address, birthdate) hidden in all public API responses
- Referee login rate-limited to 5 attempts/minute
- All API endpoints protected by Laravel Sanctum
- Referee event recording restricted to assigned referee
- Admin actions protected by Policy authorization layer
- Upload file size and type validation enforced

### Added
- **Rating engine**: Elo-based rating system (K=40/24/16, margin factor ×1.75 max)
- **SeedingService**: random / rating / manual / hybrid bracket draw strategies
- **AuditLogService**: append-only audit trail for match closes, score corrections, claims
- **MatchStatisticsService**: per-player event aggregation per competition
- **BracketProgression**: atomic, idempotent winner advancement with lockForUpdate
- **Admin — Classement Elo**: `/admin/classement` — ranking by discipline, provisional badge
- **Admin — Statistiques**: `/admin/competitions/{id}/stats` — frames, matches, events, stale flag
- **Referee Live**: config-driven shot clock (30s/15s from competition settings)
- **Referee Live**: pro event panel (foul, safety, miss, break-and-run, extension, violation)
- **Public**: player profile shows Elo rating with provisional/confirmed badge
- **API mobile v2**: backward-compatible new fields (shot_clock_config, rack_mode, player_rating_summary, allowed_events)
- Artisan commands: c8p:recalculate-ratings, c8p:recalculate-statistics, c8p:sync-frames, c8p:health-check
- FormRequest validation on all sensitive endpoints (12 request classes)
- Policies: Competition, Match, Player, Registration, MatchIncident

### Fixed
- **CRITICAL**: PlayerRatingService::applyMatchResult() called statically in KnockoutController (was fatal error)
- **CRITICAL**: Rating idempotency check moved inside transaction after lockForUpdate (TOCTOU race condition)
- Knockout closeMatch now rejects equal scores (no draw in single elimination)
- event_type validated against allowed enum (was open string)
- PublicRegistrationRequest wired in RegisterController (was inline validate)
- AuthorizesRequests trait added to base Controller

### Database
- 7 new migrations: audit_logs, player_ratings, rating_events, match_events, player_competition_statistics, match_incidents, match_tiebreaks
- Seeding config columns on competitions (seed_strategy, seeded_players_count, draw_randomize_unseeded)
- Shot clock config columns on competitions (shot_clock_enabled, shot_clock, shot_clock_late_seconds, etc.)
- 8 composite performance indexes

### Tests
- 56 tests, 153+ assertions
- TournamentEndToEndTest: full lifecycle, bracket chain, claim race, rating idempotency
- PlayerRatingServiceTest: 8 unit tests
- BracketProgressionTest, PoolStandingTest, SeedingServiceTest
- Security: AdminAuthorizationTest, RefereeLoginRateLimitTest, UploadValidationTest
- API: RefereeApiAuthTest

### Documentation
- docs/SECURITY.md, docs/TESTING.md, docs/RATING_ENGINE.md
- docs/TOURNAMENT_RUNBOOK.md (updated with shot clock, ratings)
- docs/API_REFEREE.md (new), docs/STATS_ENGINE.md (new)
- docs/DEPLOYMENT_HOSTINGER.md (new), docs/QA_TERRAIN_JOUR_J.md (new)

---

## [0.1.0] — 2026-05-24 — Initial release

- Competition management (draft → registration → in_progress → finished)
- Pool phase with round-robin scheduling
- Knockout bracket generator (single elimination R32→F)
- Referee mobile interface (claim, start, frame, undo, end, signature)
- Public pages: landing, competition, players, live scores, TV mode
- Admin: competitions, players, pools, draw, import, referees
- Authentication: admin web + referee web + referee API (Sanctum)
