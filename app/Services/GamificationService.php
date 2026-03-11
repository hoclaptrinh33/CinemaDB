<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Support\Carbon;

class GamificationService
{
    public function __construct(private BadgeAwardService $badgeAwards) {}

    public function recordDailyVisit(User $user): void
    {
        $today = Carbon::now('UTC')->toDateString();

        $activity = UserActivityLog::firstOrCreate([
            'user_id' => $user->id,
            'activity_date' => $today,
        ]);

        if (! $activity->wasRecentlyCreated && $user->last_seen_on?->toDateString() === $today) {
            return;
        }

        $previousSeen = $user->last_seen_on?->toDateString();
        $previousReputation = $user->reputation;
        $yesterday = Carbon::parse($today, 'UTC')->subDay()->toDateString();

        $currentStreak = $previousSeen === $yesterday ? $user->current_streak_days + 1 : 1;

        $user->forceFill([
            'last_seen_on' => $today,
            'current_streak_days' => $currentStreak,
            'longest_streak_days' => max($user->longest_streak_days, $currentStreak),
        ])->save();

        if ($user->last_daily_rewarded_on?->toDateString() !== $today) {
            $reward = (int) config('gamification.daily_visit_reward', 1);
            if ($reward > 0) {
                $user->increment('reputation', $reward);
                LevelUpService::check($user, $previousReputation);
            }

            $user->forceFill(['last_daily_rewarded_on' => $today])->save();
        }

        $this->badgeAwards->evaluateAndAward($user->fresh(), ['activity_streak', 'login_streak']);
    }

    public function handleFollow(User $follower, User $followee): void
    {
        $this->badgeAwards->evaluateAndAward($follower, ['following_count']);
        $this->badgeAwards->evaluateAndAward($followee, ['follower_count']);
    }

    public function handleCollectionNomination(Collection $collection, User $nominator): void
    {
        $owner = $collection->user;

        if (! $owner || $owner->id === $nominator->id) {
            return;
        }

        $previousReputation = $owner->reputation;
        $reward = (int) config('gamification.collection_nomination_received_reward', 2);

        if ($reward > 0) {
            $owner->increment('reputation', $reward);
            LevelUpService::check($owner, $previousReputation);
        }

        $this->badgeAwards->evaluateAndAward($owner->fresh(), ['collection_nominations_received']);
    }
}
