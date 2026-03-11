<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CollectionCommentController extends Controller
{
    private const DAILY_LIMIT = 50;

    // POST /collections/{collection:slug}/comments
    public function store(Request $request, Collection $collection): RedirectResponse
    {
        if (! $collection->is_published || $collection->visibility !== 'PUBLIC') {
            abort(403);
        }

        $data = $request->validate([
            'content'      => ['required', 'string', 'max:2000'],
            'content_type' => ['required', 'in:text,gif'],
            'gif_url'      => ['nullable', 'url', 'max:1024'],
            'parent_id'    => ['nullable', 'integer'],
        ]);

        // Validate parent belongs to same collection (depth ≤ 1)
        if ($data['parent_id'] ?? null) {
            CollectionComment::where('collection_comment_id', $data['parent_id'])
                ->where('collection_id', $collection->collection_id)
                ->whereNull('parent_id')
                ->firstOrFail();
        }

        // Daily limit per user per collection
        $today = now()->toDateString();
        $count = CollectionComment::where('user_id', $request->user()->id)
            ->where('collection_id', $collection->collection_id)
            ->whereDate('created_at', $today)
            ->count();

        if ($count >= self::DAILY_LIMIT) {
            return back(303)->withErrors(['content' => 'Bạn đã đạt giới hạn bình luận hôm nay.']);
        }

        CollectionComment::create([
            'collection_id' => $collection->collection_id,
            'user_id'       => $request->user()->id,
            'parent_id'     => $data['parent_id'] ?? null,
            'content'       => $data['content'],
            'content_type'  => $data['content_type'],
            'gif_url'       => ($data['content_type'] === 'gif') ? ($data['gif_url'] ?? null) : null,
        ]);

        return back(303)->with('success', 'Đã đăng bình luận.');
    }

    // PATCH /collection-comments/{comment}
    public function update(Request $request, CollectionComment $comment): RedirectResponse
    {
        if ($request->user()->id !== $comment->user_id) {
            abort(403);
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update(['content' => $data['content']]);

        return back(303)->with('success', 'Đã cập nhật bình luận.');
    }

    // DELETE /collection-comments/{comment}
    public function destroy(Request $request, CollectionComment $comment): RedirectResponse
    {
        $user = $request->user();
        if ($user->id !== $comment->user_id && ! in_array($user->role, ['ADMIN', 'MODERATOR'])) {
            abort(403);
        }

        $comment->delete();

        return back(303)->with('success', 'Đã xoá bình luận.');
    }

    // POST /collection-comments/{comment}/like
    public function like(Request $request, CollectionComment $comment): RedirectResponse
    {
        $user = $request->user();

        if ($comment->user_id === $user->id) {
            return back(303)->with('error', 'Không thể thích bình luận của chính mình.');
        }

        $liked = $comment->likes()->where('user_id', $user->id)->exists();

        if ($liked) {
            $comment->likes()->detach($user->id);
        } else {
            $comment->likes()->attach($user->id);
        }

        return back(303);
    }

    // POST /collection-comments/{comment}/hide
    public function hide(Request $request, CollectionComment $comment): RedirectResponse
    {
        $comment->update(['is_hidden' => ! $comment->is_hidden]);

        return back(303)->with('success', $comment->is_hidden ? 'Đã ẩn bình luận.' : 'Đã hiện bình luận.');
    }
}
