<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
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

    private function userWithRole(string $role, bool $active = true): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => $active]);
    }

    // ── Admin routes — only ADMIN allowed ───────────────────────────────────

    public function test_regular_user_gets_403_on_admin_dashboard(): void
    {
        $this->actingAs($this->userWithRole('USER'))
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    public function test_moderator_gets_403_on_admin_dashboard(): void
    {
        $this->actingAs($this->userWithRole('MODERATOR'))
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs($this->userWithRole('ADMIN'))
            ->get(route('admin.dashboard'))
            ->assertStatus(200);
    }

    public function test_regular_user_gets_403_on_admin_titles(): void
    {
        $this->actingAs($this->userWithRole('USER'))
            ->get(route('admin.titles.index'))
            ->assertStatus(403);
    }

    public function test_moderator_gets_403_on_admin_titles(): void
    {
        $this->actingAs($this->userWithRole('MODERATOR'))
            ->get(route('admin.titles.index'))
            ->assertStatus(403);
    }

    public function test_regular_user_gets_403_on_admin_users(): void
    {
        $this->actingAs($this->userWithRole('USER'))
            ->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    // ── Moderate routes — ADMIN and MODERATOR allowed ────────────────────────

    public function test_moderator_can_access_moderate_reviews(): void
    {
        $this->actingAs($this->userWithRole('MODERATOR'))
            ->get(route('moderate.reviews.index'))
            ->assertStatus(200);
    }

    public function test_admin_can_access_moderate_reviews(): void
    {
        $this->actingAs($this->userWithRole('ADMIN'))
            ->get(route('moderate.reviews.index'))
            ->assertStatus(200);
    }

    public function test_regular_user_gets_403_on_moderate_reviews(): void
    {
        $this->actingAs($this->userWithRole('USER'))
            ->get(route('moderate.reviews.index'))
            ->assertStatus(403);
    }

    public function test_moderator_can_access_audit_logs(): void
    {
        $this->actingAs($this->userWithRole('MODERATOR'))
            ->get(route('moderate.audit-logs.index'))
            ->assertStatus(200);
    }

    // ── Inactive user is redirected even with correct role ────────────────────

    public function test_inactive_admin_is_redirected_from_admin_routes(): void
    {
        $this->actingAs($this->userWithRole('ADMIN', false))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_inactive_moderator_is_redirected_from_moderate_routes(): void
    {
        $this->actingAs($this->userWithRole('MODERATOR', false))
            ->get(route('moderate.reviews.index'))
            ->assertRedirect(route('login'));
    }

    // ── Admin store/destroy are also protected ────────────────────────────────

    public function test_regular_user_cannot_delete_title_via_admin_route(): void
    {
        $title = Title::factory()->create([
            'original_language_id' => $this->language->language_id,
        ]);

        $this->actingAs($this->userWithRole('USER'))
            ->delete(route('admin.titles.destroy', $title))
            ->assertStatus(403);
    }

    public function test_moderator_cannot_delete_title_via_admin_route(): void
    {
        $title = Title::factory()->create([
            'original_language_id' => $this->language->language_id,
        ]);

        $this->actingAs($this->userWithRole('MODERATOR'))
            ->delete(route('admin.titles.destroy', $title))
            ->assertStatus(403);
    }
}
