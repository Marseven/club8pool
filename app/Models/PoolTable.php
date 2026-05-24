<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolTable extends Model
{
    protected $fillable = ['competition_id', 'name', 'location', 'status'];

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    public function liveMatch()
    {
        return $this->hasOne(GameMatch::class)->where('status', 'live')->latestOfMany();
    }
}
