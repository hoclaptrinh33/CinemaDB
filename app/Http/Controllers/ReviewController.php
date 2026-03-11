<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use App\Models\Title;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    public function store(StoreReviewRequest $request, Title $title): RedirectResponse
    {
        $this->authorize('create', Review::class);
        $user = $request->user();

        // Prevent duplicate reviews
        $existing = Review::withTrashed()
            ->where('title_id', $title->title_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && ! $existing->trashed()) {
            return back()->with('error', 'Bạn đã đánh giá title này rồi.');
        }

        $this->reviewService->create($user, $title, $request->validated());

        return back()->with('success', 'Đánh giá đã được gửi.');
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $data = $request->validate([
            'rating'      => ['required', 'integer', 'min:1', 'max:10'],
            'review_text' => ['nullable', 'string', 'max:5000'],
            'has_spoilers' => ['sometimes', 'boolean'],
        ]);

        $this->reviewService->update($review, $data);

        return back()->with('success', 'Cập nhật review thành công.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $this->reviewService->delete($review);

        return back()->with('success', 'Đã xoá review.');
    }

    public function helpful(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('voteHelpful', $review);

        $result = $this->reviewService->voteHelpful($review, $request->user());

        $message = $result['voted'] ? 'Đã vote helpful.' : 'Đã bỏ vote helpful.';

        return back()->with('success', $message);
    }
}
