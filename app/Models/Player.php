<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;

class Player extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $fillable = [
        'first_name', 'last_name', 'club_id', 'fgb_card', 'phone', 'email',
        'birthdate', 'cue', 'address', 'flag', 'rating', 'wins', 'losses',
        'login_name', 'login_slug', 'password', 'must_change_password',
        'last_login_at', 'profile_photo_path', 'is_player_account_enabled',
    ];

    // Never expose PII in public JSON serialization
    protected $hidden = ['phone', 'email', 'address', 'birthdate', 'password', 'remember_token'];

    protected $casts = [
        'birthdate'                  => 'date',
        'must_change_password'       => 'boolean',
        'is_player_account_enabled'  => 'boolean',
        'last_login_at'              => 'datetime',
    ];

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

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

    public function ratings()
    {
        return $this->hasMany(PlayerRating::class);
    }

    public function competitionStats()
    {
        return $this->hasMany(PlayerCompetitionStatistic::class);
    }
}
