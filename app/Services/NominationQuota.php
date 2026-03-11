<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NominationQuota
{
    public const DAILY_LIMIT = 3;

    /**
     * Count total nominations used today across both titles and collections.
     */
    public static function usedToday(int $userId, string $date): int
    {
        $titleCount = DB::table('title_nominations')
            ->where('user_id', $userId)
            ->where('nominated_date', $date)
            ->count();

        $collectionCount = DB::table('collection_nominations')
            ->where('user_id', $userId)
            ->where('nominated_date', $date)
            ->count();

        return $titleCount + $collectionCount;
    }
}
