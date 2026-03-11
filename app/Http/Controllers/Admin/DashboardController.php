<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = Cache::remember('admin_dashboard_stats', 600, fn() => [
            'total_titles'    => Title::count(),
            'total_persons'   => Person::count(),
            'total_reviews'   => Review::count(),
            'total_users'     => User::count(),
            'pending_reviews' => Review::where('has_spoilers', true)->count(),
            'hidden_titles'   => Title::where('visibility', '!=', 'PUBLIC')->count(),
        ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentTitles' => Title::latest('title_id')->take(5)->get(['title_id', 'title_name', 'slug', 'title_type', 'visibility']),
            'recentReviews' => Review::with(['user:id,username', 'title:title_id,title_name'])
                ->latest('review_id')
                ->take(5)
                ->get(),
            'recentUsers' => User::query()
                ->latest('id')
                ->take(5)
                ->get(['id', 'username', 'email', 'role', 'created_at']),
        ]);
    }
}
