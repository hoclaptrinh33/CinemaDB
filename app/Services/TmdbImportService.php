<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Person;
use App\Models\Role;
use App\Models\Season;
use App\Models\Series;
use App\Models\Studio;
use App\Models\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TmdbImportService
{
    private \Closure $outputInfo;
    private \Closure $outputWarn;

    public function __construct(private readonly TmdbService $tmdb)
    {
        $this->outputInfo = fn(string $msg) => Log::info("[TMDB] {$msg}");
        $this->outputWarn = fn(string $msg) => Log::warning("[TMDB] {$msg}");
    }

    public function setOutput(\Closure $info, \Closure $warn): void
    {
        $this->outputInfo = $info;
        $this->outputWarn = $warn;
    }

    private function info(string $msg): void
    {
        ($this->outputInfo)($msg);
    }
    private function warn(string $msg): void
    {
        ($this->outputWarn)($msg);
    }

    // ── Movie import ───────────────────────────────────────────────────────

    public function importMovie(int $tmdbId): void
    {
        $data = $this->tmdb->getMovie($tmdbId);
        usleep(250_000);

        if (!$data) {
            $this->warn("Skipping movie TMDB #{$tmdbId}: no data returned.");
            return;
        }

        try {
            DB::transaction(function () use ($data) {
                $releaseDate = $data['release_date'] ?: now()->toDateString();
                $languageId  = $this->resolveLanguage($data['original_language'] ?? '');
                $trailerUrl  = $this->extractTrailerUrl($data['videos'] ?? []);

                $title = Title::updateOrCreate(
                    ['tmdb_id' => $data['id']],
                    [
                        'title_name'           => $data['title'],
                        'release_date'         => $releaseDate,
                        'original_language_id' => $languageId,
                        'runtime_mins'         => $data['runtime'] ?: null,
                        'title_type'           => 'MOVIE',
                        'description'          => $data['overview'] ?: null,
                        'poster_path'          => $this->tmdb->posterUrl($data['poster_path'] ?? null),
                        'backdrop_path'        => $this->tmdb->backdropUrl($data['backdrop_path'] ?? null),
                        'trailer_url'          => $trailerUrl,
                        'status'               => $this->mapStatus($data['status'] ?? null),
                        'budget'               => $data['budget'] ?: null,
                        'revenue'              => $data['revenue'] ?: null,
                        'visibility'           => 'PUBLIC',
                    ]
                );

                foreach ($data['production_companies'] ?? [] as $company) {
                    $studio = $this->upsertStudio($company);
                    $title->studios()->syncWithoutDetaching([$studio->studio_id]);
                }

                $this->attachCredits($title, $data['credits'] ?? []);

                // Sync genres
                $this->syncGenres($title, $data['genres'] ?? []);

                // Fetch Vietnamese translation
                $vi      = $this->tmdb->getMovieTranslations($data['id'], 'vi-VN');
                usleep(250_000);
                $viName  = trim($vi['title'] ?? '');
                $viDesc  = trim($vi['overview'] ?? '');
                $title->update([
                    'title_name_vi'  => $viName !== '' ? $viName : null,
                    'description_vi' => $viDesc !== '' ? $viDesc : null,
                ]);

                $this->info("Imported movie: {$title->title_name} ({$title->release_date?->year})");
            });
        } catch (\Throwable $e) {
            $this->warn("Failed movie TMDB #{$tmdbId}: {$e->getMessage()}");
        }
    }

    // ── TV import ──────────────────────────────────────────────────────────

    public function importTv(int $tmdbId): void
    {
        $data = $this->tmdb->getTvShow($tmdbId);
        usleep(250_000);

        if (!$data) {
            $this->warn("Skipping TV TMDB #{$tmdbId}: no data returned.");
            return;
        }

        try {
            DB::transaction(function () use ($tmdbId, $data) {
                $releaseDate = $data['first_air_date'] ?: now()->toDateString();
                $runtimeMins = isset($data['episode_run_time'][0]) ? (int) $data['episode_run_time'][0] : null;
                $languageId  = $this->resolveLanguage($data['original_language'] ?? '');
                $trailerUrl  = $this->extractTrailerUrl($data['videos'] ?? []);

                $title = Title::updateOrCreate(
                    ['tmdb_id' => $data['id']],
                    [
                        'title_name'           => $data['name'],
                        'release_date'         => $releaseDate,
                        'original_language_id' => $languageId,
                        'runtime_mins'         => $runtimeMins,
                        'title_type'           => 'SERIES',
                        'description'          => $data['overview'] ?: null,
                        'poster_path'          => $this->tmdb->posterUrl($data['poster_path'] ?? null),
                        'backdrop_path'        => $this->tmdb->backdropUrl($data['backdrop_path'] ?? null),
                        'trailer_url'          => $trailerUrl,
                        'status'               => $this->mapStatus($data['status'] ?? null),
                        'visibility'           => 'PUBLIC',
                    ]
                );

                Series::firstOrCreate(['series_id' => $title->title_id]);

                foreach ($data['production_companies'] ?? [] as $company) {
                    $studio = $this->upsertStudio($company);
                    $title->studios()->syncWithoutDetaching([$studio->studio_id]);
                }

                $this->attachCredits($title, $data['credits'] ?? []);

                // Sync genres
                $this->syncGenres($title, $data['genres'] ?? []);

                // Fetch Vietnamese translation
                $vi      = $this->tmdb->getTvShowTranslations($tmdbId, 'vi-VN');
                usleep(250_000);
                $viName  = trim($vi['name'] ?? '');
                $viDesc  = trim($vi['overview'] ?? '');
                $title->update([
                    'title_name_vi'  => $viName !== '' ? $viName : null,
                    'description_vi' => $viDesc !== '' ? $viDesc : null,
                ]);

                foreach ($data['seasons'] ?? [] as $seasonData) {
                    $seasonNumber = (int) $seasonData['season_number'];
                    if ($seasonNumber === 0) {
                        continue; // skip specials
                    }

                    $season = Season::firstOrCreate([
                        'series_id'     => $title->title_id,
                        'season_number' => $seasonNumber,
                    ]);

                    $seasonDetail = $this->tmdb->getTvSeason($tmdbId, $seasonNumber);
                    usleep(250_000);

                    if (!$seasonDetail) {
                        continue;
                    }

                    foreach ($seasonDetail['episodes'] ?? [] as $episodeData) {
                        $this->upsertEpisode($season->season_id, $episodeData);
                    }
                }

                $this->info("Imported TV show: {$title->title_name} ({$releaseDate})");
            });
        } catch (\Throwable $e) {
            $this->warn("Failed TV TMDB #{$tmdbId}: {$e->getMessage()}");
        }
    }

    // ── Episode upsert ─────────────────────────────────────────────────────

    public function upsertEpisode(int $seasonId, array $data): void
    {
        $episodeName = $data['name'] ?: "Episode {$data['episode_number']}";
        $airDate     = $data['air_date'] ?: null;

        $existing = Episode::where('season_id', $seasonId)
            ->where('episode_number', $data['episode_number'])
            ->first();

        $titleData = [
            'title_type'   => 'EPISODE',
            'title_name'   => $episodeName,
            'runtime_mins' => $data['runtime'] ?: null,
            'description'  => $data['overview'] ?: null,
            'release_date' => $airDate ?: now()->toDateString(),
            'poster_path'  => $this->tmdb->stillUrl($data['still_path'] ?? null),
            'visibility'   => 'PUBLIC',
        ];

        if ($existing) {
            Title::where('title_id', $existing->episode_id)->update($titleData);
        } else {
            $episodeTitle = Title::create($titleData);
            Episode::create([
                'episode_id'     => $episodeTitle->title_id,
                'season_id'      => $seasonId,
                'episode_number' => $data['episode_number'],
                'air_date'       => $airDate,
            ]);
        }
    }

    // ── Person upsert ──────────────────────────────────────────────────────

    public function upsertPerson(array $credit): Person
    {
        $tmdbPersonId = (int) $credit['id'];

        // If person already imported, skip the extra API calls
        $existing = Person::where('tmdb_id', $tmdbPersonId)->first();
        if ($existing) {
            return $existing;
        }

        // New person — fetch full details from TMDB
        $personData = $this->tmdb->getPerson($tmdbPersonId);
        usleep(250_000);

        // DB enum: ['Male', 'Female', 'Other'] (nullable for unknown)
        $genderMap  = [1 => 'Female', 2 => 'Male', 3 => 'Other', 0 => null];
        $genderCode = $personData['gender'] ?? $credit['gender'] ?? 0;
        $gender     = $genderMap[$genderCode] ?? null;

        $fullName    = $personData['name']      ?? $credit['name'];
        $birthDate   = $personData['birthday']  ?? null;
        $deathDate   = $personData['deathday']  ?? null;
        $biography   = $personData['biography'] ?? null;
        $profilePath = $this->tmdb->profileUrl($personData['profile_path'] ?? $credit['profile_path'] ?? null);
        $countryId   = $this->resolveCountry($personData['place_of_birth'] ?? null);

        // Fetch Vietnamese biography translation
        $biographyVi = null;
        $viData      = $this->tmdb->getPersonTranslations($tmdbPersonId, 'vi-VN');
        usleep(250_000);
        if ($viData) {
            $viText      = trim($viData['biography'] ?? '');
            $biographyVi = $viText !== '' ? $viText : null;
        }

        return Person::create([
            'tmdb_id'      => $tmdbPersonId,
            'full_name'    => $fullName,
            'birth_date'   => $birthDate,
            'death_date'   => $deathDate,
            'gender'       => $gender,
            'biography'    => $biography,
            'biography_vi' => $biographyVi,
            'profile_path' => $profilePath,
            'country_id'   => $countryId,
        ]);
    }

    // ── Studio upsert ──────────────────────────────────────────────────────

    public function upsertStudio(array $company): Studio
    {
        $countryId = null;

        if (!empty($company['origin_country'])) {
            $country   = Country::where('iso_code', $company['origin_country'])->first();
            $countryId = $country?->country_id;
        }

        $logoPath = !empty($company['logo_path'])
            ? $this->tmdb->posterUrl($company['logo_path'])
            : null;

        return Studio::updateOrCreate(
            ['studio_name' => $company['name']],
            array_filter([
                'country_id' => $countryId,
                'logo_path'  => $logoPath,
            ], fn($v) => $v !== null)
        );
    }

    // ── Attach credits ─────────────────────────────────────────────────────

    public function attachCredits(Title $title, array $credits): void
    {
        // Top 30 cast ordered by billing order (TMDB default)
        $cast = array_slice($credits['cast'] ?? [], 0, 30);

        // All crew from relevant departments (no per-dept cap)
        $allowedDepts = ['Directing', 'Writing', 'Production'];
        $keyCrew      = array_filter(
            $credits['crew'] ?? [],
            fn($m) => in_array($m['department'] ?? '', $allowedDepts)
        );

        foreach ($cast as $member) {
            $person = $this->upsertPerson($member);
            $roleId = $this->resolveRole('Acting', $member['job'] ?? 'Actor');
            if (!$roleId) {
                continue;
            }

            DB::table('title_person_roles')->updateOrInsert(
                [
                    'title_id'  => $title->title_id,
                    'person_id' => $person->person_id,
                    'role_id'   => $roleId,
                ],
                [
                    'character_name' => $member['character'] ?? null,
                    'cast_order'     => $member['order'] ?? null,
                ]
            );
        }

        foreach ($keyCrew as $member) {
            $person = $this->upsertPerson($member);
            $roleId = $this->resolveRole($member['department'] ?? '', $member['job'] ?? '');
            if (!$roleId) {
                continue;
            }

            DB::table('title_person_roles')->updateOrInsert(
                [
                    'title_id'  => $title->title_id,
                    'person_id' => $person->person_id,
                    'role_id'   => $roleId,
                ],
                [
                    'character_name' => null,
                    'cast_order'     => null,
                ]
            );
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function resolveLanguage(string $iso): ?int
    {
        if ($iso === '') {
            return null;
        }

        return Language::where('iso_code', $iso)->value('language_id');
    }

    private function resolveCountry(?string $placeOfBirth): ?int
    {
        if (!$placeOfBirth) {
            return null;
        }

        // Use the last comma-separated segment as the country name
        $parts       = array_map('trim', explode(',', $placeOfBirth));
        $countryName = end($parts);

        return Country::where('country_name', 'like', "%{$countryName}%")->value('country_id');
    }

    private function resolveRole(string $department, string $job): ?int
    {
        $roleMap = [
            'Acting'     => 'Actor',
            'Directing'  => 'Director',
            'Writing'    => 'Writer',
            'Production' => 'Producer',
        ];

        $roleName = $roleMap[$department] ?? null;
        if (!$roleName) {
            return null;
        }

        return Role::where('role_name', $roleName)->value('role_id');
    }

    private function extractTrailerUrl(array $videos): ?string
    {
        foreach ($videos['results'] ?? [] as $video) {
            if (($video['type'] ?? '') === 'Trailer' && ($video['site'] ?? '') === 'YouTube') {
                return "https://youtube.com/watch?v={$video['key']}";
            }
        }

        return null;
    }

    private function mapStatus(?string $tmdbStatus): ?string
    {
        return match ($tmdbStatus) {
            'Released', 'Ended', 'Returning Series' => 'Released',
            'Rumored', 'Planned', 'Pilot'           => 'Rumored',
            'Post Production', 'In Production'      => 'Post Production',
            'Canceled'                              => 'Canceled',
            default                                 => null,
        };
    }

    // ── Genre sync ─────────────────────────────────────────────────────────

    private function syncGenres(Title $title, array $tmdbGenres): void
    {
        if (empty($tmdbGenres)) {
            return;
        }

        $genreIds = [];
        foreach ($tmdbGenres as $g) {
            $tmdbId = (int) $g['id'];
            $genre  = Genre::where('tmdb_id', $tmdbId)->first()
                ?? Genre::create([
                    'tmdb_id'    => $tmdbId,
                    'genre_name' => $g['name'],
                ]);

            $genreIds[] = $genre->genre_id;
        }

        $title->genres()->sync($genreIds);
    }
}
