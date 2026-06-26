# Security Documentation — Club 8 Pool

## 1. Authentication Model

Club 8 Pool uses two distinct authentication pathways depending on the actor.

### Admin authentication (web)
Administrators log in via email and password through the standard Laravel Fortify/Breeze flow. Sessions are managed server-side with CSRF protection on all state-changing routes.

### Referee authentication (PIN-based)
Referees authenticate by selecting their name from a list and entering a numeric PIN. This flow is optimised for tablet use at the table side. The PIN is hashed with `bcrypt` and is never stored in plain text. Referees receive a Sanctum token on successful login; this token is stored in the mobile/tablet client and sent as a Bearer header on all subsequent API calls.

Login endpoint: `POST /api/referee/login`
Throttle: `throttle:5,1` — maximum 5 attempts per minute per IP. Exceeding this returns `HTTP 429 Too Many Requests`.

### API authentication (Sanctum)
All API endpoints (except `/api/referee/login`) require a valid Sanctum Personal Access Token passed as:

```
Authorization: Bearer <token>
```

Tokens are scoped and stored hashed in the `personal_access_tokens` table. The plain-text token is shown only once at issuance.

---

## 2. Rate Limiting

| Endpoint | Middleware | Limit |
|---|---|---|
| `POST /api/referee/login` | `throttle:5,1` | 5 req / 1 min per IP |
| All other API routes | `throttle:60,1` | 60 req / 1 min per token |
| Web admin routes | `throttle:120,1` | 120 req / 1 min per session |

Rate limits are enforced at the route middleware level in `routes/api.php` and `routes/web.php`.

---

## 3. Role Model

Club 8 Pool uses a flat role system stored on the `users` table (`role` column). Roles are not hierarchical in the database — permissions are enforced in Policies and middleware.

| Role | Description |
|---|---|
| `admin` | Full system access. Can manage all entities. |
| `organizer` | Can create and manage their own competitions. |
| `tournament_director` | Oversees a specific competition. Can reassign referees, override results. |
| `head_referee` | Can validate disputed frames, reassign table referees. |
| `referee` | Can score frames on assigned matches only. |

### Role capabilities matrix

| Action | admin | organizer | tournament_director | head_referee | referee |
|---|:---:|:---:|:---:|:---:|:---:|
| Create competition | ✓ | ✓ | — | — | — |
| Edit competition settings | ✓ | own | own | — | — |
| Import players | ✓ | own | own | — | — |
| Run draw | ✓ | own | own | — | — |
| Assign referees | ✓ | own | own | ✓ | — |
| Score a frame | ✓ | — | — | ✓ | assigned |
| Override a frame result | ✓ | — | ✓ | ✓ | — |
| Mark match done | ✓ | — | ✓ | ✓ | assigned |
| View all competitions | ✓ | ✓ | ✓ | ✓ | own |
| Manage users | ✓ | — | — | — | — |
| View audit log | ✓ | own | own | — | — |

---

## 4. Policy-based Authorization

All authorization is expressed through Laravel Policies, not inline `if ($user->role === ...)` checks.

| Policy | Model | Key rules |
|---|---|---|
| `CompetitionPolicy` | `Competition` | `update`, `delete`, `managePlayers`, `runDraw` scoped to owner + admin |
| `MatchPolicy` | `GameMatch` | `scoreFrame` requires assigned referee or head_referee; `override` requires tournament_director+ |
| `PlayerPolicy` | `Player` | `view` public; `create/update/delete` organizer+ |
| `UserPolicy` | `User` | `viewAny`, `update`, `delete` admin only |
| `RatingPolicy` | `PlayerRating` | `recalculate` admin only |

Policies are registered in `App\Providers\AuthServiceProvider`. Every controller action that mutates data calls `$this->authorize(...)` or uses the `can` middleware.

---

## 5. API Security

- All endpoints except `POST /api/referee/login` require `auth:sanctum` middleware.
- The login response returns the token **once**. If a referee loses their token, an admin must revoke and reissue it.
- Token revocation: `DELETE /api/referee/logout` deletes the current token. Admins can revoke all tokens for a user via the admin panel or `personal_access_tokens` table.
- CORS is restricted to trusted origins in `config/cors.php`. Do not add `*` wildcard for API routes.
- Request validation is performed with Form Requests (`app/Http/Requests/`). Unvalidated data is never passed directly to Eloquent mass assignment.
- All models use `$fillable` whitelists. `$guarded = []` is never used.

---

## 6. Sensitive Data Handling

| Data | Storage | Exposure |
|---|---|---|
| Admin passwords | `bcrypt` hash in `users.password` | Never returned by API |
| Referee PINs | `bcrypt` hash in `referees.pin` | Never returned by API |
| Player phone numbers | Plain text in `players.phone` | Admin/organizer only; excluded from public API |
| Player email addresses | Plain text in `players.email` | Admin/organizer only; excluded from public API |
| Match signatures | Not stored — collected only for display | `signature_data` is never written to audit logs |
| Sanctum tokens | SHA-256 hash in `personal_access_tokens.token` | Plain text shown once at issuance only |

Audit log entries (if implemented) must never include `signature_data`, raw PINs, or password fields. Logging middleware should strip these keys before writing.

---

## 7. Pre-Competition Security Checklist

Run `php artisan c8p:health-check` — it verifies several of these automatically.

- [ ] `APP_ENV=production` in `.env`
- [ ] `APP_DEBUG=false` in `.env`
- [ ] `.env` file is **not** present in `public/`
- [ ] `storage/` is **not** publicly accessible (check `.htaccess` or nginx config)
- [ ] Rate limiting is active (`throttle` middleware applied to API routes)
- [ ] All Sanctum tokens from previous events have been revoked (`php artisan sanctum:prune-expired` or manual `truncate personal_access_tokens`)
- [ ] Admin accounts use strong, unique passwords (minimum 16 characters)
- [ ] No default/test user accounts remain active in production
- [ ] Database credentials in `.env` are production-specific (not shared with dev)
- [ ] `config:cache`, `route:cache`, `view:cache` have been run (reduces attack surface from config exposure)
- [ ] HTTPS is enforced — `APP_URL` starts with `https://`
- [ ] `FORCE_HTTPS=true` or equivalent redirect is active in the web server config
