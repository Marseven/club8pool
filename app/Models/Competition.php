<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'registration_closes_at' => 'datetime',
        'settings' => 'array',
        'alternate_break' => 'boolean',
        'push_out' => 'boolean',
        'draw_randomize_unseeded' => 'boolean',
        'seeded_players_count' => 'integer',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? asset('storage/' . $this->logo_path)
            : null;
    }

    public function tables()
    {
        return $this->hasMany(PoolTable::class);
    }

    public function pools()
    {
        return $this->hasMany(Pool::class)->orderBy('position');
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'registrations')->withPivot(['seed', 'status'])->withTimestamps();
    }

    public function raceForPhase(string $phase): int
    {
        return match ($phase) {
            'pool' => (int) ($this->pool_race_to ?? $this->race_to),
            'knockout' => (int) ($this->knockout_race_to ?? $this->race_to),
            default => (int) $this->race_to,
        };
    }

    /**
     * Returns the race-to for a specific knockout round.
     * Reads from settings['round_race_to'][round] if set, otherwise falls back
     * to knockout_race_to, then race_to.
     */
    public function raceForRound(string $round): int
    {
        $perRound = $this->settings['round_race_to'] ?? [];
        return (int) ($perRound[$round] ?? $this->knockout_race_to ?? $this->race_to);
    }

    /**
     * Returns the prize breakdown from settings.
     * Keys: '1st', '2nd', '3rd', '4th', '5th-8th', etc.
     * Each entry: ['label', 'amount' (nullable), 'currency', ...].
     */
    public function prizeBreakdown(): array
    {
        return $this->settings['prize_breakdown'] ?? [];
    }

    /**
     * Returns the competition schedule from settings.
     * Structure: ['timezone', 'registration_deadline', 'days' => [...]].
     */
    public function competitionSchedule(): array
    {
        return $this->settings['schedule'] ?? [];
    }

    /**
     * Returns payment / registration info from settings.
     * Structure: ['registration_fee', 'currency', 'methods' => [...], 'contacts' => [...]].
     */
    public function paymentInfo(): array
    {
        return $this->settings['payment'] ?? [];
    }
}
