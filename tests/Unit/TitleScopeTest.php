<?php

namespace Tests\Unit;

use App\Models\Language;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TitleScopeTest extends TestCase
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

    // ── Helper ─────────────────────────────────────────────────────────────

    private function makeTitle(array $attributes = []): Title
    {
        return Title::factory()->create(array_merge(
            ['original_language_id' => $this->language->language_id],
            $attributes
        ));
    }

    // ── scopePublished ──────────────────────────────────────────────────────

    public function test_published_scope_returns_only_public_titles(): void
    {
        $public = $this->makeTitle(['visibility' => 'PUBLIC']);
        $hidden = $this->makeTitle(['visibility' => 'HIDDEN']);

        $results = Title::published()->get();

        $this->assertTrue($results->contains($public));
        $this->assertFalse($results->contains($hidden));
    }

    // ── scopeFilter — search ────────────────────────────────────────────────

    public function test_filter_scope_searches_by_title_name(): void
    {
        $inception  = $this->makeTitle(['title_name' => 'Inception']);
        $interstellar = $this->makeTitle(['title_name' => 'Interstellar']);
        $matrix     = $this->makeTitle(['title_name' => 'The Matrix']);

        $results = Title::filter(['search' => 'inter'])->get();

        $this->assertTrue($results->contains($interstellar));
        $this->assertFalse($results->contains($inception));
        $this->assertFalse($results->contains($matrix));
    }

    // ── scopeFilter — type ──────────────────────────────────────────────────

    public function test_filter_scope_filters_by_type(): void
    {
        $movie  = $this->makeTitle(['title_type' => 'MOVIE']);
        $series = $this->makeTitle(['title_type' => 'SERIES', 'runtime_mins' => null]);

        $movies = Title::filter(['type' => 'MOVIE'])->get();
        $this->assertTrue($movies->contains($movie));
        $this->assertFalse($movies->contains($series));

        $seriesList = Title::filter(['type' => 'SERIES'])->get();
        $this->assertTrue($seriesList->contains($series));
        $this->assertFalse($seriesList->contains($movie));
    }

    // ── scopeFilter — year ──────────────────────────────────────────────────

    public function test_filter_scope_filters_by_release_year(): void
    {
        $title2020 = $this->makeTitle(['release_date' => '2020-06-15']);
        $title2023 = $this->makeTitle(['release_date' => '2023-11-01']);

        $results = Title::filter(['year' => 2020])->get();

        $this->assertTrue($results->contains($title2020));
        $this->assertFalse($results->contains($title2023));
    }

    // ── scopeFilter — language ──────────────────────────────────────────────

    public function test_filter_scope_filters_by_language(): void
    {
        $french = Language::create(['iso_code' => 'fr', 'language_name' => 'French']);

        $englishTitle = $this->makeTitle(['original_language_id' => $this->language->language_id]);
        $frenchTitle  = $this->makeTitle(['original_language_id' => $french->language_id]);

        $results = Title::filter(['language_id' => $french->language_id])->get();

        $this->assertTrue($results->contains($frenchTitle));
        $this->assertFalse($results->contains($englishTitle));
    }

    // ── scopeFilter — empty filters ─────────────────────────────────────────

    public function test_filter_scope_returns_all_titles_when_no_filters(): void
    {
        $title1 = $this->makeTitle(['title_name' => 'Alpha']);
        $title2 = $this->makeTitle(['title_name' => 'Beta']);

        $results = Title::filter([])->get();

        $this->assertTrue($results->contains($title1));
        $this->assertTrue($results->contains($title2));
    }

    // ── scopeFilter — combined ──────────────────────────────────────────────

    public function test_filter_scope_can_combine_type_and_year(): void
    {
        $movie2022 = $this->makeTitle(['title_type' => 'MOVIE', 'release_date' => '2022-05-01']);
        $movie2019 = $this->makeTitle(['title_type' => 'MOVIE', 'release_date' => '2019-03-10']);
        $series2022 = $this->makeTitle(['title_type' => 'SERIES', 'release_date' => '2022-09-20', 'runtime_mins' => null]);

        $results = Title::filter(['type' => 'MOVIE', 'year' => 2022])->get();

        $this->assertTrue($results->contains($movie2022));
        $this->assertFalse($results->contains($movie2019));
        $this->assertFalse($results->contains($series2022));
    }
}
