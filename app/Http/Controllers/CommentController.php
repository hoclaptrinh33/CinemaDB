<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Title;
use App\Notifications\CommentLiked;
use App\Notifications\CommentReplied;
use App\Services\FeedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    private const DAILY_LIMIT = 50; // max comments per user per title per day (anti-spam)

    public function __construct(private FeedService $feedService) {}

    public function store(Request $request, Title $title): RedirectResponse
    {
        $data = $request->validate([
            'content'      => ['required', 'string', 'max:2000'],
            'content_type' => ['required', 'in:text,emoji,gif'],
            'gif_url'      => ['nullable', 'url', 'max:1024'],
            'parent_id'    => ['nullable', 'integer'],
        ]);

        // Validate parent exists and belongs to same title (depth ≤ 1)
        if ($data['parent_id'] ?? null) {
            $parent = Comment::where('comment_id', $data['parent_id'])
                ->where('title_id', $title->title_id)
                ->whereNull('parent_id') // only top-level comments can be replied to
                ->firstOrFail();
        }

        $comment = Comment::create([
            'title_id'     => $title->title_id,
            'user_id'      => $request->user()->id,
            'parent_id'    => $data['parent_id'] ?? null,
            'content'      => $data['content'],
            'content_type' => $data['content_type'],
            'gif_url'      => ($data['content_type'] === 'gif') ? ($data['gif_url'] ?? null) : null,
        ]);

        // Only record top-level comments in the feed (not replies — reduces noise)
        if (empty($data['parent_id'])) {
            $this->feedService->record(
                actorId: $request->user()->id,
                activityType: 'comment_created',
                subjectType: 'comment',
                subjectId: $comment->comment_id,
                titleId: $title->title_id,
                metadata: [
                    'title_name'   => $title->title_name,
                    'title_slug'   => $title->slug,
                    'title_type'   => $title->title_type,
                    'poster_url'   => $title->poster_url,
                    'content_type' => $comment->content_type,
                ]
            );
        }

        // Notify parent comment author about the reply (if not self-reply)
        if (isset($parent) && $parent->user_id !== $request->user()->id) {
            $parent->user->notify(new CommentReplied($parent, $comment, $request->user()));
        }

        return back(303)->with('success', 'Đã đăng bình luận.');
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        $this->authorize('update', $comment);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        // Only text edits are allowed; gif/emoji type cannot be changed after posting
        $comment->update(['content' => $data['content']]);

        return back(303)->with('success', 'Đã cập nhật bình luận.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back(303)->with('success', 'Đã xoá bình luận.');
    }

    public function like(Request $request, Comment $comment): RedirectResponse
    {
        $user = $request->user();

        // Prevent self-like
        if ($comment->user_id === $user->id) {
            return back(303)->with('error', 'Không thể thích bình luận của chính mình.');
        }

        $liked = $comment->likedBy()->where('user_id', $user->id)->exists();

        if ($liked) {
            $comment->likedBy()->detach($user->id);
        } else {
            $comment->likedBy()->attach($user->id);

            // Notify comment owner (grouped: aggregate multiple likes on same comment)
            if ($comment->user_id !== $user->id) {
                $groupKey = "comment_liked:{$comment->comment_id}";
                $existing = $comment->user->unreadNotifications()
                    ->where('type', CommentLiked::class)
                    ->where('data->group_key', $groupKey)
                    ->first();

                if ($existing) {
                    $data         = $existing->data;
                    $actors       = $data['actor_names'] ?? [];
                    if (! in_array($user->username, $actors)) {
                        $actors[] = $user->username;
                    }
                    $existing->update([
                        'data' => array_merge($data, [
                            'actor_names' => $actors,
                            'count'       => count($actors),
                        ]),
                    ]);
                } else {
                    $comment->user->notify(new CommentLiked($comment, $user));
                }
            }
        }

        return back(303);
    }

    public function hide(Comment $comment): RedirectResponse
    {
        $this->authorize('hide', $comment);

        $comment->update(['is_hidden' => ! $comment->is_hidden]);

        return back(303)->with('success', $comment->is_hidden ? 'Đã ẩn bình luận.' : 'Đã hiện bình luận.');
    }
}
