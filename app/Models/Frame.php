<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    protected $fillable = ['match_id', 'frame_number', 'winner', 'duration_seconds', 'foul_a', 'foul_b', 'note'];

    public function match()
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
