<?php

namespace App\Listeners;

use App\Events\ReviewCreated;
use App\Services\BadgeAwardService;
use Illuminate\Contracts\Queue\ShouldQueue;

class AwardBadgesOnReview implements ShouldQueue
{
    public function __construct(private BadgeAwardService $badgeAwards) {}

    public function handle(ReviewCreated $event): void
    {
        $this->badgeAwards->evaluateAndAward(
            $event->review->user,
            [
                'review_count',
                'early_bird',
                'collections_count',
                'distinct_types',
            ],
            [
                'review' => $event->review,
                'title' => $event->review->title,
            ]
        );
    }
}
