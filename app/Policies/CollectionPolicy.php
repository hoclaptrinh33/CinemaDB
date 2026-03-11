<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
{
    /** Owner-only: rename, change description/visibility, delete. */
    public function update(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    /** Owner or accepted collaborator: add/remove titles. */
    public function manageItems(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id
            || $collection->isCollaborator($user->id);
    }

    /** Owner-only: send collaborator invites. */
    public function invite(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id;
    }

    /** Owner or Admin: delete the whole collection. */
    public function delete(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id || $user->hasRole('ADMIN');
    }

    /** Owner-only: publish (collection must be PUBLIC). */
    public function publish(User $user, Collection $collection): bool
    {
        return $user->id === $collection->user_id && $collection->visibility === 'PUBLIC';
    }

    /** Any authenticated user can copy a published public collection. */
    public function copy(User $user, Collection $collection): bool
    {
        return $collection->is_published && $collection->visibility === 'PUBLIC';
    }
}
