<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Title;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $latest = Cache::remember('home.latest', 3600, fn() => Title::published()
            ->with('language')
            ->whereNotNull('poster_path')
            ->where('title_type', '!=', 'EPISODE')
            ->latest('release_date')
            ->take(12)
            ->get());

        $topRated = Cache::remember('home.topRated', 3600, fn() => Title::published()
            ->with('language')
            ->whereNotNull('poster_path')
            ->where('title_type', '!=', 'EPISODE')
            ->orderByDesc('avg_rating')
            ->orderByDesc('release_date')
            ->take(12)
            ->get());

        $featured = Cache::remember('home.featured', 3600, fn() => Title::published()
            ->whereNotNull('backdrop_path')
            ->whereNotNull('poster_path')
            ->where('title_type', '!=', 'EPISODE')
            ->orderByDesc('release_date')
            ->take(8)
            ->get());

        $featuredLists = Cache::remember(
            'home.featuredLists',
            3600,
            fn() =>
            Collection::published()
                ->withCount('titles')
                ->with([
                    'user:id,name,username,avatar_path',
                    'cover',
                    'titles' => fn($q) => $q->select(['titles.title_id', 'titles.poster_path']),
                ])
                ->orderByDesc('nomination_count')
                ->orderByDesc('published_at')
                ->take(8)
                ->get()
                ->map(fn($c) => [
                    'collection_id'   => $c->collection_id,
                    'name'            => $c->name,
                    'slug'            => $c->slug,
                    'description'     => $c->description,
                    'nomination_count' => $c->nomination_count,
                    'titles_count'    => $c->titles_count,
                    'cover_url'       => $c->cover_url,
                    'cover_image_url' => $c->cover_image_url,
                    'auto_cover_urls' => $c->auto_cover_urls,
                    'owner'           => [
                        'id'       => $c->user->id,
                        'name'     => $c->user->name,
                        'username' => $c->user->username,
                    ],
                ])
        );

        return Inertia::render('Home', [
            'featured'      => $featured,
            'latest'        => $latest,
            'topRated'      => $topRated,
            'featuredLists' => $featuredLists,
        ]);
    }
}
