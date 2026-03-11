<?php

namespace App\Services;

use App\Models\Title;
use App\Models\TitleMedia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    private string $baseUrl;
    private string $imageBaseUrl;
    private string $posterSize;
    private string $backdropSize;
    private string $profileSize;

    public function __construct()
    {
        $this->baseUrl      = config('tmdb.base_url');
        $this->imageBaseUrl = config('tmdb.image_base_url');
        $this->posterSize   = config('tmdb.poster_size');
        $this->backdropSize = config('tmdb.backdrop_size');
        $this->profileSize  = config('tmdb.profile_size');
    }

    // ── Generic request ────────────────────────────────────────────────────

    private function get(string $endpoint, array $params = []): ?array
    {
        try {
            $http = Http::connectTimeout(8)
                ->timeout(config('tmdb.timeout', 15))
                ->withToken(config('tmdb.read_access_token'))
                ->acceptJson();

            if (!app()->isProduction()) {
                $http = $http->withoutVerifying();
            }

            $response = $http->get("{$this->baseUrl}/{$endpoint}", $params);

            if ($response->failed()) {
                Log::warning("TMDB {$endpoint} failed: " . $response->status());
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("TMDB request error: " . $e->getMessage());
            return null;
        }
    }

    // ── Movie endpoints ────────────────────────────────────────────────────

    public function discoverMovies(int $page = 1, array $extra = []): array
    {
        return $this->get('discover/movie', array_merge(['page' => $page, 'sort_by' => 'popularity.desc'], $extra)) ?? [];
    }

    public function getMovie(int $tmdbId): ?array
    {
        return $this->get("movie/{$tmdbId}", ['append_to_response' => 'credits,videos']);
    }

    // ── TV endpoints ───────────────────────────────────────────────────────

    public function discoverTv(int $page = 1, array $extra = []): array
    {
        return $this->get('discover/tv', array_merge(['page' => $page, 'sort_by' => 'popularity.desc'], $extra)) ?? [];
    }

    public function getTvShow(int $tmdbId): ?array
    {
        return $this->get("tv/{$tmdbId}", ['append_to_response' => 'credits,videos']);
    }

    public function getTvSeason(int $tmdbId, int $seasonNumber): ?array
    {
        return $this->get("tv/{$tmdbId}/season/{$seasonNumber}");
    }

    // ── Person endpoint ────────────────────────────────────────────────────

    public function getPerson(int $tmdbId): ?array
    {
        return $this->get("person/{$tmdbId}");
    }

    // ── Search ─────────────────────────────────────────────────────────────

    public function searchMovie(string $query, int $page = 1): array
    {
        return $this->get('search/movie', ['query' => $query, 'page' => $page]) ?? [];
    }

    public function searchTv(string $query, int $page = 1): array
    {
        return $this->get('search/tv', ['query' => $query, 'page' => $page]) ?? [];
    }

    // ── Image URL helpers ──────────────────────────────────────────────────

    public function posterUrl(?string $path): ?string
    {
        return $path ? "{$this->imageBaseUrl}/{$this->posterSize}{$path}" : null;
    }

    public function backdropUrl(?string $path): ?string
    {
        return $path ? "{$this->imageBaseUrl}/{$this->backdropSize}{$path}" : null;
    }

    public function profileUrl(?string $path): ?string
    {
        return $path ? "{$this->imageBaseUrl}/{$this->profileSize}{$path}" : null;
    }

    public function stillUrl(?string $path): ?string
    {
        return $path ? "{$this->imageBaseUrl}/w780{$path}" : null;
    }

    // ── Locale-specific endpoints ──────────────────────────────────────────

    public function getMovieTranslations(int $tmdbId, string $locale): ?array
    {
        return $this->get("movie/{$tmdbId}", ['language' => $locale]);
    }

    public function getTvShowTranslations(int $tmdbId, string $locale): ?array
    {
        return $this->get("tv/{$tmdbId}", ['language' => $locale]);
    }

    public function getPersonTranslations(int $tmdbId, string $locale): ?array
    {
        return $this->get("person/{$tmdbId}", ['language' => $locale]);
    }

    // ── Media (images + trailers) for Show page ────────────────────────────

    /**
     * Fetch and store backdrops + trailers for a title from TMDB.
     * Idempotent: skips if title_media already populated for this title.
     */
    public function fetchAndStoreMedia(Title $title): void
    {
        if (!$title->tmdb_id) {
            return;
        }

        // Skip if already fetched
        if ($title->media()->exists()) {
            return;
        }

        $endpoint = $title->title_type === 'SERIES'
            ? "tv/{$title->tmdb_id}"
            : "movie/{$title->tmdb_id}";

        $data = $this->get($endpoint, ['append_to_response' => 'images,videos']);

        if (!$data) {
            return;
        }

        $records = [];
        $order   = 0;

        // Backdrops (max 8)
        foreach (array_slice($data['images']['backdrops'] ?? [], 0, 8) as $img) {
            $records[] = [
                'title_id'      => $title->title_id,
                'media_type'    => 'backdrop',
                'url'           => "{$this->imageBaseUrl}/{$this->backdropSize}{$img['file_path']}",
                'thumbnail_url' => "{$this->imageBaseUrl}/w300{$img['file_path']}",
                'title'         => null,
                'sort_order'    => $order++,
            ];
        }

        // Trailers from YouTube (max 4)
        $trailers = array_filter(
            $data['videos']['results'] ?? [],
            fn($v) => $v['site'] === 'YouTube' && in_array($v['type'], ['Trailer', 'Teaser'])
        );
        foreach (array_slice(array_values($trailers), 0, 4) as $vid) {
            $records[] = [
                'title_id'      => $title->title_id,
                'media_type'    => 'trailer',
                'url'           => "https://www.youtube.com/watch?v={$vid['key']}",
                'thumbnail_url' => "https://img.youtube.com/vi/{$vid['key']}/hqdefault.jpg",
                'title'         => $vid['name'],
                'sort_order'    => $order++,
            ];
        }

        if ($records) {
            TitleMedia::insert($records);
        }
    }
}
