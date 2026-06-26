<?php

namespace App\Policies;

use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Competition $competition): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Competition $competition): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $this->isAdmin($user) && $competition->status === 'draft';
    }

    public function uploadLogo(User $user, Competition $competition): bool
    {
        return $this->isAdmin($user);
    }

    public function generateBracket(User $user, Competition $competition): bool
    {
        return $this->isAdmin($user);
    }

    public function importResults(User $user, Competition $competition): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'organizer']);
    }
}
