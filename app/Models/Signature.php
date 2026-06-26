<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    protected $fillable = ['match_id', 'player_id', 'signature_data', 'signed_at'];

    // Raw base64 blob — never returned in API responses
    protected $hidden = ['signature_data'];

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
