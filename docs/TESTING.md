# Testing Guide — Club 8 Pool

## 1. Prerequisites

| Requirement | Minimum version |
|---|---|
| PHP | 8.2 |
| Composer | 2.x |
| SQLite extension | bundled with PHP 8.2 |
| Node.js (for asset compilation only) | 18.x |

Verify your PHP version and SQLite availability:

```bash
php -v
php -m | grep sqlite3
```

---

## 2. Setup

```bash
# Install PHP dependencies
composer install

# Copy the test environment file (if it does not exist)
cp .env.example .env.testing

# Run the full test suite
php artisan test
```

The test suite uses an **in-memory SQLite database** configured in `phpunit.xml`. No MySQL connection is required to run tests. No seeding is needed — each test manages its own data via factories and `RefreshDatabase`.

---

## 3. Test Structure

```
tests/
├── Feature/
│   ├── Security/
│   │   ├── RateLimitingTest.php         — throttle:5,1 on referee login
│   │   ├── AuthTest.php                 — unauthenticated access returns 401/403
│   │   └── UploadValidationTest.php     — Excel import rejects invalid files
│   ├── Api/
│   │   ├── RefereeAuthTest.php          — PIN login, token issuance, logout
│   │   ├── FrameScoringTest.php         — referee scores frames on assigned match
│   │   └── MatchOverrideTest.php        — tournament_director can override result
│   └── Services/
│       ├── BracketProgressionTest.php   — winner advances to correct next slot
│       └── PoolStandingTest.php         — standings recalculated on match done
└── Unit/
    └── Services/
        └── PlayerRatingServiceTest.php  — Elo delta calculations, K-factor, margin
```

### Feature tests
Feature tests boot the full Laravel application (routes, middleware, database). They are appropriate for testing HTTP behaviour, middleware enforcement, and service interactions.

### Unit tests
Unit tests instantiate a single class in isolation. Use them for pure logic: rating calculations, score parsing, format utilities. They run much faster and should not touch the database.

---

## 4. Running Specific Suites

```bash
# Run a single test class
php artisan test --filter=PoolStandingTest

# Run a single method
php artisan test --filter=PoolStandingTest::it_ranks_players_by_points_then_frame_diff

# Run all Feature tests
php artisan test tests/Feature

# Run all Security tests
php artisan test tests/Feature/Security

# Run with verbose output (shows each test name)
php artisan test --verbose

# Stop on first failure
php artisan test --stop-on-failure
```

---

## 5. Test Database Configuration

`phpunit.xml` sets the following environment overrides:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
```

The in-memory SQLite database is created fresh for each test class that uses `RefreshDatabase`. Tests that do not use `RefreshDatabase` share the same database state within the class — use this only for read-only tests where isolation is not needed.

---

## 6. Writing New Tests

### Always use RefreshDatabase

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;
    // ...
}
```

### Always test failure cases

Every happy-path test should have a corresponding failure-case test:

```php
// Good
public function it_scores_a_frame(): void { ... }
public function it_rejects_frame_score_when_not_assigned_referee(): void { ... }
public function it_rejects_frame_score_when_match_is_done(): void { ... }
```

### Use factories, not raw inserts

```php
// Good
$match = GameMatch::factory()->inProgress()->create();

// Avoid
DB::table('game_matches')->insert([...]);
```

---

## 7. Coverage

```bash
# Generate HTML coverage report (requires Xdebug or PCOV)
php artisan test --coverage

# With a minimum threshold (fails CI if below 80%)
php artisan test --coverage --min=80
```

To install PCOV (faster than Xdebug):

```bash
pecl install pcov
# Add to php.ini:
extension=pcov.so
pcov.enabled=1
```

Coverage reports are written to `coverage/` (excluded from version control via `.gitignore`).

---

## 8. Factory Patterns for Common Models

### Competition

```php
// Active competition in pool phase
$competition = Competition::factory()->inProgress()->create([
    'format' => 'pool_then_knockout',
    'phase'  => 'pool',
]);

// Finished competition
$competition = Competition::factory()->finished()->create();
```

### Player

```php
// Player with a rating
$player = Player::factory()->withRating(1650)->create();

// Two players for a match
[$playerA, $playerB] = Player::factory()->count(2)->create();
```

### GameMatch

```php
// Assigned match ready to score
$match = GameMatch::factory()
    ->for($competition)
    ->withPlayers($playerA, $playerB)
    ->withReferee($referee)
    ->inProgress()
    ->create();

// Completed match
$match = GameMatch::factory()
    ->done()
    ->create(['score_a' => 3, 'score_b' => 1]);
```

### Referee (API auth)

```php
// Create a referee and get their Sanctum token
$referee = Referee::factory()->create();
$token   = $referee->createToken('test')->plainTextToken;

$response = $this->withToken($token)->postJson('/api/frames', [...]);
```

### Admin (web auth)

```php
$admin = User::factory()->admin()->create();
$this->actingAs($admin)->post('/admin/competitions', [...]);
```
