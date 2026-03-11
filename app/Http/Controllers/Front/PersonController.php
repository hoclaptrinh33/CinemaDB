<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PersonController extends Controller
{
    public function show(Person $person): Response
    {
        $person->load('country');

        // Load all published credits with role info via join
        $pivotRows = DB::table('title_person_roles as tpr')
            ->join('roles as r', 'r.role_id', '=', 'tpr.role_id')
            ->join('titles as t', 't.title_id', '=', 'tpr.title_id')
            ->where('tpr.person_id', $person->person_id)
            ->where('t.visibility', 'PUBLIC')
            ->select(
                't.title_id',
                't.title_type',
                't.release_date',
                'r.role_name',
                'r.role_id',
                'tpr.character_name',
                'tpr.cast_order'
            )
            ->orderBy('r.role_id')
            ->orderByDesc('t.release_date')
            ->get();

        // Load Eloquent Title models for computed attributes (poster_url, etc.)
        $titleIds = $pivotRows->pluck('title_id')->unique()->values()->toArray();
        $titles   = Title::whereIn('title_id', $titleIds)->get()->keyBy('title_id');

        // Merge pivot data with title attributes
        $credits = $pivotRows->map(fn($row) => [
            'title_id'       => $row->title_id,
            'slug'           => $titles[$row->title_id]?->slug ?? '',
            'title_name'     => $titles[$row->title_id]?->title_name ?? '',
            'title_name_vi'  => $titles[$row->title_id]?->title_name_vi ?? null,
            'title_type'     => $row->title_type,
            'release_date'   => $row->release_date,
            'poster_url'     => $titles[$row->title_id]?->poster_url ?? null,
            'avg_rating'     => $titles[$row->title_id]?->avg_rating ?? null,
            'role_name'      => $row->role_name,
            'role_id'        => $row->role_id,
            'character_name' => $row->character_name,
            'cast_order'     => $row->cast_order ?? 999,
        ]);

        // Known for: top 8 unique titles by lowest cast_order, then highest rating
        $knownFor = $credits
            ->sortBy([['cast_order', 'asc'], ['avg_rating', 'desc']])
            ->unique('title_id')
            ->take(8)
            ->values();

        // Filmography grouped by role
        $filmography = $credits
            ->groupBy('role_name')
            ->map(fn($items, $roleName) => [
                'role_name' => $roleName,
                'count'     => $items->count(),
                'credits'   => $items->values(),
            ])
            ->values();

        return Inertia::render('Persons/Show', [
            'person'       => $person,
            'filmography'  => $filmography,
            'knownFor'     => $knownFor,
            'totalCredits' => count($titleIds),
        ]);
    }
}
