<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** GET /api/notifications/unread — trả về unread notifications của user hiện tại. */
    public function unread(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->take(30)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->data['type'] ?? 'notification',
                'data'       => $n->data,
                'created_at' => $n->created_at?->diffForHumans(),
                'raw_at'     => $n->created_at,
            ]);

        return response()->json($notifications);
    }

    /** POST /api/notifications/{id}/read — đánh dấu một notification đã đọc. */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->unreadNotifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['ok' => true]);
    }

    /** POST /api/notifications/read-all — đánh dấu tất cả đã đọc. */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
