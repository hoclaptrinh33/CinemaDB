<?php

namespace App\Http\Controllers;

use App\Events\UserFollowed;
use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    /** POST /users/{user}/follow */
    public function store(Request $request, User $user, GamificationService $gamification): RedirectResponse
    {
        $currentUser = $request->user();

        abort_if($currentUser->id === $user->id, 422, 'Cannot follow yourself.');

        // syncWithoutDetaching is idempotent — safe to call repeatedly
        $currentUser->following()->syncWithoutDetaching([$user->id]);

        event(new UserFollowed($currentUser, $user));
        $gamification->handleFollow($currentUser->fresh(), $user->fresh());

        return back();
    }

    /** DELETE /users/{user}/follow */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $request->user()->following()->detach($user->id);

        return back();
    }
}
