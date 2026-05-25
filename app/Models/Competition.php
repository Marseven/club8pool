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
    ];

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
}
