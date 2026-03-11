<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function createTitle(): Title
    {
        return Title::factory()->create([
            'original_language_id' => $this->language->language_id,
            'visibility'           => 'PUBLIC',
        ]);
    }

    private function reviewPayload(array $overrides = []): array
    {
        return array_merge([
            'rating'      => 8,
            'review_text' => 'Great movie!',
            'has_spoilers' => false,
        ], $overrides);
    }

    // ── Guest cannot review ─────────────────────────────────────────────────

    public function test_guest_cannot_post_a_review(): void
    {
        $title = $this->createTitle();
        $response = $this->post(route('reviews.store', $title), $this->reviewPayload());
        $response->assertRedirect(route('login'));
    }

    // ── Authenticated user can review ────────────────────────────────────────

    public function test_active_user_can_post_a_review(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $title = $this->createTitle();

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $title), $this->reviewPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'title_id' => $title->title_id,
            'user_id'  => $user->id,
            'rating'   => 8,
        ]);
    }

    // ── Inactive user cannot review ─────────────────────────────────────────

    public function test_inactive_user_cannot_post_a_review(): void
    {
        $user  = User::factory()->create(['is_active' => false]);
        $title = $this->createTitle();

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $title), $this->reviewPayload());

        $response->assertStatus(403);
        $this->assertDatabaseCount('reviews', 0);
    }

    // ── Duplicate review blocked ─────────────────────────────────────────────

    public function test_user_cannot_post_two_reviews_for_same_title(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $title = $this->createTitle();

        // First review
        $this->actingAs($user)
            ->post(route('reviews.store', $title), $this->reviewPayload(['rating' => 7]));

        // Second review attempt
        $response = $this->actingAs($user)
            ->post(route('reviews.store', $title), $this->reviewPayload(['rating' => 5]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Database still has only one review
        $this->assertDatabaseCount('reviews', 1);
        $this->assertEquals(7, Review::first()->rating);
    }

    // ── Review validation ────────────────────────────────────────────────────

    public function test_review_rating_must_be_between_1_and_10(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $title = $this->createTitle();

        // Rating 0 is invalid
        $this->actingAs($user)
            ->post(route('reviews.store', $title), $this->reviewPayload(['rating' => 0]))
            ->assertSessionHasErrors('rating');

        // Rating 11 is invalid
        $this->actingAs($user)
            ->post(route('reviews.store', $title), $this->reviewPayload(['rating' => 11]))
            ->assertSessionHasErrors('rating');
    }

    // ── User can update own review ────────────────────────────────────────────

    public function test_user_can_update_own_review(): void
    {
        $user   = User::factory()->create(['is_active' => true]);
        $title  = $this->createTitle();
        $review = Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $user->id,
            'rating'      => 6,
            'review_text' => 'Decent.',
            'has_spoilers' => false,
        ]);

        $response = $this->actingAs($user)
            ->patch(route('reviews.update', $review), [
                'rating'      => 9,
                'review_text' => 'Actually great!',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', ['review_id' => $review->review_id, 'rating' => 9]);
    }

    // ── User cannot update another user's review ─────────────────────────────

    public function test_user_cannot_update_another_users_review(): void
    {
        $owner  = User::factory()->create(['is_active' => true]);
        $other  = User::factory()->create(['is_active' => true]);
        $title  = $this->createTitle();
        $review = Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $owner->id,
            'rating'      => 6,
            'has_spoilers' => false,
        ]);

        $this->actingAs($other)
            ->patch(route('reviews.update', $review), ['rating' => 1, 'review_text' => ''])
            ->assertStatus(403);
    }

    // ── User can delete own review ────────────────────────────────────────────

    public function test_user_can_delete_own_review(): void
    {
        $user   = User::factory()->create(['is_active' => true]);
        $title  = $this->createTitle();
        $review = Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $user->id,
            'rating'      => 7,
            'has_spoilers' => false,
        ]);

        $this->actingAs($user)
            ->delete(route('reviews.destroy', $review))
            ->assertRedirect();

        $this->assertSoftDeleted('reviews', ['review_id' => $review->review_id]);
    }

    public function test_user_can_create_a_new_review_after_soft_deleting_the_previous_one(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $title = $this->createTitle();

        $this->actingAs($user)
            ->post(route('reviews.store', $title), $this->reviewPayload([
                'rating' => 7,
                'review_text' => 'First pass review',
            ]))
            ->assertRedirect();

        $review = Review::where('user_id', $user->id)->where('title_id', $title->title_id)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('reviews.destroy', $review))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('reviews.store', $title), $this->reviewPayload([
                'rating' => 9,
                'review_text' => 'Second pass review',
            ]))
            ->assertRedirect();

        $this->assertEquals(1, Review::withTrashed()->where('user_id', $user->id)->where('title_id', $title->title_id)->count());
        $this->assertDatabaseHas('reviews', [
            'review_id' => $review->review_id,
            'rating' => 9,
            'review_text' => 'Second pass review',
            'deleted_at' => null,
            'moderation_status' => 'VISIBLE',
            'helpful_votes' => 0,
        ]);
    }

    public function test_admin_delete_uses_penalty_flow(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN', 'is_active' => true]);
        $author = User::factory()->create(['reputation' => 100, 'is_active' => true]);
        $title = $this->createTitle();
        $review = Review::create([
            'title_id' => $title->title_id,
            'user_id' => $author->id,
            'rating' => 8,
            'review_text' => 'Penalty path review',
            'has_spoilers' => false,
            'helpful_votes' => 2,
            'reputation_earned' => 3,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.reviews.destroy', $review))
            ->assertRedirect();

        $author->refresh();

        $this->assertSoftDeleted('reviews', ['review_id' => $review->review_id]);
        $this->assertSame(37, $author->reputation);
    }
}
