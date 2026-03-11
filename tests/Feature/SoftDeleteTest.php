<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
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

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['is_active' => true], $attrs));
    }

    private function title(): Title
    {
        return Title::factory()->create([
            'original_language_id' => $this->language->language_id,
            'visibility'           => 'PUBLIC',
        ]);
    }

    // ── Titles ─────────────────────────────────────────────────────────────

    public function test_deleting_title_soft_deletes_and_excludes_from_queries(): void
    {
        $title = $this->title();

        $title->delete();

        $this->assertSoftDeleted('titles', ['title_id' => $title->title_id]);
        $this->assertNull(Title::find($title->title_id));
        $this->assertNotNull(Title::withTrashed()->find($title->title_id));
    }

    public function test_soft_deleted_title_can_be_restored(): void
    {
        $title = $this->title();
        $title->delete();

        $title->restore();

        $this->assertNotNull(Title::find($title->title_id));
        $this->assertDatabaseHas('titles', [
            'title_id'   => $title->title_id,
            'deleted_at' => null,
        ]);
    }

    // ── Reviews ────────────────────────────────────────────────────────────

    public function test_deleting_review_soft_deletes(): void
    {
        Notification::fake();
        $user  = $this->user();
        $title = $this->title();

        $this->actingAs($user)
            ->post(route('reviews.store', $title), [
                'rating'       => 8,
                'review_text'  => 'Great film!',
                'has_spoilers' => false,
            ]);

        $review = Review::where('user_id', $user->id)->where('title_id', $title->title_id)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        $this->assertSoftDeleted('reviews', ['review_id' => $review->review_id]);
        $this->assertNull(Review::find($review->review_id));
    }

    public function test_soft_deleted_review_not_counted_in_title_listing(): void
    {
        Notification::fake();
        $user  = $this->user();
        $title = $this->title();

        $this->actingAs($user)->post(route('reviews.store', $title), [
            'rating'       => 9,
            'review_text'  => 'Excellent!',
            'has_spoilers' => false,
        ]);

        $review = Review::where('user_id', $user->id)->firstOrFail();
        $review->delete(); // soft delete

        // After soft-delete, the review should not be returned by default scope
        $this->assertCount(0, Review::where('title_id', $title->title_id)->get());
        $this->assertCount(1, Review::withTrashed()->where('title_id', $title->title_id)->get());
    }

    // ── Timestamps ─────────────────────────────────────────────────────────

    public function test_title_records_created_at_and_updated_at(): void
    {
        $title = $this->title();

        $this->assertNotNull($title->created_at);
        $this->assertNotNull($title->updated_at);
    }

    public function test_title_updated_at_changes_on_update(): void
    {
        $title = $this->title();
        $createdUpdatedAt = $title->updated_at;

        // Travel forward in time so the timestamp is guaranteed to change
        $this->travel(5)->seconds();
        $title->update(['title_name' => 'Updated Name']);

        $this->assertGreaterThan($createdUpdatedAt, $title->fresh()->updated_at);
    }
}
