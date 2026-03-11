<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleCrudTest extends TestCase
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

    private function admin(): User
    {
        return User::factory()->create(['role' => 'ADMIN', 'is_active' => true]);
    }

    private function regularUser(): User
    {
        return User::factory()->create(['role' => 'USER', 'is_active' => true]);
    }

    private function makeTitleData(array $overrides = []): array
    {
        return array_merge([
            'title_name'           => 'Test Movie',
            'title_type'           => 'MOVIE',
            'release_date'         => '2024-01-15',
            'runtime_mins'         => 120,
            'description'          => 'A test movie.',
            'original_language_id' => $this->language->language_id,
            'status'               => 'Released',
            'visibility'           => 'PUBLIC',
        ], $overrides);
    }

    // ── Guest redirected ────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_admin_titles_index(): void
    {
        $response = $this->get(route('admin.titles.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_when_creating_title(): void
    {
        $response = $this->post(route('admin.titles.store'), $this->makeTitleData());
        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_when_updating_title(): void
    {
        $title = Title::factory()->create([
            'original_language_id' => $this->language->language_id,
        ]);
        $response = $this->patch(route('admin.titles.update', $title), ['title_name' => 'Updated']);
        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_when_deleting_title(): void
    {
        $title = Title::factory()->create([
            'original_language_id' => $this->language->language_id,
        ]);
        $response = $this->delete(route('admin.titles.destroy', $title));
        $response->assertRedirect(route('login'));
    }

    // ── Regular user get 403 ────────────────────────────────────────────────

    public function test_regular_user_cannot_access_admin_titles(): void
    {
        $response = $this->actingAs($this->regularUser())
            ->get(route('admin.titles.index'));
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_create_title(): void
    {
        $response = $this->actingAs($this->regularUser())
            ->post(route('admin.titles.store'), $this->makeTitleData());
        $response->assertStatus(403);
    }

    // ── Admin CRUD ──────────────────────────────────────────────────────────

    public function test_admin_can_view_titles_index(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.titles.index'))
            ->assertStatus(200);
    }

    public function test_admin_can_create_title(): void
    {
        $admin = $this->admin();
        $data  = $this->makeTitleData(['title_name' => 'Inception']);

        $response = $this->actingAs($admin)->post(route('admin.titles.store'), $data);

        $response->assertRedirect(route('admin.titles.index'));
        $this->assertDatabaseHas('titles', ['title_name' => 'Inception']);
    }

    public function test_admin_can_update_title(): void
    {
        $admin = $this->admin();
        $title = Title::factory()->create([
            'title_name'           => 'Old Name',
            'original_language_id' => $this->language->language_id,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.titles.update', $title), array_merge(
                $this->makeTitleData(),
                ['title_name' => 'New Name']
            ));

        $response->assertRedirect();
        $this->assertDatabaseHas('titles', ['title_id' => $title->title_id, 'title_name' => 'New Name']);
    }

    public function test_admin_can_delete_title(): void
    {
        $admin = $this->admin();
        $title = Title::factory()->create([
            'original_language_id' => $this->language->language_id,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.titles.destroy', $title));

        $response->assertRedirect(route('admin.titles.index'));
        $this->assertSoftDeleted('titles', ['title_id' => $title->title_id]);
    }

    public function test_admin_create_title_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('admin.titles.store'), []);

        $response->assertSessionHasErrors(['title_name', 'title_type', 'visibility']);
    }
}
