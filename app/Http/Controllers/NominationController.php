<?php

namespace App\Http\Controllers;

use App\Models\Nomination;
use App\Models\Title;
use App\Services\FeedService;
use App\Services\NominationQuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NominationController extends Controller
{
    public function __construct(private FeedService $feedService) {}

    public function nominate(Request $request, Title $title): RedirectResponse
    {
        $user = $request->user();
        $today = Carbon::now('UTC')->toDateString();

        $usedToday = NominationQuota::usedToday($user->id, $today);

        if ($usedToday >= NominationQuota::DAILY_LIMIT) {
            return back(303)->with('error', "Bạn đã dùng hết {$usedToday} lượt đề cử trong ngày hôm nay.");
        }

        $already = Nomination::where('user_id', $user->id)
            ->where('title_id', $title->title_id)
            ->where('nominated_date', $today)
            ->exists();

        if ($already) {
            return back(303)->with('error', 'Bạn đã đề cử phim này hôm nay rồi.');
        }

        Nomination::create([
            'title_id'       => $title->title_id,
            'user_id'        => $user->id,
            'nominated_date' => $today,
        ]);

        $this->feedService->record(
            actorId: $user->id,
            activityType: 'nomination_created',
            subjectType: 'title',
            subjectId: $title->title_id,
            titleId: $title->title_id,
            metadata: [
                'title_name' => $title->title_name,
                'title_slug' => $title->slug,
                'poster_url' => $title->poster_url,
                'title_type' => $title->title_type,
            ]
        );

        $remaining = NominationQuota::DAILY_LIMIT - $usedToday - 1;
        return back(303)->with('success', 'Đã đề cử phim! Còn ' . $remaining . ' lượt hôm nay.');
    }

    public function unnominate(Request $request, Title $title): RedirectResponse
    {
        $today = Carbon::now('UTC')->toDateString();

        Nomination::where('user_id', $request->user()->id)
            ->where('title_id', $title->title_id)
            ->where('nominated_date', $today)
            ->delete();

        return back(303)->with('success', 'Đã huỷ đề cử.');
    }
}
