<?php

namespace Tests\Feature;

use App\Events\HelpfulVoteToggled;
use App\Events\ReviewCreated;
use App\Listeners\AwardBadgesOnReview;
use App\Listeners\UpdateReputationOnHelpful;
use App\Models\Badge;
use App\Models\Language;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use App\Notifications\BadgeEarned;
use App\Notifications\LevelUp;
use App\Services\ReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();
        $this->language = Language::create([
            'iso_code'      => 'en',
            'language_name' => 'English',
        ]);
        // Run listeners synchronously in tests (no queue)
        Queue::fake();
        Notification::fake();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['is_active' => true, 'reputation' => 0], $attrs));
    }

    private function verifiedUser(array $attrs = []): User
    {
        return $this->user(array_merge(['email_verified_at' => now()], $attrs));
    }

    private function title(): Title
    {
        return Title::factory()->create([
            'original_language_id' => $this->language->language_id,
            'visibility'           => 'PUBLIC',
        ]);
    }

    // ── Reputation on review ────────────────────────────────────────────────

    public function test_posting_short_review_earns_3_reputation(): void
    {
        $user  = $this->user();
        $title = $this->title();

        app(ReviewService::class)->create($user, $title, [
            'rating'      => 7,
            'review_text' => 'Short text.',
            'has_spoilers' => false,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'reputation' => 3]);
    }

    public function test_posting_long_review_earns_10_reputation(): void
    {
        $user  = $this->user();
        $title = $this->title();

        // Generate text with > 50 words
        $longText = str_repeat('word ', 60);

        app(ReviewService::class)->create($user, $title, [
            'rating'      => 8,
            'review_text' => $longText,
            'has_spoilers' => false,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'reputation' => 10]);
    }

    public function test_rating_only_review_earns_2_reputation(): void
    {
        $user  = $this->user();
        $title = $this->title();

        app(ReviewService::class)->create($user, $title, [
            'rating'      => 6,
            'review_text' => null,
            'has_spoilers' => false,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'reputation' => 2]);
    }

    // ── Reputation on helpful vote ──────────────────────────────────────────

    public function test_receiving_helpful_vote_earns_5_reputation(): void
    {
        $author = $this->user();
        $voter  = $this->user();
        $title  = $this->title();

        $review = Review::factory()->create([
            'user_id'  => $author->id,
            'title_id' => $title->title_id,
        ]);

        $this->actingAs($voter)
            ->post(route('reviews.helpful', $review->review_id))
            ->assertRedirect();

        $author->refresh();
        $this->assertEquals(5, $author->reputation);
    }

    public function test_removing_helpful_vote_deducts_5_reputation(): void
    {
        $author = $this->user(['reputation' => 10]);
        $voter  = $this->user();
        $title  = $this->title();

        $review = Review::factory()->create([
            'user_id'  => $author->id,
            'title_id' => $title->title_id,
        ]);

        // Vote, then unvote
        $this->actingAs($voter)->post(route('reviews.helpful', $review->review_id));
        $this->actingAs($voter)->post(route('reviews.helpful', $review->review_id));

        $author->refresh();
        $this->assertEquals(10, $author->reputation);
    }

    // ── Badge awarding ──────────────────────────────────────────────────────

    public function test_badge_awarded_when_review_count_threshold_met(): void
    {
        $user  = $this->user();
        $badge = Badge::factory()->reviewCount(1)->bronze()->create();

        $title  = $this->title();
        $review = Review::factory()->create([
            'user_id'  => $user->id,
            'title_id' => $title->title_id,
        ]);

        // Dispatch listener directly (synchronous)
        $listener = app(AwardBadgesOnReview::class);
        $listener->handle(new ReviewCreated($review));

        $this->assertTrue(
            $user->badges()->where('badges.badge_id', $badge->badge_id)->exists()
        );
    }

    public function test_badge_not_awarded_when_threshold_not_met(): void
    {
        $user  = $this->user();
        $badge = Badge::factory()->reviewCount(5)->bronze()->create();  // Need 5 reviews

        $title  = $this->title();
        $review = Review::factory()->create([
            'user_id'  => $user->id,
            'title_id' => $title->title_id,
        ]);

        $listener = app(AwardBadgesOnReview::class);
        $listener->handle(new ReviewCreated($review));

        $this->assertFalse(
            $user->badges()->where('badges.badge_id', $badge->badge_id)->exists()
        );
    }

    public function test_badge_awarded_only_once_for_same_achievement(): void
    {
        $user  = $this->user();
        $badge = Badge::factory()->reviewCount(1)->bronze()->create();
        $title = $this->title();

        $review1 = Review::factory()->create(['user_id' => $user->id, 'title_id' => $title->title_id]);
        $title2  = $this->title();
        $review2 = Review::factory()->create(['user_id' => $user->id, 'title_id' => $title2->title_id]);

        $listener = app(AwardBadgesOnReview::class);
        $listener->handle(new ReviewCreated($review1));
        $listener->handle(new ReviewCreated($review2));

        $count = $user->badges()->where('badges.badge_id', $badge->badge_id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_helpful_vote_badge_awarded_when_threshold_met(): void
    {
        $author = $this->user();
        $badge  = Badge::factory()->helpfulVotes(1)->silver()->create();

        $title  = $this->title();
        $review = Review::factory()->create([
            'user_id'       => $author->id,
            'title_id'      => $title->title_id,
            'helpful_votes' => 1,
        ]);

        $fakeReview = Review::find($review->review_id);
        $listener   = app(UpdateReputationOnHelpful::class);
        $listener->handle(new HelpfulVoteToggled($fakeReview, added: true));

        $this->assertTrue(
            $author->badges()->where('badges.badge_id', $badge->badge_id)->exists()
        );
    }

    // ── LevelUp notification ────────────────────────────────────────────────

    public function test_level_up_notification_sent_when_level_changes(): void
    {
        // Level 3 → Level 4: boundary is 60 (label: Thợ cày phim).
        // Start at 55 (level 3), add 10 → 65 (level 4).
        $user = $this->user(['reputation' => 55]);

        $user->increment('reputation', 10);
        \App\Services\LevelUpService::check($user, 55);

        Notification::assertSentTo($user, LevelUp::class, function (LevelUp $notification) use ($user) {
            $data = $notification->toDatabase($user);

            return $data['level'] === 4
                && $data['rank_label'] === 'Thợ cày phim';
        });
    }

    public function test_no_level_up_notification_within_same_level(): void
    {
        $user = $this->user(['reputation' => 60]);

        $user->increment('reputation', 5);
        \App\Services\LevelUpService::check($user, 60);

        Notification::assertNotSentTo($user, LevelUp::class);
    }

    public function test_level_up_notification_uses_latest_level_after_multi_level_jump(): void
    {
        // Level 8: min=380 (label: Kẻ sành phim). Start at 50 (level 3), add 350 → 400.
        $user = $this->user(['reputation' => 50]);

        $user->increment('reputation', 350);
        \App\Services\LevelUpService::check($user, 50);

        Notification::assertSentTo($user, LevelUp::class, function (LevelUp $notification) use ($user) {
            $data = $notification->toDatabase($user);

            return $data['level'] === 8
                && $data['rank_label'] === 'Kẻ sành phim';
        });
    }

    // ── Badge notification ──────────────────────────────────────────────────

    public function test_badge_earned_notification_is_sent(): void
    {
        $user  = $this->user();
        $badge = Badge::factory()->reviewCount(1)->bronze()->create();

        $title  = $this->title();
        $review = Review::factory()->create([
            'user_id'  => $user->id,
            'title_id' => $title->title_id,
        ]);

        $listener = app(AwardBadgesOnReview::class);
        $listener->handle(new ReviewCreated($review));

        Notification::assertSentTo($user, BadgeEarned::class);
    }

    public function test_daily_visit_reward_is_only_granted_once_per_day_and_updates_streak(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertStatus(200);

        $user->refresh();
        $this->assertSame(1, $user->reputation);
        $this->assertSame(1, $user->current_streak_days);
        $this->assertSame(1, $user->longest_streak_days);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertStatus(200);

        $user->refresh();
        $this->assertSame(1, $user->reputation);
        $this->assertSame(1, $user->current_streak_days);

        $this->travel(1)->days();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertStatus(200);

        $user->refresh();
        $this->assertSame(2, $user->reputation);
        $this->assertSame(2, $user->current_streak_days);
        $this->assertSame(2, $user->longest_streak_days);
    }

    public function test_following_user_can_unlock_follower_badge(): void
    {
        $followee = $this->verifiedUser();
        $follower = $this->verifiedUser();

        $badge = Badge::create([
            'slug' => 'first-follower-test',
            'name' => 'Tiếng nói đầu tiên',
            'tier' => 'WOOD',
            'category' => 'social',
            'badge_family' => 'followers-milestones',
            'badge_stage' => 1,
            'rarity_tier' => 'COMMON',
            'frame_style' => 'plain',
            'sort_order' => 1,
            'condition_type' => 'follower_count',
            'condition_value' => 1,
        ]);

        $this->actingAs($follower)
            ->post(route('users.follow', $followee))
            ->assertRedirect();

        $followee->refresh();

        $this->assertSame(3, $followee->reputation);
        $this->assertDatabaseHas('user_badges', [
            'user_id' => $followee->id,
            'badge_id' => $badge->badge_id,
            'earned_reputation_bonus' => 3,
        ]);
    }

    public function test_collection_nomination_rewards_owner_and_awards_badge(): void
    {
        $owner = $this->verifiedUser();
        $nominator = $this->verifiedUser();

        $badge = Badge::create([
            'slug' => 'spotlight-curator-test',
            'name' => 'Spotlight Curator',
            'tier' => 'WOOD',
            'category' => 'collections',
            'badge_family' => 'collection-nomination-milestones',
            'badge_stage' => 1,
            'rarity_tier' => 'COMMON',
            'frame_style' => 'plain',
            'sort_order' => 1,
            'condition_type' => 'collection_nominations_received',
            'condition_value' => 1,
        ]);

        $collection = \App\Models\Collection::factory()
            ->for($owner)
            ->published()
            ->create(['nomination_count' => 0]);

        $this->actingAs($nominator)
            ->post(route('collections.nominate', $collection->slug))
            ->assertRedirect();

        $owner->refresh();
        $collection->refresh();

        $this->assertSame(1, $collection->nomination_count);
        $this->assertSame(5, $owner->reputation);
        $this->assertDatabaseHas('user_badges', [
            'user_id' => $owner->id,
            'badge_id' => $badge->badge_id,
            'earned_reputation_bonus' => 3,
            'earned_snapshot_value' => 1,
        ]);
    }
}
