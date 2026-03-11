<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use App\Notifications\BadgeEarned;

class BadgeAwardService
{
    public function __construct(
        private BadgeConditionEvaluator $evaluator,
        private FeedService $feedService,
    ) {}

    public function evaluateAndAward(User $user, array $conditionTypes, array $context = []): void
    {
        if ($conditionTypes === []) {
            return;
        }

        Badge::query()
            ->whereIn('condition_type', $conditionTypes)
            ->orderBy('sort_order')
            ->orderBy('condition_value')
            ->get()
            ->each(function (Badge $badge) use ($user, $context): void {
                if (! $this->evaluator->isEarned($badge, $user, $context)) {
                    return;
                }

                $this->award($user, $badge, $this->evaluator->currentValue($badge, $user, $context));
            });
    }

    public function award(User $user, Badge $badge, ?int $snapshotValue = null): bool
    {
        $bonus = $badge->reputationBonus();
        $previousReputation = $user->reputation;

        $result = $user->badges()->syncWithoutDetaching([
            $badge->badge_id => [
                'earned_at' => now(),
                'earned_reputation_bonus' => $bonus,
                'earned_snapshot_value' => $snapshotValue,
            ],
        ]);

        if (empty($result['attached'])) {
            return false;
        }

        $user->increment('reputation', $bonus);
        $user->notify(new BadgeEarned($badge->fresh()));
        LevelUpService::check($user, $previousReputation);

        $this->feedService->record(
            actorId: $user->id,
            activityType: 'badge_earned',
            subjectType: 'badge',
            subjectId: $badge->badge_id,
            metadata: [
                'badge_name' => $badge->name,
                'badge_slug' => $badge->slug,
                'badge_tier' => $badge->tier,
                'icon_path' => $badge->icon_path,
                'rarity_tier' => $badge->rarity_tier,
                'frame_style' => $badge->frame_style,
                'earned_users_percent' => (float) $badge->earned_users_percent,
            ]
        );

        $this->refreshStatistics($badge);

        return true;
    }

    public function refreshStatistics(Badge $badge): void
    {
        $earnedUsers = $badge->users()->count();
        $totalUsers = max(1, User::count());

        $badge->forceFill([
            'earned_users_count' => $earnedUsers,
            'earned_users_percent' => round(($earnedUsers / $totalUsers) * 100, 2),
        ])->save();
    }
}
