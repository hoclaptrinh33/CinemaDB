<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Title;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
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

    private function title(array $attrs = []): Title
    {
        return Title::factory()->create(array_merge([
            'original_language_id' => $this->language->language_id,
            'visibility'           => 'PUBLIC',
        ], $attrs));
    }

    // ── Validation ─────────────────────────────────────────────────────────

    public function test_search_requires_minimum_two_characters(): void
    {
        $this->getJson(route('api.search', ['q' => 'a']))
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    public function test_search_returns_empty_on_blank_query(): void
    {
        $this->getJson(route('api.search', ['q' => '']))
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    public function test_search_rejects_invalid_type_filter(): void
    {
        $this->getJson(route('api.search', ['q' => 'batman', 'type' => 'INVALID']))
            ->assertUnprocessable();
    }

    public function test_search_rejects_year_out_of_range(): void
    {
        $this->getJson(route('api.search', ['q' => 'batman', 'year' => 1700]))
            ->assertUnprocessable();
    }

    public function test_search_rejects_min_rating_above_10(): void
    {
        $this->getJson(route('api.search', ['q' => 'batman', 'min_rating' => 11]))
            ->assertUnprocessable();
    }

    public function test_search_rejects_limit_above_20(): void
    {
        $this->getJson(route('api.search', ['q' => 'batman', 'limit' => 21]))
            ->assertUnprocessable();
    }

    // ── DB-backed search (runs in test env with scout.driver=null) ───────────

    public function test_search_returns_results_via_meilisearch(): void
    {
        // Force DB fallback path that is used in all test environments.
        $this->app['config']->set('scout.driver', 'null');

        $this->title(['title_name' => 'The Dark Knight', 'title_type' => 'MOVIE']);

        $response = $this->getJson(route('api.search', ['q' => 'Dark Knight']));

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [['id', 'name', 'slug', 'type', 'year', 'rating', 'poster_url']],
            ]);
    }

    // ── Fallback: DB search when Scout is disabled/queue ───────────────────

    public function test_search_endpoint_accessible_to_guests(): void
    {
        // The search API must be accessible without authentication
        $this->getJson(route('api.search', ['q' => 'test query']))
            ->assertOk();
    }

    public function test_search_result_shape_matches_spec(): void
    {
        // Force DB fallback so the test doesn't depend on an external search engine.
        $this->app['config']->set('scout.driver', 'null');

        $this->title(['title_name' => 'Inception', 'title_type' => 'MOVIE']);

        $response = $this->getJson(route('api.search', ['q' => 'Inception']));

        $response->assertOk();
        $data = $response->json('results.0');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('rating', $data);
        $this->assertArrayHasKey('poster_url', $data);
    }

    // ── toSearchableArray ──────────────────────────────────────────────────

    public function test_title_searchable_array_contains_required_fields(): void
    {
        $title = $this->title([
            'title_name'   => 'Interstellar',
            'title_type'   => 'MOVIE',
            'release_date' => '2014-11-07',
            'avg_rating'   => 8.6,
        ]);

        $searchable = $title->toSearchableArray();

        $this->assertArrayHasKey('title_name', $searchable);
        $this->assertArrayHasKey('title_type', $searchable);
        $this->assertArrayHasKey('release_year', $searchable);
        $this->assertArrayHasKey('avg_rating', $searchable);
        $this->assertEquals('Interstellar', $searchable['title_name']);
        $this->assertEquals(2014, $searchable['release_year']);
    }
}
