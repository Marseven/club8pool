<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'club_id', 'fgb_card', 'phone', 'email',
        'birthdate', 'cue', 'address', 'flag', 'rating', 'wins', 'losses',
    ];

    protected $casts = [
        'birthdate' => 'date',
    ];

    public function getNameAttribute(): string
    {
        return strtoupper($this->last_name) ? trim($this->first_name . ' ' . strtoupper($this->last_name)) : $this->first_name;
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(($this->first_name[0] ?? '') . ($this->last_name[0] ?? ''));
    }

    public function getWinRateAttribute(): int
    {
        $total = $this->wins + $this->losses;
        return $total > 0 ? (int) round(($this->wins / $total) * 100) : 0;
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function matchesAsA()
    {
        return $this->hasMany(GameMatch::class, 'player_a_id');
    }

    public function matchesAsB()
    {
        return $this->hasMany(GameMatch::class, 'player_b_id');
    }
}
