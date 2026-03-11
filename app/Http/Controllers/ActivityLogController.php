<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Nomination;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab  = $request->query('tab', 'reviews');

        $reviews = $tab === 'reviews'
            ? Review::with(['title:title_id,title_name,slug,poster_path,title_type'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(15)
            ->through(fn($r) => [
                'review_id'     => $r->review_id,
                'title_name'    => $r->title?->title_name,
                'title_slug'    => $r->title?->slug,
                'title_poster'  => $r->title?->poster_path,
                'title_type'    => $r->title?->title_type,
                'rating'        => $r->rating,
                'review_text'   => $r->review_text,
                'helpful_votes' => $r->helpful_votes,
                'has_spoilers'  => $r->has_spoilers,
                'created_at'    => $r->created_at,
            ])
            : null;

        $comments = $tab === 'comments'
            ? Comment::with(['title:title_id,title_name,slug'])
            ->withCount('likedBy')
            ->where('user_id', $user->id)
            ->where('is_hidden', false)
            ->latest()
            ->paginate(20)
            ->through(fn($c) => [
                'comment_id'   => $c->comment_id,
                'title_name'   => $c->title?->title_name,
                'title_slug'   => $c->title?->slug,
                'content'      => $c->content,
                'content_type' => $c->content_type,
                'parent_id'    => $c->parent_id,
                'likes'        => $c->liked_by_count,
                'created_at'   => $c->created_at,
            ])
            : null;

        $nominations = $tab === 'nominations'
            ? Nomination::with(['title:title_id,title_name,slug,poster_path,title_type'])
            ->where('user_id', $user->id)
            ->orderByDesc('nomination_id')
            ->paginate(20)
            ->through(fn($n) => [
                'nomination_id' => $n->nomination_id,
                'title_name'    => $n->title?->title_name,
                'title_slug'    => $n->title?->slug,
                'title_poster'  => $n->title?->poster_path,
                'title_type'    => $n->title?->title_type,
                'created_at'    => $n->nominated_date,
            ])
            : null;

        return Inertia::render('ActivityLog/Index', [
            'tab'         => $tab,
            'reviews'     => $reviews,
            'comments'    => $comments,
            'nominations' => $nominations,
        ]);
    }
}
