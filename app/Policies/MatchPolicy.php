<?php

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\User;

class MatchPolicy
{
    public function view(?User $user, GameMatch $match): bool
    {
        return true;
    }

    public function start(User $user, GameMatch $match): bool
    {
        return $this->isAdmin($user)
            || $match->referee_id === $user->id
            || in_array($user->role, ['head_referee', 'tournament_director']);
    }

    public function scoreFrame(User $user, GameMatch $match): bool
    {
        return $this->isAdmin($user)
            || $match->referee_id === $user->id
            || in_array($user->role, ['head_referee']);
    }

    public function undoFrame(User $user, GameMatch $match): bool
    {
        return $this->isAdmin($user)
            || $match->referee_id === $user->id
            || in_array($user->role, ['head_referee']);
    }

    public function close(User $user, GameMatch $match): bool
    {
        return $this->isAdmin($user)
            || $match->referee_id === $user->id
            || in_array($user->role, ['head_referee']);
    }

    public function correctScore(User $user, GameMatch $match): bool
    {
        return $this->isAdmin($user)
            || in_array($user->role, ['tournament_director']);
    }

    public function claim(User $user, GameMatch $match): bool
    {
        return ($user->role === 'referee' || in_array($user->role, ['head_referee']))
            && !$match->referee_id;
    }

    public function reassign(User $user, GameMatch $match): bool
    {
        return $this->isAdmin($user)
            || in_array($user->role, ['head_referee', 'tournament_director']);
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'organizer']);
    }
}
