<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * ADMIN bypasses all checks.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    public function moderate(User $user, Review $review): bool
    {
        return $user->isModerator();
    }

    public function forceDelete(User $user, Review $review): bool
    {
        return $user->isModerator();
    }

    public function voteHelpful(User $user, Review $review): bool
    {
        return $user->isActive()
            && $user->id !== $review->user_id;
    }
}
