<?php

namespace App\Services;

use App\Models\FeedItem;

/**
 * Records user activity as feed items for the social feed.
 *
 * Supported activity_type values in v1:
 *   review_created | comment_created | collection_created
 *   collection_title_added | nomination_created | badge_earned
 */
class FeedService
{
    public function record(
        int     $actorId,
        string  $activityType,
        ?string $subjectType = null,
        ?int    $subjectId   = null,
        ?int    $titleId     = null,
        ?int    $collectionId = null,
        array   $metadata    = []
    ): void {
        FeedItem::create([
            'actor_user_id' => $actorId,
            'activity_type' => $activityType,
            'subject_type'  => $subjectType,
            'subject_id'    => $subjectId,
            'title_id'      => $titleId,
            'collection_id' => $collectionId,
            'metadata'      => $metadata ?: null,
            'created_at'    => now(),
        ]);
    }
}
