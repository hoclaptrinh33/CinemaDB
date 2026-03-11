<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewFollower extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly User $follower) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'             => 'new_follower',
            'follower_id'      => $this->follower->id,
            'follower_name'    => $this->follower->name,
            'follower_username' => $this->follower->username,
            'follower_avatar'  => $this->follower->avatar_url,
        ];
    }
}
