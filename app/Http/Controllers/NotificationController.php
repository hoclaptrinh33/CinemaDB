<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    private const CLASS_MAP = [
        'follow'  => ['App\\Notifications\\NewFollower'],
        'helpful' => ['App\\Notifications\\ReviewHelpful'],
        'comment' => ['App\\Notifications\\CommentReplied', 'App\\Notifications\\CommentLiked'],
        'system'  => ['App\\Notifications\\BadgeEarned', 'App\\Notifications\\LevelUp', 'App\\Notifications\\CollectionInvited'],
    ];

    public function index(Request $request): Response
    {
        $filter       = $request->input('filter') === 'unread' ? 'unread' : 'all';
        $selectedType = array_key_exists($request->input('type', ''), self::CLASS_MAP)
            ? $request->input('type')
            : null;

        $query = $filter === 'unread'
            ? $request->user()->unreadNotifications()
            : $request->user()->notifications();

        if ($selectedType !== null) {
            $query->whereIn('type', self::CLASS_MAP[$selectedType]);
        }

        $notifications = $query
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? 'notification',
                'data'       => $n->data,
                'read_at'    => $n->read_at?->toISOString(),
                'created_at' => $n->created_at?->diffForHumans(),
                'raw_at'     => $n->created_at?->toISOString(),
            ]);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filter'        => $filter,
            'selectedType'  => $selectedType,
            'unreadCount'   => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
