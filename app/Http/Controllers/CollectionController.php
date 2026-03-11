<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionComment;
use App\Models\CollectionNomination;
use App\Models\CollectionTitleNote;
use App\Models\Title;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\FeedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(private FeedService $feedService) {}

    // GET /collections (public published lists)
    public function publicIndex(Request $request): Response
    {
        $q = trim($request->input('q', ''));

        $query = Collection::published()
            ->withCount('titles')
            ->with([
                'user:id,name,username,avatar_path',
                'cover',
                'titles' => fn($tq) => $tq->select(['titles.title_id', 'titles.poster_path']),
            ]);

        if ($q !== '') {
            // Support "#5" or plain "5" for ID lookup
            $idSearch = ltrim($q, '#');
            if (is_numeric($idSearch)) {
                $query->where('collection_id', (int) $idSearch);
            } else {
                $query->where('name', 'like', '%' . $q . '%');
            }
        }

        $collections = $query
            ->orderByDesc('nomination_count')
            ->orderByDesc('published_at')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Collections/PublicIndex', [
            'q'           => $q,
            'collections' => $collections->through(fn($c) => [
                'collection_id'   => $c->collection_id,
                'name'            => $c->name,
                'slug'            => $c->slug,
                'description'     => $c->description,
                'nomination_count' => $c->nomination_count,
                'titles_count'    => $c->titles_count,
                'cover_url'       => $c->cover_url,
                'cover_image_url' => $c->cover_image_url,
                'auto_cover_urls' => $c->auto_cover_urls,
                'owner'           => [
                    'id'       => $c->user->id,
                    'name'     => $c->user->name,
                    'username' => $c->user->username,
                ],
            ]),
        ]);
    }

    // GET /users/{user}/collections
    public function index(User $user): Response
    {
        $viewerIsOwner = auth()->id() === $user->id;

        $mapCollection = fn($c) => [
            'collection_id'   => $c->collection_id,
            'name'            => $c->name,
            'slug'            => $c->slug,
            'description'     => $c->description,
            'visibility'      => $c->visibility,
            'titles_count'    => $c->titles_count,
            'cover_url'       => $c->cover_url,
            'cover_image_url' => $c->cover_image_url,
            'auto_cover_urls' => $c->auto_cover_urls,
        ];

        // Collections owned by this user
        $owned = Collection::forUser($user->id)
            ->when(! $viewerIsOwner, fn($q) => $q->public())
            ->withCount('titles')
            ->with(['cover', 'titles' => fn($q) => $q->select(['titles.title_id', 'titles.poster_path'])])
            ->latest()
            ->get()
            ->map($mapCollection);

        // Collections where this user is an accepted collaborator
        $shared = Collection::whereHas(
            'collaborators',
            fn($q) => $q
                ->where('user_id', $user->id)
                ->whereNotNull('accepted_at')
        )
            ->when(! $viewerIsOwner, fn($q) => $q->public())
            ->withCount('titles')
            ->with(['cover', 'titles' => fn($q) => $q->select(['titles.title_id', 'titles.poster_path'])])
            ->latest()
            ->get()
            ->map($mapCollection)
            ->values();

        return Inertia::render('Collections/Index', [
            'owner'   => ['id' => $user->id, 'name' => $user->name, 'username' => $user->username],
            'owned'   => $owned,
            'shared'  => $shared,
        ]);
    }

    // GET /collections/{collection:slug}
    public function show(Collection $collection): Response
    {
        $authId          = auth()->id();
        $isOwner         = $authId === $collection->user_id;
        $isCollaborator  = $authId && $collection->isCollaborator($authId);
        $isPendingInvite = $authId && $collection->hasPendingInvite($authId);

        if ($collection->visibility === 'PRIVATE' && ! $isOwner && ! $isCollaborator && ! $isPendingInvite) {
            abort(403, 'Bộ sưu tập này là riêng tư.');
        }

        $myRole = match (true) {
            $isOwner         => 'owner',
            $isCollaborator  => 'collaborator',
            $isPendingInvite => 'pending',
            default          => null,
        };

        $collaborators = $collection->collaborators()
            ->with('user:id,name,username,avatar_path')
            ->get()
            ->map(fn($c) => [
                'user_id'    => $c->user_id,
                'name'       => $c->user->name,
                'username'   => $c->user->username,
                'avatar_url' => $c->user->avatar_url,
                'status'     => $c->accepted_at ? 'accepted' : 'pending',
            ]);

        $titles = $collection->titles()
            ->where('visibility', 'PUBLIC')
            ->get()
            ->map(fn($t) => [
                'title_id'   => $t->title_id,
                'slug'       => $t->slug,
                'title_name' => $t->title_name,
                'poster_url' => $t->poster_url,
                'avg_rating' => $t->avg_rating,
                'added_at'   => $t->pivot->added_at,
                'note'       => $t->pivot->note,
            ]);

        // Per-user watch status (only for members; note is now shared on the pivot)
        $userNotes = [];
        if ($authId && ($isOwner || $isCollaborator)) {
            $userNotes = CollectionTitleNote::where('collection_id', $collection->collection_id)
                ->where('user_id', $authId)
                ->get()
                ->keyBy('title_id')
                ->map(fn($n) => [
                    'watched_at' => $n->watched_at?->toIso8601String(),
                ])
                ->toArray();
        }

        // Nomination state
        $today = Carbon::now('UTC')->toDateString();
        $userHasNominated = $authId
            ? CollectionNomination::where('user_id', $authId)
            ->where('collection_id', $collection->collection_id)
            ->where('nominated_date', $today)
            ->exists()
            : false;

        // Comments (only on published public collections)
        $isPublicPublished = $collection->is_published && $collection->visibility === 'PUBLIC';
        $comments = null;
        $canComment = false;
        if ($isPublicPublished) {
            $comments = $collection->comments()
                ->where('parent_id', null)
                ->where('is_hidden', false)
                ->with([
                    'user:id,name,username,reputation',
                    'replies' => fn($q) => $q->where('is_hidden', false)
                        ->with('user:id,name,username,reputation')
                        ->withCount([
                            'likes as like_count',
                            'likes as liked_by_user' => fn($q) => $q->where('user_id', $authId ?? 0),
                        ]),
                ])
                ->withCount([
                    'likes as like_count',
                    'likes as liked_by_user' => fn($q) => $q->where('user_id', $authId ?? 0),
                ])
                ->latest('collection_comment_id')
                ->paginate(20);
            $canComment = (bool) $authId;
        }

        return Inertia::render('Collections/Show', [
            'collection' => [
                'collection_id'        => $collection->collection_id,
                'name'                 => $collection->name,
                'slug'                 => $collection->slug,
                'description'          => $collection->description,
                'visibility'           => $collection->visibility,
                'is_published'         => $collection->is_published,
                'nomination_count'     => $collection->nomination_count,
                'original_author_name' => $collection->original_author_name,
                'cover_url'            => $collection->cover_url,
                'cover_image_url'      => $collection->cover_image_url,
                'publish_headline'     => $collection->publish_headline,
                'publish_body'         => $collection->publish_body,
                'owner'                => [
                    'id'         => $collection->user->id,
                    'name'       => $collection->user->name,
                    'username'   => $collection->user->username,
                    'avatar_url' => $collection->user->avatar_url,
                ],
            ],
            'collaborators'    => $collaborators,
            'myRole'           => $myRole,
            'titles'           => $titles,
            'userNotes'        => $userNotes,
            'userHasNominated' => $userHasNominated,
            'comments'         => $comments,
            'canComment'       => $canComment,
            'can'              => [
                'edit'       => $isOwner || $isCollaborator,
                'invite'     => $isOwner,
                'editMeta'   => $isOwner,
                'publish'    => $isOwner && $collection->visibility === 'PUBLIC',
                'copy'       => $authId && $collection->is_published && $collection->visibility === 'PUBLIC',
                'nominate'   => $authId && $collection->is_published && $collection->visibility === 'PUBLIC',
            ],
        ]);
    }

    // POST /collections
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility'  => ['in:PUBLIC,PRIVATE'],
        ]);

        $data['user_id'] = auth()->id();
        $collection = Collection::create($data);

        $this->feedService->record(
            actorId: auth()->id(),
            activityType: 'collection_created',
            subjectType: 'collection',
            subjectId: $collection->collection_id,
            collectionId: $collection->collection_id,
            metadata: [
                'collection_name' => $collection->name,
                'collection_slug' => $collection->slug,
                'visibility'      => $collection->visibility,
            ]
        );

        return redirect()->route('collections.show', $collection->slug)
            ->with('success', 'Bộ sưu tập "' . $collection->name . '" đã được tạo.');
    }

    // PATCH /collections/{collection}
    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility'  => ['sometimes', 'in:PUBLIC,PRIVATE'],
        ]);


        // Auto-unpublish when switching to PRIVATE
        if (isset($data['visibility']) && $data['visibility'] === 'PRIVATE' && $collection->is_published) {
            $data['is_published'] = false;
            $data['published_at'] = null;
            $this->bustListCaches();
        }

        $collection->update($data);

        return redirect()->route('collections.show', $collection->slug)
            ->with('success', 'Đã cập nhật bộ sưu tập.');
    }

    // DELETE /collections/{collection}
    public function destroy(Collection $collection): RedirectResponse
    {
        $this->authorize('delete', $collection);
        $collection->delete();

        return redirect()->route('users.collections', $collection->user_id)
            ->with('success', 'Đã xóa bộ sưu tập.');
    }

    // POST /collections/{collection}/titles
    public function addTitle(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('manageItems', $collection);

        $request->validate([
            'title_id' => ['required', 'integer', 'exists:titles,title_id'],
            'note'     => ['nullable', 'string', 'max:500'],
        ]);

        $collection->titles()->syncWithoutDetaching([
            $request->title_id => ['note' => $request->note],
        ]);

        $title = Title::find($request->title_id);
        if ($title && $collection->visibility === 'PUBLIC') {
            $this->feedService->record(
                actorId: auth()->id(),
                activityType: 'collection_title_added',
                subjectType: 'collection',
                subjectId: $collection->collection_id,
                titleId: $title->title_id,
                collectionId: $collection->collection_id,
                metadata: [
                    'title_name'      => $title->title_name,
                    'title_slug'      => $title->slug,
                    'poster_url'      => $title->poster_url,
                    'title_type'      => $title->title_type,
                    'collection_name' => $collection->name,
                    'collection_slug' => $collection->slug,
                ]
            );
        }

        return back()->with('success', 'Đã thêm vào "' . $collection->name . '".');
    }

    // DELETE /collections/{collection}/titles/{title}
    public function removeTitle(Collection $collection, Title $title): RedirectResponse
    {
        $this->authorize('manageItems', $collection);
        $collection->titles()->detach($title->title_id);

        return back()->with('success', 'Đã xóa khỏi "' . $collection->name . '".');
    }

    // POST /collections/{collection}/publish
    public function publish(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('publish', $collection);

        if ($collection->titles()->count() === 0) {
            return back()->withErrors([
                'titles' => 'Cần có ít nhất 1 phim trước khi đăng danh sách công khai.',
            ]);
        }

        $data = $request->validate([
            'publish_headline' => ['nullable', 'string', 'max:200'],
            'publish_body'     => ['nullable', 'string', 'max:20000'],
        ]);

        $collection->update(array_merge($data, [
            'is_published' => true,
            'published_at' => Carbon::now(),
        ]));

        $this->bustListCaches();

        return back()->with('success', 'Đã đăng danh sách công khai.');
    }

    // DELETE /collections/{collection}/publish
    public function unpublish(Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $collection->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->bustListCaches();

        return back()->with('success', 'Đã hủy đăng danh sách.');
    }

    // POST /collections/{collection}/copy
    public function copy(Collection $collection): RedirectResponse
    {
        $this->authorize('copy', $collection);

        $owner = $collection->user;
        $newName = $collection->name . ' by ' . $owner->name;

        $copy = Collection::create([
            'user_id'              => auth()->id(),
            'name'                 => $newName,
            'description'          => $collection->description,
            'visibility'           => 'PRIVATE',
            'is_published'         => false,
            'source_collection_id' => $collection->collection_id,
            'original_author_name' => $owner->name,
        ]);

        // Copy titles (no notes)
        $titlesData = $collection->titles()->pluck('titles.title_id')->mapWithKeys(
            fn($id) => [$id => ['added_at' => now()]]
        )->toArray();

        if ($titlesData) {
            $copy->titles()->sync($titlesData);
        }

        return redirect()
            ->route('collections.show', $copy->slug)
            ->with('success', 'Đã sao chép danh sách.');
    }

    // POST /collections/{collection}/cover
    public function uploadCover(Request $request, Collection $collection, CloudinaryService $cloudinary): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'cover' => ['required', 'file', 'image', 'max:4096', 'mimes:jpg,jpeg,png,webp'],
        ]);

        if ($collection->cover_image_url) {
            $cloudinary->delete($collection->cover_image_url);
        }

        $url = $cloudinary->upload($request->file('cover'), 'collection-covers');
        $collection->update(['cover_image_url' => $url]);

        return back()->with('success', 'Đã cập nhật ảnh bìa.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function bustListCaches(): void
    {
        Cache::forget('home.featuredLists');
        foreach (['week', 'month', 'year'] as $period) {
            Cache::forget("leaderboard.lists.{$period}");
        }
    }
}
