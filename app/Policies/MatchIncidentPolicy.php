<?php

namespace App\Policies;

use App\Models\MatchIncident;
use App\Models\User;

class MatchIncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user)
            || in_array($user->role, ['head_referee', 'tournament_director']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['referee', 'head_referee', 'tournament_director'])
            || $this->isAdmin($user);
    }

    public function update(User $user, MatchIncident $incident): bool
    {
        return $this->isAdmin($user)
            || in_array($user->role, ['head_referee', 'tournament_director']);
    }

    public function resolve(User $user, MatchIncident $incident): bool
    {
        return $this->isAdmin($user)
            || $user->role === 'tournament_director';
    }

    public function assign(User $user, MatchIncident $incident): bool
    {
        return $this->isAdmin($user)
            || in_array($user->role, ['head_referee', 'tournament_director']);
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'organizer']);
    }
}
