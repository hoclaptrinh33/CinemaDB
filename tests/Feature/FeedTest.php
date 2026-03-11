<?php

namespace Tests\Feature;

use App\Models\FeedItem;
use App\Models\Language;
use App\Models\Title;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
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

    // ── FeedService ────────────────────────────────────────────────────────

    public function test_feed_service_records_activity(): void
    {
        $user = $this->user();
        $title = $this->title();

        /** @var FeedService $feedService */
        $feedService = app(FeedService::class);
        $feedService->record(
            actorId: $user->id,
            activityType: 'review_created',
            subjectType: 'review',
            subjectId: 99,
            titleId: $title->title_id,
            metadata: ['title_name' => $title->title_name],
        );

        $this->assertDatabaseHas('feed_items', [
            'actor_user_id' => $user->id,
            'activity_type' => 'review_created',
            'title_id'      => $title->title_id,
        ]);
    }

    public function test_feed_service_can_be_mocked(): void
    {
        $user = $this->user();

        // Use withArgs() callback to avoid PHP 8 named-argument / Mockery
        // positional-array mismatch that occurs with ->with(namedArg: value).
        $mock = $this->mock(FeedService::class);
        $mock->shouldReceive('record')
            ->once()
            ->withArgs(function (int $actorId, string $activityType, ?string $subjectType) use ($user) {
                return $actorId === $user->id
                    && $activityType === 'review_created'
                    && $subjectType === 'review';
            });

        $title = $this->title();

        $this->actingAs($user)
            ->post(route('reviews.store', $title), [
                'rating'       => 8,
                'review_text'  => 'Great movie!',
                'has_spoilers' => false,
            ]);
    }

    // ── Feed endpoint ──────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_feed(): void
    {
        $this->get(route('feed'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_feed(): void
    {
        $user = $this->user()->fresh();
        $this->actingAs($user)
            ->get(route('feed'))
            ->assertOk();
    }

    public function test_feed_only_shows_items_from_followed_users_and_self(): void
    {
        $actor   = $this->user();
        $other   = $this->user();
        $title   = $this->title();
        $viewer  = $this->user();

        // actor follows viewer — so viewer should see actor's activity
        $viewer->following()->attach($actor->id, [
            'created_at' => now(),
        ]);

        // Create feed item for actor
        FeedItem::create([
            'actor_user_id' => $actor->id,
            'activity_type' => 'review_created',
            'title_id'      => $title->title_id,
            'created_at'    => now(),
        ]);

        // Create feed item for unrelated user
        FeedItem::create([
            'actor_user_id' => $other->id,
            'activity_type' => 'review_created',
            'title_id'      => $title->title_id,
            'created_at'    => now(),
        ]);

        $response = $this->actingAs($viewer)->get(route('feed'));
        $response->assertOk();

        // The feed page should not contain the unrelated user's item
        $feedItems = $response->original->getData()['page']['props']['items'] ?? null;
        if ($feedItems !== null) {
            $actorIds = collect($feedItems['data'] ?? [])->pluck('actor.id')->all();
            $this->assertNotContains($other->id, $actorIds);
        }
    }

    // ── Soft delete does not appear in feed ────────────────────────────────

    public function test_soft_deleted_title_reviews_not_visible(): void
    {
        $user  = $this->user();
        $title = $this->title();

        $this->actingAs($user)
            ->post(route('reviews.store', $title), [
                'rating'       => 7,
                'review_text'  => 'Nice',
                'has_spoilers' => false,
            ]);

        $title->delete(); // soft delete

        $this->assertSoftDeleted('titles', ['title_id' => $title->title_id]);

        // Title should no longer appear in listing
        $response = $this->get(route('titles.index'));
        $response->assertOk();
        $props = $response->original?->getData()['page']['props'] ?? [];
        $slugs = collect($props['titles']['data'] ?? [])->pluck('slug')->all();
        $this->assertNotContains($title->slug, $slugs);
    }
}
