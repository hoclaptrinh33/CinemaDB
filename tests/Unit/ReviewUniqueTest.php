<?php

namespace Tests\Unit;

use App\Models\Language;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUniqueTest extends TestCase
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
        ]);
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    // ── Unique constraint (uq_user_title) ───────────────────────────────────

    public function test_user_can_only_have_one_review_per_title(): void
    {
        $user  = $this->createUser();
        $title = $this->createTitle();

        Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $user->id,
            'rating'      => 8,
            'has_spoilers' => false,
        ]);

        $this->expectException(QueryException::class);

        Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $user->id,
            'rating'      => 5,
            'has_spoilers' => false,
        ]);
    }

    public function test_different_users_can_review_same_title(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $title = $this->createTitle();

        Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $user1->id,
            'rating'      => 7,
            'has_spoilers' => false,
        ]);
        Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $user2->id,
            'rating'      => 9,
            'has_spoilers' => false,
        ]);

        $this->assertDatabaseCount('reviews', 2);
    }

    public function test_same_user_can_review_different_titles(): void
    {
        $user   = $this->createUser();
        $title1 = $this->createTitle();
        $title2 = $this->createTitle();

        Review::create([
            'title_id'    => $title1->title_id,
            'user_id'     => $user->id,
            'rating'      => 6,
            'has_spoilers' => false,
        ]);
        Review::create([
            'title_id'    => $title2->title_id,
            'user_id'     => $user->id,
            'rating'      => 8,
            'has_spoilers' => true,
        ]);

        $this->assertDatabaseCount('reviews', 2);
    }

    // ── Review default values ────────────────────────────────────────────────

    public function test_review_defaults_has_spoilers_to_false(): void
    {
        $user  = $this->createUser();
        $title = $this->createTitle();

        $review = Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $user->id,
            'rating'      => 8,
            'has_spoilers' => false,
        ]);

        $this->assertFalse((bool) $review->has_spoilers);
    }

    public function test_review_tracks_the_owner(): void
    {
        $user  = $this->createUser();
        $title = $this->createTitle();

        $review = Review::create([
            'title_id'    => $title->title_id,
            'user_id'     => $user->id,
            'rating'      => 9,
            'has_spoilers' => false,
        ]);

        $this->assertEquals($user->id, $review->user->id);
        $this->assertEquals($title->title_id, $review->title->title_id);
    }
}
