<?php

namespace App\Listeners;

use App\Events\UserFollowed;
use App\Notifications\NewFollower;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFollowNotification implements ShouldQueue
{
    public function handle(UserFollowed $event): void
    {
        $event->followee->notify(new NewFollower($event->follower));
    }
}
