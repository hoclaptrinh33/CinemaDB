<?php

namespace App\Notifications;

use App\Models\Badge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BadgeEarned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Badge $badge) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'badge_earned',
            'badge_name'  => $this->badge->name,
            'badge_slug'  => $this->badge->slug,
            'badge_tier'  => $this->badge->tier,
            'rarity_tier' => $this->badge->rarity_tier,
            'frame_style' => $this->badge->frame_style,
            'earned_users_percent' => (float) $this->badge->earned_users_percent,
            'reputation_bonus' => $this->badge->reputationBonus(),
            'icon_path'   => $this->badge->icon_path,
            'description' => $this->badge->description,
        ];
    }
}
