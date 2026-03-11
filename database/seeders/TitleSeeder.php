<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Episode;
use App\Models\Language;
use App\Models\Person;
use App\Models\Review;
use App\Models\Role;
use App\Models\Season;
use App\Models\Series;
use App\Models\Studio;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TitleSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Seed Persons (50 people) ────────────────────────────────────
        Person::factory(50)->create();

        // ── 2. Cache lookup IDs ────────────────────────────────────────────
        $enLang  = Language::where('iso_code', 'en')->value('language_id');
        $frLang  = Language::where('iso_code', 'fr')->value('language_id');
        $jpLang  = Language::where('iso_code', 'ja')->value('language_id');
        $koLang  = Language::where('iso_code', 'ko')->value('language_id');

        $roles   = Role::pluck('role_id', 'role_name');
        $studios = Studio::pluck('studio_id')->all();
        $users   = User::pluck('id')->all();
        $persons = Person::pluck('person_id')->all();

        // ── 3. Seed Movies (14 phim lẻ) ───────────────────────────────────
        $movies = [
            [
                'title_name'           => 'The Last Frontier',
                'original_language_id' => $enLang,
                'release_date'         => '2020-07-15',
                'runtime_mins'         => 132,
                'title_type'           => 'MOVIE',
                'description'          => 'An epic adventure set in the harsh wilderness of the American West. A group of explorers ventures into uncharted territory, facing nature and their own demons.',
                'status'               => 'Released',
                'budget'               => 85_000_000,
                'revenue'              => 240_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Neon City',
                'original_language_id' => $enLang,
                'release_date'         => '2019-11-22',
                'runtime_mins'         => 118,
                'title_type'           => 'MOVIE',
                'description'          => 'A neo-noir thriller set in a rain-soaked cyberpunk metropolis where a detective unravels a global conspiracy.',
                'status'               => 'Released',
                'budget'               => 60_000_000,
                'revenue'              => 185_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Beyond the Stars',
                'original_language_id' => $enLang,
                'release_date'         => '2021-03-05',
                'runtime_mins'         => 145,
                'title_type'           => 'MOVIE',
                'description'          => 'A visionary sci-fi epic following a crew of astronauts on a one-way mission to colonize a distant exoplanet.',
                'status'               => 'Released',
                'budget'               => 150_000_000,
                'revenue'              => 520_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'La Belle Époque',
                'original_language_id' => $frLang,
                'release_date'         => '2018-09-12',
                'runtime_mins'         => 112,
                'title_type'           => 'MOVIE',
                'description'          => 'A French romantic drama about a disillusioned illustrator who finds meaning in an immersive historical simulation.',
                'status'               => 'Released',
                'budget'               => 12_000_000,
                'revenue'              => 45_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Shadows of Edo',
                'original_language_id' => $jpLang,
                'release_date'         => '2022-04-08',
                'runtime_mins'         => 128,
                'title_type'           => 'MOVIE',
                'description'          => 'A samurai period drama set in 17th-century Japan, following a ronin seeking redemption after the fall of his lord.',
                'status'               => 'Released',
                'budget'               => 20_000_000,
                'revenue'              => 88_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Broken Mirrors',
                'original_language_id' => $enLang,
                'release_date'         => '2023-01-20',
                'runtime_mins'         => 99,
                'title_type'           => 'MOVIE',
                'description'          => 'A psychological thriller about a forensic psychologist who begins to question her own sanity after taking a new case.',
                'status'               => 'Released',
                'budget'               => 18_000_000,
                'revenue'              => 62_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'The Grand Heist',
                'original_language_id' => $enLang,
                'release_date'         => '2017-05-30',
                'runtime_mins'         => 124,
                'title_type'           => 'MOVIE',
                'description'          => 'A stylish heist film following a crew of elite thieves planning an audacious robbery of the world\'s most secure vault.',
                'status'               => 'Released',
                'budget'               => 75_000_000,
                'revenue'              => 310_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Seoul Rising',
                'original_language_id' => $koLang,
                'release_date'         => '2024-02-14',
                'runtime_mins'         => 136,
                'title_type'           => 'MOVIE',
                'description'          => 'A gripping Korean political thriller about a young activist who exposes corruption at the highest levels of government.',
                'status'               => 'Released',
                'budget'               => 15_000_000,
                'revenue'              => 95_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Wildfire Season',
                'original_language_id' => $enLang,
                'release_date'         => '2022-08-19',
                'runtime_mins'         => 107,
                'title_type'           => 'MOVIE',
                'description'          => 'Based on true events, a disaster drama following firefighters battling a catastrophic wildfire threatening a small mountain town.',
                'status'               => 'Released',
                'budget'               => 40_000_000,
                'revenue'              => 140_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Ocean\'s Depth',
                'original_language_id' => $enLang,
                'release_date'         => '2023-06-23',
                'runtime_mins'         => 115,
                'title_type'           => 'MOVIE',
                'description'          => 'A deep-sea exploration film where a marine biologist discovers an ancient underwater civilization.',
                'status'               => 'Released',
                'budget'               => 90_000_000,
                'revenue'              => 275_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Ivory Tower',
                'original_language_id' => $enLang,
                'release_date'         => '2016-10-07',
                'runtime_mins'         => 103,
                'title_type'           => 'MOVIE',
                'description'          => 'A coming-of-age drama set in a prestigious New England university where a scholarship student navigates ambition, love, and identity.',
                'status'               => 'Released',
                'budget'               => 8_000_000,
                'revenue'              => 28_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'The Haunting of Blackwood Manor',
                'original_language_id' => $enLang,
                'release_date'         => '2021-10-29',
                'runtime_mins'         => 110,
                'title_type'           => 'MOVIE',
                'description'          => 'A gothic horror film following a family who inherits a remote Victorian mansion with a dark and violent history.',
                'status'               => 'Released',
                'budget'               => 22_000_000,
                'revenue'              => 78_000_000,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'Velocity',
                'original_language_id' => $enLang,
                'release_date'         => '2025-05-10',
                'runtime_mins'         => 98,
                'title_type'           => 'MOVIE',
                'description'          => 'A high-octane action film following a former race car driver who becomes an undercover agent to dismantle a global smuggling ring.',
                'status'               => 'Post Production',
                'budget'               => 110_000_000,
                'revenue'              => null,
                'visibility'           => 'PUBLIC',
            ],
            [
                'title_name'           => 'The Memory Thief',
                'original_language_id' => $enLang,
                'release_date'         => '2024-09-13',
                'runtime_mins'         => 121,
                'title_type'           => 'MOVIE',
                'description'          => 'A mind-bending sci-fi thriller set in a near future where memories can be extracted and sold on the black market.',
                'status'               => 'Released',
                'budget'               => 55_000_000,
                'revenue'              => 195_000_000,
                'visibility'           => 'PUBLIC',
            ],
        ];

        $createdMovies = [];
        foreach ($movies as $movieData) {
            $title = Title::create($movieData);
            $createdMovies[] = $title;
        }

        // ── 4. Seed Series (3 series, each with seasons + episodes) ───────
        $seriesData = [
            [
                'title' => [
                    'title_name'           => 'Chronicles of the Void',
                    'original_language_id' => $enLang,
                    'release_date'         => '2020-03-15',
                    'title_type'           => 'SERIES',
                    'description'          => 'An epic sci-fi series set in a distant galaxy where humanity fights for survival against an ancient alien threat.',
                    'status'               => 'Released',
                    'visibility'           => 'PUBLIC',
                ],
                'seasons' => [
                    [
                        'season_number' => 1,
                        'episodes' => [
                            ['episode_number' => 1, 'episode_name' => 'Pilot',             'runtime_mins' => 52, 'air_date' => '2020-03-15'],
                            ['episode_number' => 2, 'episode_name' => 'The Signal',        'runtime_mins' => 48, 'air_date' => '2020-03-22'],
                            ['episode_number' => 3, 'episode_name' => 'First Contact',     'runtime_mins' => 50, 'air_date' => '2020-03-29'],
                        ],
                    ],
                    [
                        'season_number' => 2,
                        'episodes' => [
                            ['episode_number' => 1, 'episode_name' => 'Resurgence',        'runtime_mins' => 55, 'air_date' => '2021-06-10'],
                            ['episode_number' => 2, 'episode_name' => 'The Rift',          'runtime_mins' => 51, 'air_date' => '2021-06-17'],
                            ['episode_number' => 3, 'episode_name' => 'End of Days',       'runtime_mins' => 62, 'air_date' => '2021-06-24'],
                        ],
                    ],
                ],
            ],
            [
                'title' => [
                    'title_name'           => 'Crimson Ties',
                    'original_language_id' => $enLang,
                    'release_date'         => '2022-09-01',
                    'title_type'           => 'SERIES',
                    'description'          => 'A prestige crime drama following three generations of a powerful crime family as their empire begins to crumble.',
                    'status'               => 'Released',
                    'visibility'           => 'PUBLIC',
                ],
                'seasons' => [
                    [
                        'season_number' => 1,
                        'episodes' => [
                            ['episode_number' => 1, 'episode_name' => 'Blood Money',       'runtime_mins' => 58, 'air_date' => '2022-09-01'],
                            ['episode_number' => 2, 'episode_name' => 'Family Business',   'runtime_mins' => 54, 'air_date' => '2022-09-08'],
                            ['episode_number' => 3, 'episode_name' => 'Loyalty',           'runtime_mins' => 56, 'air_date' => '2022-09-15'],
                        ],
                    ],
                    [
                        'season_number' => 2,
                        'episodes' => [
                            ['episode_number' => 1, 'episode_name' => 'The Reckoning',     'runtime_mins' => 60, 'air_date' => '2023-10-05'],
                            ['episode_number' => 2, 'episode_name' => 'New Alliances',     'runtime_mins' => 57, 'air_date' => '2023-10-12'],
                            ['episode_number' => 3, 'episode_name' => 'The Siege',         'runtime_mins' => 65, 'air_date' => '2023-10-19'],
                        ],
                    ],
                ],
            ],
            [
                'title' => [
                    'title_name'           => 'Lost in Kyoto',
                    'original_language_id' => $jpLang,
                    'release_date'         => '2023-04-07',
                    'title_type'           => 'SERIES',
                    'description'          => 'A slow-burn Japanese drama about a foreign chef who moves to Kyoto and discovers love, tradition, and himself.',
                    'status'               => 'Released',
                    'visibility'           => 'PUBLIC',
                ],
                'seasons' => [
                    [
                        'season_number' => 1,
                        'episodes' => [
                            ['episode_number' => 1, 'episode_name' => 'Arrival',           'runtime_mins' => 45, 'air_date' => '2023-04-07'],
                            ['episode_number' => 2, 'episode_name' => 'The First Dish',    'runtime_mins' => 42, 'air_date' => '2023-04-14'],
                            ['episode_number' => 3, 'episode_name' => 'Cherry Blossoms',   'runtime_mins' => 44, 'air_date' => '2023-04-21'],
                        ],
                    ],
                    [
                        'season_number' => 2,
                        'episodes' => [
                            ['episode_number' => 1, 'episode_name' => 'Return',            'runtime_mins' => 46, 'air_date' => '2024-03-29'],
                            ['episode_number' => 2, 'episode_name' => 'Old Wounds',        'runtime_mins' => 43, 'air_date' => '2024-04-05'],
                            ['episode_number' => 3, 'episode_name' => 'Home',              'runtime_mins' => 50, 'air_date' => '2024-04-12'],
                        ],
                    ],
                ],
            ],
        ];

        $createdSeries = [];
        foreach ($seriesData as $sd) {
            // Create the parent Title (type=SERIES)
            $seriesTitle = Title::create($sd['title']);

            // Create the Series record (shared PK)
            $series = Series::create(['series_id' => $seriesTitle->title_id]);

            foreach ($sd['seasons'] as $seasonData) {
                $season = Season::create([
                    'series_id'     => $series->series_id,
                    'season_number' => $seasonData['season_number'],
                ]);

                foreach ($seasonData['episodes'] as $ep) {
                    // Each episode is also a Title record
                    $episodeTitle = Title::create([
                        'title_name'           => $ep['episode_name'],
                        'original_language_id' => $sd['title']['original_language_id'],
                        'release_date'         => $ep['air_date'],
                        'runtime_mins'         => $ep['runtime_mins'],
                        'title_type'           => 'EPISODE',
                        'status'               => 'Released',
                        'visibility'           => 'PUBLIC',
                    ]);

                    Episode::create([
                        'episode_id'     => $episodeTitle->title_id,
                        'season_id'      => $season->season_id,
                        'episode_number' => $ep['episode_number'],
                        'air_date'       => $ep['air_date'],
                    ]);
                }
            }

            $createdSeries[] = $seriesTitle;
        }

        // All non-episode titles for use in relations
        $allMainTitles = array_merge($createdMovies, $createdSeries);

        // ── 5. Assign Studios ──────────────────────────────────────────────
        foreach ($allMainTitles as $title) {
            $studioCount = rand(1, 3);
            $picked = collect($studios)->shuffle()->take($studioCount)->all();
            $title->studios()->sync($picked);
        }

        // ── 6. Assign Cast & Crew ──────────────────────────────────────────
        $actorRoleId    = $roles['Actor'];
        $directorRoleId = $roles['Director'];
        $writerRoleId   = $roles['Writer'];

        foreach ($allMainTitles as $title) {
            $usedPersons = [];

            // 1 Director
            $director = collect($persons)->filter(fn($id) => !in_array($id, $usedPersons))->random();
            $usedPersons[] = $director;
            DB::table('title_person_roles')->insert([
                'title_id'       => $title->title_id,
                'person_id'      => $director,
                'role_id'        => $directorRoleId,
                'character_name' => null,
                'cast_order'     => 0,
            ]);

            // 1 Writer
            $writer = collect($persons)->filter(fn($id) => !in_array($id, $usedPersons))->random();
            $usedPersons[] = $writer;
            DB::table('title_person_roles')->insert([
                'title_id'       => $title->title_id,
                'person_id'      => $writer,
                'role_id'        => $writerRoleId,
                'character_name' => null,
                'cast_order'     => 0,
            ]);

            // 3–5 Actors
            $actorCount = rand(3, 5);
            $availableActors = collect($persons)->filter(fn($id) => !in_array($id, $usedPersons))->shuffle()->take($actorCount);
            foreach ($availableActors as $order => $actorId) {
                $usedPersons[] = $actorId;
                DB::table('title_person_roles')->insert([
                    'title_id'       => $title->title_id,
                    'person_id'      => $actorId,
                    'role_id'        => $actorRoleId,
                    'character_name' => fake()->firstName() . ' ' . fake()->lastName(),
                    'cast_order'     => $order + 1,
                ]);
            }
        }

        // ── 7. Seed Reviews ────────────────────────────────────────────────
        $reviewTexts = [
            'An absolute masterpiece! The cinematography is breathtaking and the performances are unforgettable.',
            'A solid film with great writing and direction. Some pacing issues in the second act but overall very enjoyable.',
            'Disappointing. The trailers promised something epic but the actual film felt hollow and formulaic.',
            'One of the best I\'ve seen this year. The story is gripping from start to finish.',
            'Beautifully crafted with exceptional attention to detail. The score alone is worth the watch.',
            'A fun popcorn film that doesn\'t take itself too seriously. Great entertainment.',
            'The acting was brilliant but the script let it down. Missed potential.',
            'Visually stunning and emotionally resonant. A rare gem in modern cinema.',
            'Predictable plot but held together by charismatic performances.',
            'An ambitious production that mostly delivers on its promises.',
        ];

        // Each user reviews a random selection of titles
        foreach ($users as $userId) {
            // Pick random 5—10 titles to review
            $titlesToReview = collect($allMainTitles)->shuffle()->take(rand(5, 10));
            foreach ($titlesToReview as $title) {
                Review::create([
                    'title_id'    => $title->title_id,
                    'user_id'     => $userId,
                    'rating'      => rand(4, 10),
                    'review_text' => $reviewTexts[array_rand($reviewTexts)],
                    'has_spoilers' => (bool) rand(0, 1),
                ]);
            }
        }
    }
}
