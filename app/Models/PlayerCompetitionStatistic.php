<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerCompetitionStatistic extends Model
{
    protected $table = 'player_competition_statistics';

    protected $guarded = ['id'];

    protected $fillable = [
        'competition_id',
        'player_id',
        'matches_played',
        'matches_won',
        'matches_lost',
        'frames_won',
        'frames_lost',
        'breaks_taken',
        'break_wins',
        'break_and_runs',
        'safeties',
        'fouls',
        'misses',
        'warnings',
        'shot_clock_violations',
        'avg_match_duration_seconds',
        'is_stale',
    ];

    protected $casts = [
        'is_stale' => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }
}
