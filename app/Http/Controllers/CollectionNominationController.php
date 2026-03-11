<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionNomination;
use App\Services\GamificationService;
use App\Services\NominationQuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CollectionNominationController extends Controller
{
    public function nominate(Request $request, Collection $collection, GamificationService $gamification): RedirectResponse
    {
        $user  = $request->user();
        $today = Carbon::now('UTC')->toDateString();

        if ($collection->visibility !== 'PUBLIC' || ! $collection->is_published) {
            return back(303)->with('error', 'Bộ sưu tập này không thể được đề cử.');
        }

        $usedToday = NominationQuota::usedToday($user->id, $today);

        if ($usedToday >= NominationQuota::DAILY_LIMIT) {
            return back(303)->with('error', "Bạn đã dùng hết {$usedToday} lượt đề cử trong ngày hôm nay.");
        }

        $already = CollectionNomination::where('user_id', $user->id)
            ->where('collection_id', $collection->collection_id)
            ->where('nominated_date', $today)
            ->exists();

        if ($already) {
            return back(303)->with('error', 'Bạn đã đề cử danh sách này hôm nay rồi.');
        }

        CollectionNomination::create([
            'collection_id'  => $collection->collection_id,
            'user_id'        => $user->id,
            'nominated_date' => $today,
        ]);

        $gamification->handleCollectionNomination($collection->fresh('user'), $user);

        $remaining = NominationQuota::DAILY_LIMIT - $usedToday - 1;
        return back(303)->with('success', 'Đã đề cử danh sách! Còn ' . $remaining . ' lượt hôm nay.');
    }

    public function unnominate(Request $request, Collection $collection): RedirectResponse
    {
        $today = Carbon::now('UTC')->toDateString();

        CollectionNomination::where('user_id', $request->user()->id)
            ->where('collection_id', $collection->collection_id)
            ->where('nominated_date', $today)
            ->delete();

        return back(303)->with('success', 'Đã huỷ đề cử danh sách.');
    }
}
