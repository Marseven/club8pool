<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\Competition;

class HealthCheckCommand extends Command
{
    protected $signature = 'c8p:health-check';
    protected $description = 'Run pre-competition health checks';

    public function handle(): int
    {
        $ok = true;

        $this->info('== Club 8 Pool Health Check ==');
        $this->newLine();

        // 1. Database connectivity
        try {
            DB::connection()->getPdo();
            $this->line('<fg=green>✓</> Database: connected');
        } catch (\Exception $e) {
            $this->error('✗ Database: ' . $e->getMessage());
            $ok = false;
        }

        // 2. Pending migrations
        try {
            $migrationFiles = array_map(
                fn ($f) => pathinfo($f, PATHINFO_FILENAME),
                glob(database_path('migrations/*.php'))
            );
            $ran = DB::table('migrations')->pluck('migration')->toArray();
            $pending = count(array_diff($migrationFiles, $ran));

            if ($pending > 0) {
                $this->warn("⚠ Migrations: {$pending} pending");
                $ok = false;
            } else {
                $this->line('<fg=green>✓</> Migrations: up to date');
            }
        } catch (\Exception $e) {
            $this->error('✗ Migrations check failed: ' . $e->getMessage());
        }

        // 3. Storage writable
        try {
            Storage::disk('local')->put('_health_test', 'ok');
            Storage::disk('local')->delete('_health_test');
            $this->line('<fg=green>✓</> Storage: writable');
        } catch (\Exception $e) {
            $this->error('✗ Storage: ' . $e->getMessage());
            $ok = false;
        }

        // 4. Cache writable
        try {
            Cache::put('_health_test', 'ok', 5);
            Cache::forget('_health_test');
            $this->line('<fg=green>✓</> Cache: writable');
        } catch (\Exception $e) {
            $this->warn('⚠ Cache: ' . $e->getMessage());
        }

        // 5. APP_DEBUG check
        if (config('app.debug') && config('app.env') === 'production') {
            $this->error('✗ Security: APP_DEBUG=true in production!');
            $ok = false;
        } else {
            $debugLabel = config('app.debug') ? 'true (non-prod)' : 'false';
            $this->line("<fg=green>✓</> APP_DEBUG: {$debugLabel}");
        }

        // 6. APP_ENV
        $this->line('<fg=green>✓</> APP_ENV: ' . config('app.env'));

        // 7. Check public .env exposure
        if (file_exists(public_path('.env'))) {
            $this->error('✗ CRITICAL: .env file found in public/ directory!');
            $ok = false;
        } else {
            $this->line('<fg=green>✓</> No .env exposed in public/');
        }

        // 8. Active competitions check
        try {
            $activeCount = Competition::where('status', 'in_progress')->count();
            $this->line('<fg=green>✓</> Active competitions: ' . $activeCount);
        } catch (\Exception $e) {
            $this->warn('⚠ Could not check competitions');
        }

        $this->newLine();

        if ($ok) {
            $this->info('All checks passed. System is ready.');
            return Command::SUCCESS;
        } else {
            $this->error('Some checks failed. Review before competition.');
            return Command::FAILURE;
        }
    }
}
