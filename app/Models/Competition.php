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
}
