<?php

namespace App\Jobs;

use App\Models\Title;
use App\Models\TmdbImportLog;
use App\Services\TmdbService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DiscoverTmdbTitlesJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function __construct(
        public readonly int   $pages = 3,
        public readonly array $types = ['movie', 'tv'],
    ) {}

    public function handle(TmdbService $tmdb): void
    {
        // Thu thập toàn bộ tmdb_id từ tất cả các trang trước, rồi lọc 1 lần duy nhất
        $collected = ['movie' => [], 'tv' => []];

        for ($page = 1; $page <= $this->pages; $page++) {
            if (in_array('movie', $this->types)) {
                $movies = $tmdb->discoverMovies($page);
                foreach ($movies['results'] ?? [] as $item) {
                    $collected['movie'][$item['id']] = true;
                }
            }

            if (in_array('tv', $this->types)) {
                $tvShows = $tmdb->discoverTv($page);
                foreach ($tvShows['results'] ?? [] as $item) {
                    $collected['tv'][$item['id']] = true;
                }
            }
        }

        foreach ($this->types as $type) {
            $tmdbIds = array_keys($collected[$type] ?? []);
            if (empty($tmdbIds)) {
                continue;
            }

            // Lọc bỏ titles đã có trong DB
            $importedIds = Title::whereIn('tmdb_id', $tmdbIds)
                ->pluck('tmdb_id')
                ->flip();

            // Lọc bỏ titles đang pending/processing trong queue (tránh dispatch trùng)
            $inProgressIds = TmdbImportLog::whereIn('tmdb_id', $tmdbIds)
                ->where('type', $type)
                ->whereIn('status', ['pending', 'processing'])
                ->pluck('tmdb_id')
                ->flip();

            $newIds = array_filter(
                $tmdbIds,
                fn($id) => ! $importedIds->has($id) && ! $inProgressIds->has($id)
            );

            foreach ($newIds as $tmdbId) {
                $log = TmdbImportLog::create(['tmdb_id' => $tmdbId, 'type' => $type]);
                ImportTmdbTitleJob::dispatch($tmdbId, $type, $log->id);
            }
        }
    }
}
