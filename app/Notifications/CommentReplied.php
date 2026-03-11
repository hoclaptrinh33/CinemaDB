<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CommentReplied extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Comment $parentComment,
        private readonly Comment $reply,
        private readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'comment_replied',
            'group_key'  => "comment_replied:{$this->parentComment->comment_id}",
            'actor_name' => $this->actor->username,
            'comment_id' => $this->parentComment->comment_id,
            'reply_id'   => $this->reply->comment_id,
            'title_id'   => $this->parentComment->title_id,
            'title_name' => $this->parentComment->title?->name ?? '',
            'preview'    => mb_substr($this->reply->content, 0, 100),
        ];
    }
}
