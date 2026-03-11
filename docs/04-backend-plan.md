# 04 — Backend Plan

## Models

### Danh sách Models

| Model           | Table             | Mô tả                               |
| --------------- | ----------------- | ----------------------------------- |
| `Title`         | `titles`          | Phim, Series, Episode               |
| `Series`        | `series`          | Extension của Title                 |
| `Season`        | `seasons`         | Mùa của Series                      |
| `Episode`       | `episodes`        | Extension của Title                 |
| `Person`        | `persons`         | Diễn viên, Đạo diễn, ...            |
| `Role`          | `roles`           | Loại vai trò (Actor, Director, ...) |
| `Studio`        | `studios`         | Hãng sản xuất                       |
| `Country`       | `countries`       | Quốc gia                            |
| `Language`      | `languages`       | Ngôn ngữ                            |
| `Review`        | `reviews`         | Đánh giá của user                   |
| `TitleAuditLog` | `title_audit_log` | Log thay đổi                        |
| `User`          | `users`           | Tài khoản người dùng                |

---

### `Title` Model

```php
// app/Models/Title.php
class Title extends Model
{
    use HasSlug, SoftDeletes;

    protected $fillable = [
        'title_name', 'slug', 'original_language_id', 'release_date',
        'runtime_mins', 'title_type', 'description', 'poster_path',
        'backdrop_path', 'trailer_url', 'status', 'budget', 'revenue',
        'visibility', 'moderation_reason',
    ];

    // Không dùng timestamps mặc định của Laravel (schema không có created_at/updated_at)
    // Thêm nếu cần:
    // public $timestamps = false;

    // --- Slug ---
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title_name')
            ->saveSlugsTo('slug');
    }

    // --- Relationships ---
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'original_language_id');
    }

    public function studios(): BelongsToMany
    {
        return $this->belongsToMany(Studio::class, 'title_studios');
    }

    public function persons(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'title_person_roles', 'title_id', 'person_id')
            ->using(TitlePersonRole::class)
            ->withPivot(['role_id', 'character_name', 'cast_order']);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'title_id');
    }

    public function seriesDetail(): HasOne
    {
        return $this->hasOne(Series::class, 'series_id');
    }

    public function episodeDetail(): HasOne
    {
        return $this->hasOne(Episode::class, 'episode_id');
    }

    // --- Scopes ---
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('visibility', 'PUBLIC');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn($q, $v) =>
                $q->where('title_name', 'LIKE', "%{$v}%"))
            ->when($filters['type'] ?? null, fn($q, $v) =>
                $q->where('title_type', $v))
            ->when($filters['year'] ?? null, fn($q, $v) =>
                $q->whereYear('release_date', $v))
            ->when($filters['language_id'] ?? null, fn($q, $v) =>
                $q->where('original_language_id', $v));
    }

    // --- Accessors ---
    public function getPosterUrlAttribute(): string
    {
        return $this->poster_path
            ? Storage::url($this->poster_path)
            : asset('images/no-poster.jpg');
    }
}
```

---

### `User` Model (cập nhật)

```php
// app/Models/User.php — thêm vào class

protected $fillable = ['username', 'email', 'password', 'role', 'is_active'];

// Helper methods
public function isAdmin(): bool       { return $this->role === 'ADMIN'; }
public function isModerator(): bool   { return $this->role === 'MODERATOR'; }
public function isActive(): bool      { return (bool) $this->is_active; }

public function reviews(): HasMany
{
    return $this->hasMany(Review::class, 'user_id');
}
```

---

## Controllers

### Cấu trúc thư mục

```
app/Http/Controllers/
├── Admin/
│   ├── DashboardController.php
│   ├── TitleController.php        (resource)
│   ├── SeasonController.php       (store, update, destroy)
│   ├── EpisodeController.php      (store, update, destroy)
│   ├── PersonController.php       (resource)
│   ├── StudioController.php       (resource)
│   ├── CountryController.php      (resource except show)
│   ├── LanguageController.php     (resource except show)
│   ├── RoleController.php         (resource except show)
│   ├── GenreController.php        (resource except show)
│   ├── ReviewController.php       (index, updateStatus, destroy)
│   ├── UserController.php         (index, show, update, adjustReputation)
│   ├── AuditLogController.php     (index)
│   ├── BadgeController.php        (index, create, store, edit, update, destroy)
│   └── TmdbImportController.php   (index, logs, store)
├── Front/
│   ├── HomeController.php
│   ├── TitleController.php        (index, show)
│   ├── PersonController.php       (show)
│   └── LeaderboardController.php  (index)
├── Api/
│   ├── SearchController.php       (__invoke — Meilisearch)
│   └── NotificationController.php (unread, markRead, readAll)
├── Moderate/
│   ├── ReviewController.php       (index, updateStatus, destroy)
│   └── AuditLogController.php     (index)
├── ReviewController.php           (store, update, destroy, helpful)
├── CommentController.php          (store, update, destroy, like, hide)
├── CollectionController.php       (publicIndex, index, show, store, update, destroy, addTitle, removeTitle, publish, unpublish, copy, uploadCover)
├── CollaboratorController.php     (store, accept, destroy)
├── CollectionCommentController.php (store, update, destroy, like, hide)
├── CollectionNominationController.php (nominate, unnominate)
├── CollectionTitleNoteController.php  (upsert, toggleWatch)
├── NominationController.php       (nominate, unnominate)
├── FollowController.php           (store, destroy)
├── FeedController.php             (index)
├── NotificationController.php     (index — trang trung tâm thông báo)
├── UserProfileController.php      (show — public profile)
├── ActivityLogController.php      (index)
├── ProfileController.php          (edit, update, destroy, uploadAvatar, uploadCover)
└── TenorController.php            (search — proxy Tenor GIF API)
```

### Ví dụ: `Admin\TitleController`

```php
class TitleController extends Controller
{
    public function __construct(private TitleService $titleService) {}

    public function index(): Response
    {
        $titles = Title::with('language')
            ->filter(request()->only(['search','type','year']))
            ->latest('title_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Titles/Index', [
            'titles'  => $titles,
            'filters' => request()->only(['search','type','year']),
        ]);
    }

    public function store(StoreTitleRequest $request): RedirectResponse
    {
        $this->titleService->create($request->validated());
        return redirect()->route('admin.titles.index')
            ->with('success', 'Title đã được tạo thành công.');
    }

    public function update(UpdateTitleRequest $request, Title $title): RedirectResponse
    {
        $this->titleService->update($title, $request->validated());
        return back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy(Title $title): RedirectResponse
    {
        $this->titleService->delete($title);
        return redirect()->route('admin.titles.index')
            ->with('success', 'Đã xoá title.');
    }
}
```

---

## Services

### `TitleService`

```php
// app/Services/TitleService.php
class TitleService
{
    public function create(array $data): Title
    {
        $data = $this->handleMediaUploads($data);
        return Title::create($data);
    }

    public function update(Title $title, array $data): Title
    {
        $data = $this->handleMediaUploads($data, $title);
        $title->update($data);
        return $title->fresh();
    }

    public function delete(Title $title): void
    {
        // Xoá file ảnh nếu có
        if ($title->poster_path)   Storage::disk('public')->delete($title->poster_path);
        if ($title->backdrop_path) Storage::disk('public')->delete($title->backdrop_path);
        $title->delete();
    }

    private function handleMediaUploads(array $data, ?Title $existing = null): array
    {
        if (isset($data['poster_image'])) {
            if ($existing?->poster_path) Storage::disk('public')->delete($existing->poster_path);
            $data['poster_path'] = $this->storeImage($data['poster_image'], 'titles/posters', 800, 1200);
            unset($data['poster_image']);
        }
        if (isset($data['backdrop_image'])) {
            if ($existing?->backdrop_path) Storage::disk('public')->delete($existing->backdrop_path);
            $data['backdrop_path'] = $this->storeImage($data['backdrop_image'], 'titles/backdrops', 1920, 1080);
            unset($data['backdrop_image']);
        }
        return $data;
    }

    private function storeImage(UploadedFile $file, string $directory, int $width, int $height): string
    {
        $path = $file->store($directory, 'public');
        // Resize với Intervention Image v3
        $image = \Intervention\Image\Laravel\Facades\Image::read(Storage::disk('public')->path($path));
        $image->cover($width, $height)->save();
        return $path;
    }
}
```

---

## Form Requests

```
app/Http/Requests/
├── Admin/
│   ├── StoreTitleRequest.php
│   ├── UpdateTitleRequest.php
│   ├── StorePersonRequest.php
│   └── StoreStudioRequest.php
└── StoreReviewRequest.php
```

### `StoreTitleRequest`

```php
class StoreTitleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title_name'           => ['required', 'string', 'max:300'],
            'title_type'           => ['required', Rule::in(['MOVIE','SERIES','EPISODE'])],
            'release_date'         => ['nullable', 'date'],
            'runtime_mins'         => ['nullable', 'integer', 'min:1'],
            'description'          => ['nullable', 'string'],
            'original_language_id' => ['nullable', 'exists:languages,language_id'],
            'status'               => ['nullable', Rule::in(['Rumored','Post Production','Released','Canceled'])],
            'visibility'           => ['required', Rule::in(['PUBLIC','HIDDEN','COPYRIGHT_STRIKE','GEO_BLOCKED'])],
            'poster_image'         => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'backdrop_image'       => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'trailer_url'          => ['nullable', 'url', 'max:500'],
            'budget'               => ['nullable', 'integer', 'min:0'],
            'revenue'              => ['nullable', 'integer', 'min:0'],
        ];
    }
}
```

---

## Middleware

```php
// app/Http/Middleware/EnsureRole.php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    $user = $request->user();

    if (! $user || ! $user->is_active) {
        return redirect()->route('login');
    }

    if (! in_array($user->role, $roles)) {
        abort(403, 'Không có quyền truy cập.');
    }

    return $next($request);
}
```

**Đăng ký** trong `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureRole::class,
    ]);
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
})
```

---

## Seeders

| Seeder           | Nội dung                                                             |
| ---------------- | -------------------------------------------------------------------- |
| `CountrySeeder`  | ~50 quốc gia phổ biến (Việt Nam, USA, Korea, Japan, ...)             |
| `LanguageSeeder` | ~20 ngôn ngữ (vi, en, ko, ja, zh, fr, ...)                           |
| `RoleSeeder`     | Actor, Director, Writer, Producer, Cinematographer, Composer, Editor |
| `UserSeeder`     | 3 tài khoản: admin, mod, user                                        |
| `StudioSeeder`   | 10 hãng lớn (Warner Bros, Marvel, BHD, ...)                          |
| `TitleSeeder`    | 20 phim mẫu với cast/crew đầy đủ                                     |
| `BadgeSeeder`    | Bộ huy hiệu đầy đủ theo gamification config                          |
| `GenreSeeder`    | Các thể loại phim (Action, Drama, Comedy, ...)                       |

---

## Services mới (ngoài docs gốc)

> Các service này đã được implement nhưng chưa có trong tài liệu trước.

### `CloudinaryService`

Bọc toàn bộ logic Cloudinary: upload, resize, tạo URL tối ưu (auto format, quality, CDN). Được inject vào `TitleService`, `PersonService`, `StudioService`, `ProfileController`.

```php
// app/Services/CloudinaryService.php
class CloudinaryService
{
    public function upload(UploadedFile $file, string $folder, array $options = []): array
    // Trả về ['url' => ..., 'public_id' => ...]

    public function delete(string $publicId): void

    public function optimizedUrl(string $publicId, int $width, int $height): string
    // Tạo URL CDN với auto crop, format webp, quality auto
}
```

### `TmdbService` + `TmdbImportService`

`TmdbService` gọi TMDB API (đôi chậu lấy metadata), `TmdbImportService` điều phối import vào database (tạo Title, Person, Studio, gán cast/crew, tải poster lên Cloudinary).

```env
# .env
TMDB_API_KEY=your_api_key_here
TMDB_BASE_URL=https://api.themoviedb.org/3
TMDB_IMAGE_BASE=https://image.tmdb.org/t/p
```

Import có thể chạy qua Admin UI (`/admin/tmdb-import`) hoặc queue job (`ImportTmdbTitleJob`, `DiscoverTmdbTitlesJob`).

### `TenorService`

Proxy Tenor GIF search để ẩn API key khỏi client. Route: `GET /api/gifs?q=...` (throttle 30 req/min, auth required).

### `FeedService`

Ghi hoạt động vào bảng `feed_items`. Được gọi từ: `ReviewService`, `CollectionController`, `AwardBadgesOnReview` listener.

```php
FeedService::record(
    actor: $user,
    activityType: 'review_created',  // review_created | collection_created | badge_earned | ...
    subject: $review,
    metadata: ['title_name' => $title->title_name]
);
```

### `LevelUpService`

Kiểm tra cấp bậc sau khi reputation thay đổi, gửi notification `LevelUp` nếu lên cấp.

### `BadgeAwardService` + `BadgeConditionEvaluator`

Tách logic award badge ra khỏi listener để dễ test và tái dùng. `BadgeConditionEvaluator` kiểm tra từng `condition_type`; `BadgeAwardService` điều phối award + side effects.

---

## Models mới (ngoài docs gốc)

| Model                    | Table                      | Mô tả                                                               |
| ------------------------ | -------------------------- | ------------------------------------------------------------------- |
| `Genre`                  | `genres`                   | Thể loại phim; pivot `title_genres`                                 |
| `Comment`                | `title_comments`           | Bình luận trên trang chi tiết title; có thread (reply); soft delete |
| `Nomination`             | `title_nominations`        | User đề cử title vào collection tổng hợp                            |
| `TitleMedia`             | `title_media`              | Gallery ảnh/video của title (trailer, screenshot, ...)              |
| `FeedItem`               | `feed_items`               | Bản ghi hoạt động (review, badge, collection) cho feed              |
| `UserActivityLog`        | `user_activity_logs`       | Log truy cập và hành động của user                                  |
| `TmdbImportLog`          | `tmdb_import_logs`         | Lịch sử import từ TMDB                                              |
| `CollectionCollaborator` | `collection_collaborators` | Cộng tác viên của collection                                        |
| `CollectionComment`      | `collection_comments`      | Bình luận trên collection                                           |
| `CollectionNomination`   | `collection_nominations`   | Đề cử collection                                                    |
| `CollectionTitleNote`    | `collection_title_notes`   | Ghi chú + đánh dấu watched theo từng title trong collection         |
