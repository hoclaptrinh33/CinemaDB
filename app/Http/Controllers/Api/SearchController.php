<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Title;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:MOVIE,SERIES,EPISODE'],
            'year' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));

        if ($query === '' || mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $filters = [];

        if (!empty($validated['type'])) {
            $filters[] = "title_type = '{$validated['type']}'";
        }

        if (!empty($validated['year'])) {
            $filters[] = 'release_year = ' . (int) $validated['year'];
        }

        if (isset($validated['min_rating'])) {
            $filters[] = 'avg_rating >= ' . (float) $validated['min_rating'];
        }

        $limit = (int) ($validated['limit'] ?? 10);
        $options = [
            'limit' => $limit,
            'attributesToHighlight' => ['title_name', 'title_name_vi'],
        ];

        if (!empty($filters)) {
            $options['filter'] = implode(' AND ', $filters);
        }

        $results = $this->searchTitles($query, $validated, $options, $limit);

        return response()->json([
            'results' => $results->map(fn(Title $title) => [
                'id' => $title->title_id,
                'name' => $title->title_name,
                'name_vi' => $title->title_name_vi,
                'slug' => $title->slug,
                'type' => $title->title_type,
                'year' => $title->release_date?->year,
                'rating' => (float) ($title->avg_rating ?? 0),
                'poster_url' => $title->poster_url,
            ])->values(),
        ]);
    }

    private function searchTitles(string $query, array $validated, array $options, int $limit)
    {
        $driver = config('scout.driver');

        if (! in_array($driver, ['null', 'collection'], true)) {
            try {
                return Title::search($query)
                    ->options($options)
                    ->get();
            } catch (\Throwable) {
                // Fall back to DB search when the configured search engine is unavailable.
            }
        }

        return Title::query()
            ->published()
            ->where(function ($builder) use ($query) {
                $builder->where('title_name', 'like', '%' . $query . '%')
                    ->orWhere('title_name_vi', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%')
                    ->orWhere('description_vi', 'like', '%' . $query . '%');
            })
            ->when(! empty($validated['type']), fn($builder) => $builder->where('title_type', $validated['type']))
            ->when(! empty($validated['year']), fn($builder) => $builder->whereYear('release_date', (int) $validated['year']))
            ->when(isset($validated['min_rating']), fn($builder) => $builder->where('avg_rating', '>=', (float) $validated['min_rating']))
            ->orderByDesc('avg_rating')
            ->orderBy('title_name')
            ->limit($limit)
            ->get();
    }
}
