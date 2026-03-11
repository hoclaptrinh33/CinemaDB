<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Language $language;

    protected function setUp(): void
    {
        parent::setUp();

        $this->language = Language::create([
            'iso_code' => 'en',
            'language_name' => 'English',
        ]);
    }

    private function title(array $overrides = []): Title
    {
        return Title::factory()->create(array_merge([
            'original_language_id' => $this->language->language_id,
            'visibility' => 'PUBLIC',
        ], $overrides));
    }

    public function test_hidden_reviews_are_excluded_from_public_title_page(): void
    {
        $title = $this->title(['title_name' => 'Visible Title']);
        $visibleUser = User::factory()->create(['username' => 'visible_user']);
        $hiddenUser = User::factory()->create(['username' => 'hidden_user']);

        Review::create([
            'title_id' => $title->title_id,
            'user_id' => $visibleUser->id,
            'rating' => 8,
            'review_text' => 'Visible review body',
            'has_spoilers' => false,
            'moderation_status' => 'VISIBLE',
        ]);

        Review::create([
            'title_id' => $title->title_id,
            'user_id' => $hiddenUser->id,
            'rating' => 4,
            'review_text' => 'Hidden review body',
            'has_spoilers' => false,
            'moderation_status' => 'HIDDEN',
        ]);

        $this->get(route('titles.show', $title->slug))
            ->assertOk()
            ->assertSee('Visible review body')
            ->assertDontSee('Hidden review body');
    }

    public function test_public_profile_top_reviews_only_include_visible_reviews(): void
    {
        $user = User::factory()->create(['username' => 'profile_owner']);
        $visibleTitle = $this->title(['title_name' => 'Profile Visible Title']);
        $hiddenTitle = $this->title(['title_name' => 'Profile Hidden Title']);

        Review::create([
            'title_id' => $visibleTitle->title_id,
            'user_id' => $user->id,
            'rating' => 9,
            'review_text' => 'Profile visible review',
            'has_spoilers' => false,
            'moderation_status' => 'VISIBLE',
            'helpful_votes' => 5,
        ]);

        Review::create([
            'title_id' => $hiddenTitle->title_id,
            'user_id' => $user->id,
            'rating' => 2,
            'review_text' => 'Profile hidden review',
            'has_spoilers' => false,
            'moderation_status' => 'HIDDEN',
            'helpful_votes' => 10,
        ]);

        $this->get(route('users.show', $user->username))
            ->assertOk()
            ->assertSee('Profile visible review')
            ->assertDontSee('Profile hidden review');
    }
}
