<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionNomination;
use App\Models\Nomination;
use App\Models\Title;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now          = Carbon::now('UTC');
        $nomPeriod    = in_array($request->get('nom_period'), ['day', 'week', 'month', 'year'])
            ? $request->get('nom_period') : 'week';
        $ratingPeriod = in_array($request->get('rating_period'), ['month', 'year'])
            ? $request->get('rating_period') : 'month';
        $ratingYear   = (int) ($request->get('rating_year')  ?? $now->year);
        $ratingMonth  = (int) ($request->get('rating_month') ?? $now->month);
        $activeYear   = (int) ($request->get('active_year')  ?? $now->year);
        $listsPeriod  = in_array($request->get('lists_period'), ['week', 'month', 'year'])
            ? $request->get('lists_period') : 'week';

        // ── 1. Top nominated titles ────────────────────────────────
        $nomRange  = $this->nominationDateRange($nomPeriod, $now);
        $nominatedTitles = Cache::remember(
            "leaderboard.nom.{$nomPeriod}",
            $nomPeriod === 'day' ? 900 : 1800,
            function () use ($nomRange) {
                return Nomination::select('title_id', DB::raw('COUNT(*) as nomination_count'))
                    ->whereBetween('nominated_date', [$nomRange[0], $nomRange[1]])
                    ->groupBy('title_id')
                    ->orderByDesc('nomination_count')
                    ->with(['title' => fn($q) => $q->select('title_id', 'title_name', 'title_name_vi', 'slug', 'poster_path', 'title_type')])
                    ->take(10)
                    ->get()
                    ->filter(fn($n) => $n->title !== null)
                    ->map(fn($n) => [
                        'title_id'         => $n->title->title_id,
                        'title_name'       => $n->title->title_name,
                        'title_name_vi'    => $n->title->title_name_vi,
                        'slug'             => $n->title->slug,
                        'poster_url'       => $n->title->poster_url,
                        'title_type'       => $n->title->title_type,
                        'nomination_count' => $n->nomination_count,
                    ])
                    ->values();
            }
        );

        // ── 2. Top rated new titles ────────────────────────────────
        $ratingCacheKey  = $ratingPeriod === 'month'
            ? "leaderboard.toprated.{$ratingYear}.{$ratingMonth}"
            : "leaderboard.toprated.{$ratingYear}";
        $topRatedTitles = Cache::remember($ratingCacheKey, 3600, function () use ($ratingPeriod, $ratingYear, $ratingMonth) {
            return Title::published()
                ->where('title_type', '!=', 'EPISODE')
                ->whereNotNull('poster_path')
                ->whereYear('release_date', $ratingYear)
                ->when($ratingPeriod === 'month', fn($q) => $q->whereMonth('release_date', $ratingMonth))
                ->where('rating_count', '>', 0)
                ->orderByDesc('avg_rating')
                ->orderByDesc('rating_count')
                ->take(10)
                ->get()
                ->map(fn($t) => [
                    'title_id'      => $t->title_id,
                    'title_name'    => $t->title_name,
                    'title_name_vi' => $t->title_name_vi,
                    'slug'          => $t->slug,
                    'poster_url'    => $t->poster_url,
                    'title_type'    => $t->title_type,
                    'avg_rating'    => round((float) $t->avg_rating, 1),
                    'rating_count'  => $t->rating_count,
                    'release_date'  => $t->release_date?->toDateString(),
                ]);
        });

        // ── 3. Top users by reputation ─────────────────────────────
        $topUsers = Cache::remember('leaderboard.topusers', 3600, function () {
            return User::where('is_active', true)
                ->orderByDesc('reputation')
                ->take(10)
                ->get()
                ->map(fn($u) => [
                    'id'         => $u->id,
                    'username'   => $u->username,
                    'name'       => $u->name,
                    'reputation' => $u->reputation,
                    'avatar_url' => $u->avatar_url,
                    'role'       => $u->role,
                ]);
        });

        // ── 4. Most active users (by year) ─────────────────────────
        $topActiveUsers = Cache::remember("leaderboard.topactive.{$activeYear}", 1800, function () use ($activeYear) {
            return UserActivityLog::select('user_id', DB::raw('COUNT(*) as activity_days'))
                ->whereYear('activity_date', $activeYear)
                ->groupBy('user_id')
                ->orderByDesc('activity_days')
                ->with(['user' => fn($q) => $q->select('id', 'username', 'name', 'reputation', 'avatar_path')])
                ->take(10)
                ->get()
                ->filter(fn($log) => $log->user !== null)
                ->map(fn($log) => [
                    'id'            => $log->user->id,
                    'username'      => $log->user->username,
                    'name'          => $log->user->name,
                    'reputation'    => $log->user->reputation,
                    'avatar_url'    => $log->user->avatar_url,
                    'activity_days' => $log->activity_days,
                ])
                ->values();
        });

        // ── 5. Top lists by nominations (week/month/year) ──────────
        $listsRange = $this->nominationDateRange($listsPeriod, $now);
        $topLists = Cache::remember(
            "leaderboard.lists.{$listsPeriod}",
            $listsPeriod === 'week' ? 900 : 1800,
            function () use ($listsRange) {
                return CollectionNomination::select('collection_id', DB::raw('COUNT(*) as nom_count'))
                    ->whereBetween('nominated_date', [$listsRange[0], $listsRange[1]])
                    ->groupBy('collection_id')
                    ->orderByDesc('nom_count')
                    ->with(['collection' => fn($q) => $q
                        ->where('is_published', true)
                        ->where('visibility', 'PUBLIC')
                        ->withCount('titles')
                        ->with([
                            'user:id,name,username,avatar_path',
                            'cover',
                            'titles' => fn($tq) => $tq->select(['titles.title_id', 'titles.poster_path']),
                        ])])
                    ->take(10)
                    ->get()
                    ->filter(fn($n) => $n->collection !== null)
                    ->map(fn($n) => [
                        'collection_id'    => $n->collection->collection_id,
                        'name'             => $n->collection->name,
                        'slug'             => $n->collection->slug,
                        'cover_image_url'  => $n->collection->cover_image_url,
                        'cover_poster_url' => $n->collection->cover?->poster_url,
                        'auto_cover_urls'  => $n->collection->auto_cover_urls,
                        'titles_count'     => $n->collection->titles_count,
                        'nomination_count' => $n->nom_count,
                        'owner_name'       => $n->collection->user->name,
                        'owner_username'   => $n->collection->user->username,
                    ])
                    ->values();
            }
        );

        return Inertia::render('Leaderboards/Index', [
            'nominatedTitles' => $nominatedTitles,
            'nomPeriod'       => $nomPeriod,
            'topRatedTitles'  => $topRatedTitles,
            'ratingPeriod'    => $ratingPeriod,
            'ratingYear'      => $ratingYear,
            'ratingMonth'     => $ratingMonth,
            'topUsers'        => $topUsers,
            'topActiveUsers'  => $topActiveUsers,
            'activeYear'      => $activeYear,
            'currentYear'     => $now->year,
            'currentMonth'    => $now->month,
            'topLists'        => $topLists,
            'listsPeriod'     => $listsPeriod,
        ]);
    }

    private function nominationDateRange(string $period, Carbon $now): array
    {
        return match ($period) {
            'day'   => [$now->toDateString(), $now->toDateString()],
            'week'  => [$now->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
            'month' => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
            'year'  => [$now->copy()->startOfYear()->toDateString(), $now->copy()->endOfYear()->toDateString()],
        };
    }
}
