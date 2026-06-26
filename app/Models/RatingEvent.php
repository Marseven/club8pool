<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingEvent extends Model
{
    protected $table = 'rating_events';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $fillable = [
        'match_id',
        'competition_id',
        'discipline',
        'player_a_id',
        'player_b_id',
        'rating_a_before',
        'rating_b_before',
        'rating_a_after',
        'rating_b_after',
        'expected_a',
        'expected_b',
        'score_a',
        'score_b',
        'margin_factor',
        'k_factor_a',
        'k_factor_b',
    ];

    protected $casts = [
        'expected_a'    => 'decimal:4',
        'expected_b'    => 'decimal:4',
        'margin_factor' => 'decimal:3',
        'created_at'    => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function playerA(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_a_id');
    }

    public function playerB(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_b_id');
    }
}
