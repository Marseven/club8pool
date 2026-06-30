<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Player;
use App\Services\PlayerLoginResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class PreparePlayerAccounts extends Command
{
    protected $signature = 'c8p:prepare-player-accounts
                            {competition? : Competition ID or slug}
                            {--reset-passwords : Reset all passwords to default}';

    protected $description = 'Initialize player account credentials (login_name, login_slug, password)';

    public function handle(PlayerLoginResolver $resolver): int
    {
        $arg = $this->argument('competition');
        $resetPasswords = $this->option('reset-passwords');

        if ($arg) {
            $competition = Competition::where('id', $arg)->orWhere('slug', $arg)->first();
            if (!$competition) {
                $this->error("Competition not found: {$arg}");
                return Command::FAILURE;
            }
            $players = Player::whereHas('registrations', fn($q) => $q->where('competition_id', $competition->id))->get();
            $this->line("Processing players registered in: {$competition->name}");
        } else {
            $players = Player::all();
            $this->line('Processing all players.');
        }

        $created  = 0;
        $existing = 0;
        $reset    = 0;
        $ambiguous = 0;

        foreach ($players as $player) {
            $changed = false;

            // Set login_name from first_name if missing
            if (empty($player->login_name)) {
                $player->login_name = trim($player->first_name);
                $changed = true;
            }

            // Generate unique login_slug if missing
            if (empty($player->login_slug)) {
                $player->login_slug = $resolver->generateSlug($player->login_name);
                $changed = true;
            }

            // Set password if missing or --reset-passwords
            if (empty($player->password)) {
                $player->password = Hash::make('1234567');
                $player->must_change_password = true;
                $player->is_player_account_enabled = true;
                $changed = true;
                $created++;
            } elseif ($resetPasswords) {
                $player->password = Hash::make('1234567');
                $player->must_change_password = true;
                $player->is_player_account_enabled = true;
                $changed = true;
                $reset++;
            } else {
                $existing++;
            }

            if ($changed) {
                $player->save();
            }
        }

        $this->newLine();
        $this->info("Done.");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Passwords created',   $created],
                ['Passwords kept',      $existing],
                ['Passwords reset',     $reset],
                ['Ambiguous (skipped)', $ambiguous],
            ]
        );

        return Command::SUCCESS;
    }
}
