<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;

class BadgeConditionEvaluator
{
    public function isEarned(Badge $badge, User $user, array $context = []): bool
    {
        if ($badge->condition_type === 'manual') {
            return false;
        }

        return $this->currentValue($badge, $user, $context) >= (int) ($badge->condition_value ?? 0);
    }

    public function currentValue(Badge $badge, User $user, array $context = []): int
    {
        return match ($badge->condition_type) {
            'review_count' => $user->reviews()->count(),
            'helpful_votes' => (int) $user->reviews()->sum('helpful_votes'),
            'collections_count' => $user->collections()->count(),
            'distinct_types' => $user->reviews()
                ->join('titles', 'reviews.title_id', '=', 'titles.title_id')
                ->distinct()
                ->count('titles.title_type'),
            'early_bird' => $this->resolveEarlyBirdDays($context),
            'collection_nominations_received' => (int) $user->collections()->sum('nomination_count'),
            'follower_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'activity_streak', 'login_streak' => (int) $user->current_streak_days,
            default => 0,
        };
    }

    private function resolveEarlyBirdDays(array $context): int
    {
        $review = $context['review'] ?? null;
        $title = $context['title'] ?? $review?->title;

        if (! $review || ! $title?->release_date || ! $review->created_at) {
            return PHP_INT_MAX;
        }

        return max(0, $title->release_date->diffInDays($review->created_at, false));
    }
}
