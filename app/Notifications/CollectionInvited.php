<?php

namespace App\Notifications;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CollectionInvited extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Collection $collection,
        public readonly User $inviter,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'collection_invited',
            'collection_id'   => $this->collection->collection_id,
            'collection_name' => $this->collection->name,
            'collection_slug' => $this->collection->slug,
            'inviter_id'      => $this->inviter->id,
            'inviter_name'    => $this->inviter->name,
            'inviter_username' => $this->inviter->username,
            'url'             => route('collections.show', $this->collection->slug),
        ];
    }
}
