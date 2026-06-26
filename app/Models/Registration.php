<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = ['competition_id', 'pool_id', 'pool_slot', 'player_id', 'seed', 'seed_rating', 'status', 'registered_at'];

    protected $casts = [
        'registered_at' => 'datetime',
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function pool()
    {
        return $this->belongsTo(Pool::class);
    }
}
