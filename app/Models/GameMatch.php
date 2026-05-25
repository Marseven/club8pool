<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $table = 'matches';

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'frames' => 'array',
    ];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function playerA()
    {
        return $this->belongsTo(Player::class, 'player_a_id');
    }

    public function playerB()
    {
        return $this->belongsTo(Player::class, 'player_b_id');
    }

    public function table()
    {
        return $this->belongsTo(PoolTable::class, 'pool_table_id');
    }

    public function pool()
    {
        return $this->belongsTo(Pool::class);
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function frameRecords()
    {
        return $this->hasMany(Frame::class, 'match_id');
    }

    public function signatures()
    {
        return $this->hasMany(Signature::class, 'match_id');
    }
}
