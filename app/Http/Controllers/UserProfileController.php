<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\Review;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\Nomination;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    public function show(Request $request, string $username): Response
    {
        $user = User::where('username', $username)
            ->withCount(['reviews', 'nominations', 'followers', 'following', 'activityLogs'])
            ->firstOrFail()
            ->load(['badges' => fn($q) => $q->withPivot('earned_at')]);

        $viewer         = $request->user();
        $isOwnProfile   = $viewer && $viewer->id === $user->id;
        $isFollowing    = $viewer && !$isOwnProfile ? $viewer->isFollowing($user) : false;
        $followerCount  = $user->followers_count;
        $followingCount = $user->following_count;

        // Earned badges only — sorted by tier
        $tierOrder = ['DIAMOND' => 0, 'PLATINUM' => 1, 'GOLD' => 2, 'SILVER' => 3, 'BRONZE' => 4, 'IRON' => 5, 'WOOD' => 6];
        $earnedBadges = $user->badges
            ->sortBy(fn($b) => $tierOrder[$b->tier] ?? 9)
            ->map(fn($b) => [
                'badge_id'       => $b->badge_id,
                'name'           => $b->name,
                'description'    => $b->description,
                'icon_path'      => $b->icon_path,
                'tier'           => $b->tier,
                'condition_type' => $b->condition_type,
                'condition_value' => $b->condition_value,
                'rarity_tier'    => $b->rarity_tier,
                'frame_style'    => $b->frame_style,
                'earned_users_percent' => (float) $b->earned_users_percent,
                'is_earned'      => true,
                'earned_at'      => optional($b->pivot)->earned_at,
            ])
            ->values();

        // Collections: owned + collaborated (visibility filtered for public profiles)
        $mapCollection = fn($c, string $role) => [
            'collection_id' => $c->collection_id,
            'name'          => $c->name,
            'slug'          => $c->slug,
            'visibility'    => $c->visibility,
            'titles_count'  => $c->titles_count,
            'role'          => $role,
            'owner'         => $role === 'collaborator' ? [
                'name'     => $c->user->name,
                'username' => $c->user->username,
            ] : null,
        ];

        $ownedCollections = $user->collections()
            ->when(! $isOwnProfile, fn($q) => $q->where('visibility', 'PUBLIC'))
            ->withCount('titles')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($c) => $mapCollection($c, 'owner'));

        $collaboratedCollections = \App\Models\Collection::whereHas(
            'acceptedCollaborators',
            fn($q) => $q->where('user_id', $user->id)
        )
            ->when(! $isOwnProfile, fn($q) => $q->where('visibility', 'PUBLIC'))
            ->withCount('titles')
            ->with('user:id,name,username')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($c) => $mapCollection($c, 'collaborator'));

        $collections = $ownedCollections->concat($collaboratedCollections)->values();

        // Stats (no nominations_today / nominations_limit — not relevant for public view)
        $stats = [
            'reviews_count'     => $user->reviews_count,
            'nominations_total' => $user->nominations_count,
            'activity_days'     => $user->activity_logs_count,
            'current_streak_days' => $user->current_streak_days,
            'longest_streak_days' => $user->longest_streak_days,
            'followers_count'   => $followerCount,
            'following_count'   => $followingCount,
        ];

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

        // Top reviews — sorted by helpful votes count, max 5
        $topReviews = Review::where('user_id', $user->id)
            ->where('moderation_status', 'VISIBLE')
            ->withCount('helpfulVoters')
            ->with('title:title_id,title_name,slug,poster_path')
            ->orderByDesc('helpful_voters_count')
            ->orderByDesc('rating')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'review_id'      => $r->review_id,
                'rating'         => $r->rating,
                'review_text'    => $r->review_text,
                'has_spoilers'   => $r->has_spoilers,
                'helpful_count'  => $r->helpful_voters_count,
                'created_at'     => $r->created_at,
                'user'           => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'username'   => $user->username,
                    'avatar'     => $user->avatar_url,
                    'reputation' => $user->reputation,
                ],
                'title'          => $r->title ? [
                    'title_id'   => $r->title->title_id,
                    'title_name' => $r->title->title_name,
                    'slug'       => $r->title->slug,
                    'poster_url' => $r->title->poster_url,
                ] : null,
            ]);

        return Inertia::render('Profile/Show', [
            'profileUser' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'username'        => $user->username,
                'role'            => $user->role,
                'reputation'      => $user->reputation,
                'avatar_url'      => $user->avatar_url,
                'cover_url'       => $user->cover_url,
                'created_at'      => $user->created_at,
                'follower_count'  => $followerCount,
                'following_count' => $followingCount,
            ],
            'isFollowing'      => $isFollowing,
            'isOwnProfile'     => $isOwnProfile,
            'earnedBadges'     => $earnedBadges,
            'collections'      => $collections,
            'stats'            => $stats,
            'activityCalendar' => $calendar,
            'topReviews'       => $topReviews,
        ]);
    }
}
