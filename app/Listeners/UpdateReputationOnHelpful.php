<?php

namespace App\Listeners;

use App\Events\HelpfulVoteToggled;
use App\Services\BadgeAwardService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateReputationOnHelpful implements ShouldQueue
{
    public function __construct(private BadgeAwardService $badgeAwards) {}

    public function handle(HelpfulVoteToggled $event): void
    {
        // Reputation change (+5 / -5) is already applied synchronously in ReviewService.
        if (! $event->added) {
            return;
        }

        $this->badgeAwards->evaluateAndAward($event->review->user, ['helpful_votes']);
    }
}
