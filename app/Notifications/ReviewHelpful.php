<?php

namespace App\Notifications;

use App\Models\Review;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReviewHelpful extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Review $review,
        private readonly User $voter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'review_helpful',
            'group_key'   => "review_helpful:{$this->review->review_id}",
            'actor_names' => [$this->voter->username],
            'count'       => 1,
            'review_id'   => $this->review->review_id,
            'title_id'    => $this->review->title_id,
            'title_name'  => $this->review->title?->name ?? '',
            'preview'     => mb_substr($this->review->review_text ?? '', 0, 80),
        ];
    }
}
