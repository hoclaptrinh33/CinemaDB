<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
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
        RateLimiter::clear('throttle');
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

    // ── Review store throttle ──────────────────────────────────────────────

    public function test_review_store_throttle_returns_429_after_limit(): void
    {
        $user  = $this->user();
        $title = $this->title();

        // First request should succeed (or redirect as review is created)
        $this->actingAs($user)
            ->post(route('reviews.store', $title), [
                'rating'       => 8,
                'review_text'  => 'Good film',
                'has_spoilers' => false,
            ])
            ->assertRedirect(); // success or duplicate-review redirect

        // For authenticated users, the throttle key is sha1($user->id).
        // The first request already registered 1 hit; add 19 more to reach 20.
        $key = sha1($user->getAuthIdentifier());
        for ($i = 0; $i < 19; $i++) {
            RateLimiter::hit($key, 60);
        }

        // Now the route should block with 429
        $this->actingAs($user)
            ->post(route('reviews.store', $title), [
                'rating'       => 7,
                'review_text'  => 'Another review',
                'has_spoilers' => false,
            ])
            ->assertStatus(429);
    }

    // ── Comment store throttle ─────────────────────────────────────────────

    public function test_comment_store_throttle_returns_429_after_limit(): void
    {
        $user  = $this->user();
        $title = $this->title();

        // For authenticated users, the throttle key is sha1($user->id).
        $key = sha1($user->getAuthIdentifier());
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit($key, 60);
        }

        $this->actingAs($user)
            ->post(route('comments.store', $title), [
                'content'      => 'Hello',
                'content_type' => 'text',
            ])
            ->assertStatus(429);
    }
}
