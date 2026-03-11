<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * ADMIN bypasses all checks.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false; // before() bypass for ADMIN
    }

    public function update(User $user, User $target): bool
    {
        // Admin cannot change their own role
        return $user->user_id !== $target->user_id;
    }
}
