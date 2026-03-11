<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\Title;
use App\Models\User;

class TitlePolicy
{
    /**
     * ADMIN bypasses all checks.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewHidden(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MODERATOR']);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin(); // before() already handles this
    }

    public function update(User $user, Title $title): bool
    {
        return false; // only ADMIN (before bypass)
    }

    public function delete(User $user, Title $title): bool
    {
        return false; // only ADMIN (before bypass)
    }

    public function toggleVisibility(User $user, Title $title): bool
    {
        return $user->isModerator();
    }
}
