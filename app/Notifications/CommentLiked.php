<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CommentLiked extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Comment $comment,
        private readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'        => 'comment_liked',
            'group_key'   => "comment_liked:{$this->comment->comment_id}",
            'actor_names' => [$this->actor->username],
            'count'       => 1,
            'comment_id'  => $this->comment->comment_id,
            'title_id'    => $this->comment->title_id,
            'title_name'  => $this->comment->title?->name ?? '',
            'preview'     => mb_substr($this->comment->content, 0, 80),
        ];
    }
}
