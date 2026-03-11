<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Badge;
use App\Models\Nomination;
use App\Models\UserActivityLog;
use App\Services\CloudinaryService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load([
            'badges',
            'collections' => fn($q) => $q->withCount('titles')->orderByDesc('created_at'),
            'nominations',
        ]);

        $today = Carbon::now('UTC')->toDateString();

        // All system badges with earned flag
        $earnedIds = $user->badges->pluck('badge_id')->flip();
        $allBadges = Badge::orderByRaw("FIELD(tier,'DIAMOND','PLATINUM','GOLD','SILVER','BRONZE','IRON','WOOD')")
            ->orderBy('condition_value')
            ->get()
            ->map(fn($badge) => array_merge($badge->toArray(), [
                'is_earned' => $earnedIds->has($badge->badge_id),
                'earned_at' => $earnedIds->has($badge->badge_id)
                    ? optional($user->badges->firstWhere('badge_id', $badge->badge_id)->pivot)->earned_at
                    : null,
            ]));

        // Activity calendar — last 365 days
        $startDate = Carbon::now('UTC')->subDays(364)->startOfDay();
        $activityDates = UserActivityLog::where('user_id', $user->id)
            ->where('activity_date', '>=', $startDate->toDateString())
            ->pluck('activity_date')
            ->map(fn($d) => $d instanceof \DateTimeInterface ? $d->format('Y-m-d') : (string) $d)
            ->flip()
            ->all();

        $calendar = [];
        for ($i = 0; $i <= 364; $i++) {
            $date = Carbon::now('UTC')->subDays(364 - $i)->toDateString();
            $calendar[] = ['date' => $date, 'active' => isset($activityDates[$date])];
        }

        // Stats
        $nominationsToday = Nomination::where('user_id', $user->id)
            ->where('nominated_date', $today)
            ->count();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => session('status'),
            'user'            => [
                'name'         => $user->name,
                'username'     => $user->username,
                'email'        => $user->email,
                'role'         => $user->role,
                'reputation'   => $user->reputation,
                'avatar_url'   => $user->avatar_url,
                'cover_url'    => $user->cover_url,
                'created_at'   => $user->created_at,
                'has_password' => !is_null($user->password),
            ],
            'allBadges'       => $allBadges,
            'collections'     => $user->collections->map(fn($c) => [
                'collection_id' => $c->collection_id,
                'name'          => $c->name,
                'slug'          => $c->slug,
                'visibility'    => $c->visibility,
                'titles_count'  => $c->titles_count,
            ]),
            'stats'           => [
                'reviews_count'       => $user->reviews()->count(),
                'nominations_total'   => $user->nominations()->count(),
                'nominations_today'   => $nominationsToday,
                'nominations_limit'   => 3,
                'activity_days'       => UserActivityLog::where('user_id', $user->id)->count(),
                'current_streak_days' => $user->current_streak_days,
                'longest_streak_days' => $user->longest_streak_days,
                'followers_count'     => $user->followers()->count(),
                'following_count'     => $user->following()->count(),
            ],
            'activityCalendar' => $calendar,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Upload avatar image.
     */
    public function uploadAvatar(Request $request, CloudinaryService $cloudinary): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            $cloudinary->delete($user->avatar_path);
        }

        $path = $cloudinary->upload($request->file('avatar'), 'movie-database/avatars');
        $user->update(['avatar_path' => $path]);

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Upload cover image.
     */
    public function uploadCover(Request $request, CloudinaryService $cloudinary): RedirectResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'max:4096', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $user = $request->user();

        if ($user->cover_path) {
            $cloudinary->delete($user->cover_path);
        }

        $path = $cloudinary->upload($request->file('cover'), 'movie-database/covers');
        $user->update(['cover_path' => $path]);

        return Redirect::route('profile.edit')->with('status', 'cover-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! is_null($user->password)) {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
