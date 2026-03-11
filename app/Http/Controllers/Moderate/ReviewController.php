<?php

namespace App\Http\Controllers\Moderate;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function index(): Response
    {
        $reviews = Review::with(['user:id,username', 'title:title_id,title_name,slug'])
            ->when(request('search'), function ($q, $v) {
                $q->where(function ($nested) use ($v) {
                    $nested->whereHas('user', fn($userQ) => $userQ->where('username', 'LIKE', "%{$v}%"))
                        ->orWhereHas('title', fn($titleQ) => $titleQ->where('title_name', 'LIKE', "%{$v}%"));
                });
            })
            ->when(request('status'), function ($q, $v) {
                if ($v === 'HIDDEN') {
                    $q->where('moderation_status', 'HIDDEN');
                }
                if ($v === 'VISIBLE') {
                    $q->where('moderation_status', 'VISIBLE');
                }
            })
            ->latest('review_id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Moderate/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => request()->only(['search', 'status']),
        ]);
    }

    /**
     * Moderators can delete reviews that violate guidelines.
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('forceDelete', $review);

        $this->reviewService->delete($review);

        return back()->with('success', 'Review đã bị xoá.');
    }

    public function updateStatus(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);

        $data = $request->validate([
            'status' => ['required', 'in:VISIBLE,HIDDEN'],
        ]);

        $review->update([
            'moderation_status' => $data['status'],
        ]);

        return back()->with('success', 'Đã cập nhật trạng thái review.');
    }
}
