<?php

use App\Models\Title;
use Illuminate\Database\Migrations\Migration;
use Laravel\Scout\EngineManager;

return new class extends Migration
{
    public function up(): void
    {
        if (config('scout.driver') !== 'meilisearch') {
            return;
        }

        /** @var \Laravel\Scout\Engines\MeilisearchEngine $engine */
        $engine = app(EngineManager::class)->engine();
        $index = $engine->index((new Title())->searchableAs());

        $index->updateSettings([
            'searchableAttributes' => ['title_name', 'title_name_vi', 'description', 'description_vi'],
            'filterableAttributes' => ['title_type', 'release_year', 'avg_rating', 'visibility', 'original_language_id'],
            'sortableAttributes' => ['avg_rating', 'release_year', 'rating_count'],
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => [
                    'oneTypo' => 4,
                    'twoTypos' => 8,
                ],
            ],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
        ]);
    }

    public function down(): void
    {
        if (config('scout.driver') !== 'meilisearch') {
            return;
        }

        /** @var \Laravel\Scout\Engines\MeilisearchEngine $engine */
        $engine = app(EngineManager::class)->engine();
        $index = $engine->index((new Title())->searchableAs());

        $index->resetSettings();
    }
};
