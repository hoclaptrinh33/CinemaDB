<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LevelUp extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly array $rank) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'level_up',
            'level'      => $this->rank['level'] ?? null,
            'rank_label' => $this->rank['label'],
            'rank_icon'  => $this->rank['icon'],
        ];
    }
}
