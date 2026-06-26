<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerRating extends Model
{
    protected $table = 'player_ratings';

    protected $guarded = ['id'];

    protected $fillable = [
        'player_id',
        'discipline',
        'rating',
        'games_played',
        'frames_won',
        'frames_lost',
        'robustness',
        'provisional',
        'last_match_at',
    ];

    protected $casts = [
        'provisional'   => 'boolean',
        'last_match_at' => 'datetime',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
