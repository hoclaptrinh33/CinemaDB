<?php

namespace App\Listeners;

use App\Events\ReviewCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateReputationOnReview implements ShouldQueue
{
    public function handle(ReviewCreated $event): void
    {
        $review = $event->review;
        $points = $this->calcPoints($review->review_text);

        // reputation_earned was already stored by ReviewService; just ensure user rep is correct.
        // ReviewService::create() increments users.reputation synchronously before the event,
        // so this listener only needs to act if the stored value is out-of-sync (should not happen).
        // We use this listener surface for future expansion (e.g. analytics, bonuses).
    }

    private function calcPoints(?string $text): int
    {
        if (blank($text)) {
            return 2;
        }

        $wordCount = str_word_count(strip_tags($text));

        return $wordCount >= 50 ? 10 : 3;
    }
}
