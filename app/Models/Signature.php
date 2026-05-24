<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    protected $fillable = ['match_id', 'player_id', 'signature_data', 'signed_at'];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
