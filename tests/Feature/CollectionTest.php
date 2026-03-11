<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\CollectionCollaborator;
use App\Models\Language;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
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

    // ── Guest access ────────────────────────────────────────────────────────

    public function test_guest_cannot_create_collection(): void
    {
        $this->post(route('collections.store'), ['name' => 'My List'])
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_view_private_collection(): void
    {
        $owner      = $this->user();
        $collection = Collection::factory()->private()->create(['user_id' => $owner->id]);

        $this->get(route('collections.show', $collection->slug))
            ->assertForbidden();
    }

    // ── Create ──────────────────────────────────────────────────────────────

    public function test_user_can_create_a_private_collection(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('collections.store'), [
                'name'       => 'My Favourites',
                'visibility' => 'PRIVATE',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'user_id'    => $user->id,
            'name'       => 'My Favourites',
            'visibility' => 'PRIVATE',
        ]);
    }

    public function test_user_can_create_a_public_collection(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('collections.store'), [
                'name'       => 'Best Sci-Fi',
                'visibility' => 'PUBLIC',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'user_id'    => $user->id,
            'name'       => 'Best Sci-Fi',
            'visibility' => 'PUBLIC',
        ]);
    }

    // ── Privacy ─────────────────────────────────────────────────────────────

    public function test_public_collection_is_visible_to_anyone(): void
    {
        $owner      = $this->user();
        $collection = Collection::factory()->public()->create(['user_id' => $owner->id]);

        $this->get(route('collections.show', $collection->slug))
            ->assertOk();
    }

    public function test_private_collection_is_visible_to_owner(): void
    {
        $owner      = $this->user();
        $collection = Collection::factory()->private()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('collections.show', $collection->slug))
            ->assertOk();
    }

    public function test_private_collection_is_hidden_from_other_users(): void
    {
        $owner      = $this->user();
        $other      = $this->user();
        $collection = Collection::factory()->private()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('collections.show', $collection->slug))
            ->assertForbidden();
    }

    // ── Add / Remove titles ─────────────────────────────────────────────────

    public function test_owner_can_add_title_to_collection(): void
    {
        $owner      = $this->user();
        $collection = Collection::factory()->create(['user_id' => $owner->id]);
        $title      = $this->title();

        $this->actingAs($owner)
            ->post(route('collections.titles.add', $collection->slug), [
                'title_id' => $title->title_id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collection_titles', [
            'collection_id' => $collection->collection_id,
            'title_id'      => $title->title_id,
        ]);
    }

    public function test_non_member_cannot_add_title_to_collection(): void
    {
        $owner      = $this->user();
        $other      = $this->user();
        $collection = Collection::factory()->create(['user_id' => $owner->id]);
        $title      = $this->title();

        $this->actingAs($other)
            ->post(route('collections.titles.add', $collection->slug), [
                'title_id' => $title->title_id,
            ])
            ->assertForbidden();
    }

    public function test_owner_can_remove_title_from_collection(): void
    {
        $owner      = $this->user();
        $collection = Collection::factory()->create(['user_id' => $owner->id]);
        $title      = $this->title();

        $collection->titles()->attach($title->title_id, ['added_at' => now()]);

        $this->actingAs($owner)
            ->delete(route('collections.titles.remove', [$collection->slug, $title->title_id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('collection_titles', [
            'collection_id' => $collection->collection_id,
            'title_id'      => $title->title_id,
        ]);
    }

    // ── Update / Delete ─────────────────────────────────────────────────────

    public function test_owner_can_update_collection_metadata(): void
    {
        $owner      = $this->user();
        $collection = Collection::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->patch(route('collections.update', $collection->slug), [
                'name'        => 'Updated Name',
                'description' => 'New description',
                'visibility'  => 'PUBLIC',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'collection_id' => $collection->collection_id,
            'name'          => 'Updated Name',
        ]);
    }

    public function test_other_user_cannot_update_collection(): void
    {
        $owner      = $this->user();
        $other      = $this->user();
        $collection = Collection::factory()->public()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->patch(route('collections.update', $collection->slug), ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_owner_can_delete_collection(): void
    {
        $owner      = $this->user();
        $collection = Collection::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('collections.destroy', $collection->slug))
            ->assertRedirect();

        $this->assertDatabaseMissing('collections', ['collection_id' => $collection->collection_id]);
    }

    public function test_other_user_cannot_delete_collection(): void
    {
        $owner      = $this->user();
        $other      = $this->user();
        $collection = Collection::factory()->public()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->delete(route('collections.destroy', $collection->slug))
            ->assertForbidden();
    }

    // ── Collaborators ───────────────────────────────────────────────────────

    public function test_collaborator_can_see_private_collection(): void
    {
        $owner      = $this->user();
        $collab     = $this->user();
        $collection = Collection::factory()->private()->create(['user_id' => $owner->id]);

        CollectionCollaborator::create([
            'collection_id' => $collection->collection_id,
            'user_id'       => $collab->id,
            'invited_by'    => $owner->id,
            'accepted_at'   => now(),
        ]);

        $this->actingAs($collab)
            ->get(route('collections.show', $collection->slug))
            ->assertOk();
    }

    public function test_collaborator_can_add_title(): void
    {
        $owner      = $this->user();
        $collab     = $this->user();
        $collection = Collection::factory()->public()->create(['user_id' => $owner->id]);
        $title      = $this->title();

        CollectionCollaborator::create([
            'collection_id' => $collection->collection_id,
            'user_id'       => $collab->id,
            'invited_by'    => $owner->id,
            'accepted_at'   => now(),
        ]);

        $this->actingAs($collab)
            ->post(route('collections.titles.add', $collection->slug), [
                'title_id' => $title->title_id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('collection_titles', [
            'collection_id' => $collection->collection_id,
            'title_id'      => $title->title_id,
        ]);
    }

    // ── Copy ────────────────────────────────────────────────────────────────

    public function test_user_can_copy_a_published_collection(): void
    {
        $owner      = $this->user();
        $other      = $this->user();
        $collection = Collection::factory()->published()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->post(route('collections.copy', $collection->slug))
            ->assertRedirect();

        $this->assertDatabaseHas('collections', [
            'user_id'               => $other->id,
            'source_collection_id'  => $collection->collection_id,
        ]);
    }
}
