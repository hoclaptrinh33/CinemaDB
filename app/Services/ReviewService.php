<?php

namespace App\Services;

use App\Events\HelpfulVoteToggled;
use App\Events\ReviewCreated;
use App\Events\ReviewDeleted;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Notifications\ReviewHelpful;
use App\Services\FeedService;
use App\Services\LevelUpService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function __construct(private FeedService $feedService) {}
    // ── Create ────────────────────────────────────────────────────────────

    /**
     * Tạo review mới và cộng điểm reputation.
     * - Review có text ≥ 50 từ : +10
     * - Review có text  < 50 từ : +3
     * - Chỉ rating (không text)  : +2
     */
    public function create(User $user, Title $title, array $data): Review
    {
        $points = $this->calcWritePoints($data['review_text'] ?? null);

        $existingReview = Review::withTrashed()
            ->where('user_id', $user->id)
            ->where('title_id', $title->title_id)
            ->first();

        if ($existingReview && ! $existingReview->trashed()) {
            throw ValidationException::withMessages([
                'review' => 'Bạn đã đánh giá title này rồi.',
            ]);
        }

        if ($existingReview) {
            DB::table('review_helpful_votes')
                ->where('review_id', $existingReview->review_id)
                ->delete();

            $existingReview->restore();
            $existingReview->update([
                'rating'            => $data['rating'],
                'review_text'       => $data['review_text'] ?? null,
                'has_spoilers'      => $data['has_spoilers'] ?? false,
                'moderation_status' => 'VISIBLE',
                'helpful_votes'     => 0,
                'reputation_earned' => $points,
                'created_at'        => now(),
            ]);

            $review = $existingReview->fresh();
        } else {
            $review = Review::create([
                'user_id'           => $user->id,
                'title_id'          => $title->title_id,
                'rating'            => $data['rating'],
                'review_text'       => $data['review_text'] ?? null,
                'has_spoilers'      => $data['has_spoilers'] ?? false,
                'reputation_earned' => $points,
            ]);
        }

        $user->increment('reputation', $points);

        LevelUpService::check($user, $user->reputation - $points);

        event(new ReviewCreated($review));

        $this->feedService->record(
            actorId: $user->id,
            activityType: 'review_created',
            subjectType: 'review',
            subjectId: $review->review_id,
            titleId: $title->title_id,
            metadata: [
                'title_name'   => $title->title_name,
                'title_slug'   => $title->slug,
                'poster_url'   => $title->poster_url,
                'title_type'   => $title->title_type,
                'rating'       => $review->rating,
                'has_spoilers' => $review->has_spoilers,
            ]
        );

        return $review;
    }

    // ── Update ────────────────────────────────────────────────────────────

    /**
     * Sửa review: cộng bù nếu user bổ sung text đủ dài (< 50 từ → ≥ 50 từ).
     */
    public function update(Review $review, array $data): Review
    {
        $oldPoints = $review->reputation_earned ?? 0;
        $newText   = $data['review_text'] ?? $review->review_text;
        $newPoints = $this->calcWritePoints($newText);
        $diff      = $newPoints - $oldPoints;

        $review->update([
            'rating'            => $data['rating'] ?? $review->rating,
            'review_text'       => $newText,
            'has_spoilers'      => $data['has_spoilers'] ?? $review->has_spoilers,
            'reputation_earned' => $newPoints,
        ]);

        if ($diff > 0) {
            $review->user->increment('reputation', $diff);
        }

        return $review->fresh();
    }

    // ── Delete ────────────────────────────────────────────────────────────

    /**
     * Xóa review do user tự xóa: trừ toàn bộ điểm đã kiếm.
     */
    public function delete(Review $review): void
    {
        $totalEarned = ($review->reputation_earned ?? 0)
            + ($review->helpful_votes * 5);

        $review->user->decrement('reputation', max(0, $totalEarned));

        // Clone trước khi xóa để event vẫn có dữ liệu
        $snapshot = clone $review;
        $review->delete();

        event(new ReviewDeleted($snapshot));
    }

    /**
     * Admin xóa và phạt: trừ toàn bộ + 50 điểm phạt.
     */
    public function adminDeleteWithPenalty(Review $review): void
    {
        $totalEarned = ($review->reputation_earned ?? 0)
            + ($review->helpful_votes * 5);

        $review->user->decrement('reputation', $totalEarned + 50);
        $review->delete();
    }

    // ── Helpful vote ──────────────────────────────────────────────────────

    /**
     * Toggle helpful vote. Tác giả review được +5 / −5 reputation.
     * Trả về ['voted' => bool, 'helpful_votes' => int].
     */
    public function voteHelpful(Review $review, User $voter): array
    {
        $alreadyVoted = DB::table('review_helpful_votes')
            ->where('review_id', $review->review_id)
            ->where('user_id', $voter->id)
            ->exists();

        if ($alreadyVoted) {
            DB::table('review_helpful_votes')
                ->where('review_id', $review->review_id)
                ->where('user_id', $voter->id)
                ->delete();

            $review->decrement('helpful_votes');
            $review->user->decrement('reputation', 5);

            event(new HelpfulVoteToggled($review->fresh(), added: false));

            return [
                'voted'         => false,
                'helpful_votes' => $review->fresh()->helpful_votes,
            ];
        }

        DB::table('review_helpful_votes')->insert([
            'review_id' => $review->review_id,
            'user_id'   => $voter->id,
            'voted_at'  => now(),
        ]);

        $review->increment('helpful_votes');
        $review->user->increment('reputation', 5);

        // Notify review author (grouped per review) + check level-up
        $author   = $review->user;
        $groupKey = "review_helpful:{$review->review_id}";
        $existing = $author->unreadNotifications()
            ->where('type', ReviewHelpful::class)
            ->where('data->group_key', $groupKey)
            ->first();

        if ($existing) {
            $data         = $existing->data;
            $actors       = $data['actor_names'] ?? [];
            if (! in_array($voter->username, $actors)) {
                $actors[] = $voter->username;
            }
            $existing->update([
                'data' => array_merge($data, [
                    'actor_names' => $actors,
                    'count'       => count($actors),
                ]),
            ]);
        } elseif ($author->id !== $voter->id) {
            $author->notify(new ReviewHelpful($review->fresh(), $voter));
        }

        LevelUpService::check($author, $author->reputation - 5);

        event(new HelpfulVoteToggled($review->fresh(), added: true));

        return [
            'voted'         => true,
            'helpful_votes' => $review->fresh()->helpful_votes,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function calcWritePoints(?string $text): int
    {
        if (blank($text)) {
            return 2; // chỉ thả sao
        }

        $wordCount = str_word_count(strip_tags($text));

        return $wordCount >= 50 ? 10 : 3;
    }
}
