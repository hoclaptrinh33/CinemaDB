<?php

namespace App\Http\Controllers;

use App\Models\FeedItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $feedItems = FeedItem::whereIn('actor_user_id', $user->following()->select('users.id'))
            ->with([
                'actor:id,name,username,avatar_path',
            ])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn(FeedItem $item) => [
                'id'            => $item->id,
                'activity_type' => $item->activity_type,
                'metadata'      => $item->metadata,
                'created_at'    => $item->created_at,
                'actor' => [
                    'id'         => $item->actor->id,
                    'name'       => $item->actor->name,
                    'username'   => $item->actor->username,
                    'avatar_url' => $item->actor->avatar_url,
                ],
            ]);

        return Inertia::render('Feed/Index', [
            'feedItems'      => $feedItems,
            'followingCount' => $user->following()->count(),
        ]);
    }
}
