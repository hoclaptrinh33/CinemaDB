<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Collection;
use App\Models\Nomination;
use App\Models\Review;
use App\Models\Season;
use App\Models\Title;
use App\Services\TmdbService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TitleController extends Controller
{
    public function index(): Response
    {
        $filterKeys = ['search', 'type', 'year', 'language_id', 'genre_id', 'sort'];

        $titles = Title::published()
            ->with('language')
            ->where('title_type', '!=', 'EPISODE')
            ->filter(request()->only($filterKeys))
            ->paginate(24)
            ->withQueryString();

        $years = Cache::remember('titles.years', 86400, fn() => Title::published()
            ->whereNotNull('release_date')
            ->selectRaw('YEAR(release_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year'));

        $languages = Cache::remember('titles.languages', 86400, fn() => Language::orderBy('language_name')->get(['language_id', 'language_name']));

        $genres = Cache::remember('titles.genres', 86400, fn() => Genre::orderBy('genre_name')->get(['genre_id', 'genre_name', 'genre_name_vi']));

        return Inertia::render('Titles/Index', [
            'titles'    => $titles,
            'filters'   => request()->only($filterKeys),
            'languages' => $languages,
            'genres'    => $genres,
            'years'     => $years,
        ]);
    }

    public function show(string $slug): Response
    {
        $viewer = request()->user();
        $title = Title::where('slug', $slug)
            ->with(['language', 'studios', 'genres'])
            ->firstOrFail();

        // Only admins/moderators can view non-public titles
        if ($title->visibility !== 'PUBLIC') {
            if (! $viewer || ! in_array($viewer->role, ['ADMIN', 'MODERATOR'])) {
                abort(404);
            }
        }

        // Load cast & crew via a single join query (avoids N+1)
        $personsWithRoles = DB::table('title_person_roles as tpr')
            ->join('persons as p', 'p.person_id', '=', 'tpr.person_id')
            ->join('roles as r', 'r.role_id', '=', 'tpr.role_id')
            ->where('tpr.title_id', $title->title_id)
            ->select([
                'p.person_id',
                'p.full_name',
                'p.profile_path',
                'r.role_name',
                'tpr.character_name',
                'tpr.cast_order',
            ])
            ->get();

        $resolvePhoto = fn(?string $path): ?string => $path
            ? (str_starts_with($path, 'http') ? $path : Storage::url($path))
            : null;

        $cast = $personsWithRoles
            ->filter(fn($p) => $p->role_name === 'Actor')
            ->sortBy('cast_order')
            ->map(fn($p) => [
                'person_id'      => $p->person_id,
                'full_name'      => $p->full_name,
                'photo_url'      => $resolvePhoto($p->profile_path),
                'character_name' => $p->character_name,
                'cast_order'     => $p->cast_order,
            ])
            ->values();

        $crew = $personsWithRoles
            ->filter(fn($p) => $p->role_name !== 'Actor')
            ->unique('person_id')
            ->map(fn($p) => [
                'person_id' => $p->person_id,
                'full_name' => $p->full_name,
                'photo_url' => $resolvePhoto($p->profile_path),
                'role_name' => $p->role_name,
            ])
            ->values();

        // Add seasons/episodes count for series (single query with withCount)
        if ($title->title_type === 'SERIES') {
            $seasons = Season::where('series_id', $title->title_id)->withCount('episodes')->get();
            $title->setAttribute('seasons_count', $seasons->count());
            $title->setAttribute('episodes_count', $seasons->sum('episodes_count'));
        }

        $reviews = $title->reviews()
            ->where('moderation_status', 'VISIBLE')
            ->with('user:id,name,username,reputation')
            ->latest('review_id')
            ->paginate(10);

        $topHelpfulReviews = Cache::remember(
            'top_helpful_reviews',
            1800,
            fn() =>
            Review::query()
                ->with('user:id,name,username,reputation')
                ->where('moderation_status', 'VISIBLE')
                ->whereHas('title', fn($q) => $q->where('visibility', 'PUBLIC'))
                ->orderByDesc('helpful_votes')
                ->orderByDesc('review_id')
                ->take(3)
                ->get()
        );

        $userReview = null;
        if ($viewer) {
            $userReview = $title->reviews()
                ->where('user_id', $viewer->id)
                ->first();
        }

        $relatedTitles = Cache::remember(
            "related_titles:{$title->title_id}:{$title->title_type}",
            3600,
            fn() => Title::published()
                ->where('title_type', $title->title_type)
                ->where('title_id', '!=', $title->title_id)
                ->whereNotNull('poster_path')
                ->latest('release_date')
                ->take(6)
                ->get()
        );

        // ── Media (TMDB backdrops + trailers) ─────────────────────────────
        $tmdb = app(TmdbService::class);
        $tmdb->fetchAndStoreMedia($title);
        $media = $title->media()->get(['media_type', 'url', 'thumbnail_url', 'title', 'sort_order']);

        // ── Nominations ────────────────────────────────────────────────────
        $today = Carbon::now('UTC')->toDateString();
        $user  = $viewer;

        $userNominated = false;
        $dailyLeft     = 3;

        if ($user) {
            $userNominated = Nomination::where('user_id', $user->id)
                ->where('title_id', $title->title_id)
                ->where('nominated_date', $today)
                ->exists();

            $usedToday = Nomination::where('user_id', $user->id)
                ->where('nominated_date', $today)
                ->count();

            $dailyLeft = max(0, 3 - $usedToday);
        }

        // ── User collections — owned + collaborated (accepted) ──────────────
        $userCollections = [];
        if ($user) {
            $collaboratedIds = \App\Models\CollectionCollaborator::where('user_id', $user->id)
                ->whereNotNull('accepted_at')
                ->pluck('collection_id');

            $collections = Collection::where(function ($q) use ($user, $collaboratedIds) {
                $q->where('user_id', $user->id)
                    ->orWhereIn('collection_id', $collaboratedIds);
            })
                ->withCount('titles')
                ->orderBy('name')
                ->get();

            // Preload which collections already contain this title (one query instead of N)
            $titleInCollectionIds = DB::table('collection_titles')
                ->whereIn('collection_id', $collections->pluck('collection_id'))
                ->where('title_id', $title->title_id)
                ->pluck('collection_id')
                ->flip()
                ->all();

            $userCollections = $collections
                ->map(function ($c) use ($titleInCollectionIds) {
                    return [
                        'collection_id'    => $c->collection_id,
                        'name'             => $c->name,
                        'slug'             => $c->slug,
                        'visibility'       => $c->visibility,
                        'titles_count'     => $c->titles_count,
                        'has_title'        => isset($titleInCollectionIds[$c->collection_id]),
                    ];
                })
                ->values()
                ->all();
        }

        // ── Comments (top-level only, replies eager-loaded) ────────────────
        $comments = $title->comments()
            ->whereNull('parent_id')
            ->where('is_hidden', false)
            ->with(['user:id,name,username,reputation', 'replies.user:id,name,username,reputation'])
            ->withCount(['likedBy as liked_by_user' => fn($q) => $q->where('user_id', $user?->id ?? 0)])
            ->latest('comment_id')
            ->paginate(20);

        return Inertia::render('Titles/Show', [
            'title'         => $title,
            'cast'          => $cast,
            'crew'          => $crew,
            'reviews'       => $reviews,
            'topHelpfulReviews' => $topHelpfulReviews,
            'userReview'    => $userReview,
            'relatedTitles' => $relatedTitles,
            'media'         => $media,
            'comments'      => $comments,
            'nominations'   => [
                'count'         => $title->nomination_count ?? 0,
                'userNominated' => $userNominated,
                'dailyLeft'     => $dailyLeft,
            ],
            'userCollections'  => $userCollections,
            'can'           => [
                'review'           => (bool) $viewer && ! $userReview,
                'toggleVisibility' => (bool) $viewer && $viewer->can('toggleVisibility', $title),
                'comment'          => (bool) $viewer,
                'nominate'         => (bool) $viewer,
            ],
        ]);
    }
}
