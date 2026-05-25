<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pool extends Model
{
    protected $fillable = ['competition_id', 'name', 'position', 'size'];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class)->orderBy('pool_slot');
    }

    public function players()
    {
        return $this->belongsToMany(Player::class, 'registrations')
                    ->withPivot(['pool_slot', 'seed'])
                    ->orderBy('pool_slot');
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class)->where('phase', 'pool');
    }
}
