<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(User $user, Registration $registration): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Registration $registration): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'organizer']);
    }
}
