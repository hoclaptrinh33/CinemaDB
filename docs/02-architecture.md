# 02 — Kiến trúc hệ thống

## Tổng quan kiến trúc

```
┌─────────────────────────────────────────────────────────────────┐
│                          Docker Network (sail)                  │
│                                                                 │
│   ┌──────────────────────────────────┐   ┌──────────────────┐  │
│   │       laravel.test container     │   │  mysql container │  │
│   │  ┌────────────┐  ┌────────────┐  │   │  MySQL 8.4       │  │
│   │  │  Nginx     │  │  PHP 8.2   │  │◄──│  Port 3306       │  │
│   │  │  Port 80   │  │  FPM       │  │   │  sail-mysql vol  │  │
│   │  └────────────┘  └────────────┘  │   └──────────────────┘  │
│   │  ┌────────────────────────────┐  │                         │
│   │  │  Vite Dev Server Port 5173 │  │                         │
│   │  └────────────────────────────┘  │                         │
│   └──────────────────────────────────┘                         │
└─────────────────────────────────────────────────────────────────┘

Browser
  │
  ├── GET /movies/inception     → Laravel Router
  │                                  │
  │                             Route::get() → Controller@method
  │                                  │
  │                             Controller (thin)
  │                                  │
  │                          ┌───────┴───────┐
  │                          │               │
  │                       Service        Eloquent Model
  │                          │               │
  │                          └───────┬───────┘
  │                                  │
  │                             MySQL Query
  │                                  │
  │                         Inertia::render('Pages/Public/Titles/Show', $data)
  │                                  │
  ◄─────────────────────── Vue 3 Component (SSR-like via Inertia)
```

---

## Request Flow (Inertia.js)

### Lần đầu (Full page load)

```
Browser → GET /movies
       → Laravel Router → TitleController@index
       → Eloquent query → data array
       → Inertia::render('Pages/Public/Titles/Index', ['titles' => $paginator])
       → Laravel renders blade shell (app.blade.php)
       → Blade nhúng Inertia page data dưới dạng JSON vào <div id="app">
       → Vue 3 mount → hydrate component từ JSON
       → Trang hiển thị ✓
```

### Điều hướng tiếp theo (SPA navigation)

```
User click link → Inertia router.get('/movies/inception')
               → XHR với header X-Inertia: true
               → Laravel nhận → TitleController@show
               → Inertia::render(...) → trả JSON response
               → Vue 3 cập nhật component, browser không reload
```

---

## Registration + Email Verification Flow

Luồng đăng ký hiện tại:

```text
User nhập thông tin
→ POST /register
→ Backend tạo user chưa verify
→ Tạo token xác thực và gửi mail
→ Redirect tới màn hình chờ xác thực
→ User mở email và nhấn link
→ GET /register/verify/{token}
→ Backend set email_verified_at
→ Auth::login($user)
→ Redirect về /
```

Các nguyên tắc cần giữ đúng trong code và tài liệu:

1. Submit form đăng ký không đăng nhập user ngay.
2. Chỉ sau khi nhấn link trong email thì user mới được xác thực và tự động đăng nhập.
3. Các route ghi dữ liệu quan trọng nên yêu cầu `auth` và `verified`.
4. Màn hình `Auth/VerifyEmail` là bước trung gian của quá trình đăng ký, không phải flow quên mật khẩu.

---

## Phân tầng (Layers)

### Controller — Thin

Controller chỉ làm:

1. Validate input (delegate sang Form Request)
2. Gọi Service
3. Trả response (Inertia render hoặc redirect)

```php
// ✅ Đúng — thin controller
public function store(StoreReviewRequest $request, Title $title): RedirectResponse
{
    $this->reviewService->create($title, $request->validated());
    return back()->with('success', 'Đánh giá của bạn đã được gửi.');
}

// ❌ Sai — fat controller
public function store(Request $request, Title $title): RedirectResponse
{
    if (Review::where('title_id', $title->id)->where('user_id', auth()->id())->exists()) {
        return back()->withErrors(['review' => 'Bạn đã review rồi.']);
    }
    $review = new Review();
    $review->title_id = $title->id;
    // ... 30 dòng nữa
}
```

### Service Layer

`app/Services/` chứa business logic:

- `TitleService` — create/update với upload ảnh, slug generation
- `ReviewService` — tạo review, kiểm tra duplicate, update helpful votes
- `PersonService` — quản lý cast/crew relationships

### Model — Chứa relationship, scope, accessor

```php
// Scope
public function scopePublished(Builder $query): Builder
{
    return $query->where('visibility', 'PUBLIC');
}

// Scope filter cho search/filter page
public function scopeFilter(Builder $query, array $filters): Builder
{
    return $query
        ->when($filters['search'] ?? null, fn($q, $v) => $q->where('title_name', 'LIKE', "%{$v}%"))
        ->when($filters['type'] ?? null,   fn($q, $v) => $q->where('title_type', $v))
        ->when($filters['year'] ?? null,   fn($q, $v) => $q->whereYear('release_date', $v));
}

// Accessor
public function getPosterUrlAttribute(): string
{
    return $this->poster_path
        ? Storage::url($this->poster_path)
        : asset('images/no-poster.jpg');
}
```

---

## Phân quyền (Authorization)

### 3 Roles

```
ADMIN       → toàn quyền
MODERATOR   → xem + kiểm duyệt reviews, xem audit log
USER        → xem public, viết/sửa/xoá review của mình
```

### Middleware `EnsureRole`

```php
// app/Http/Middleware/EnsureRole.php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (! in_array($request->user()?->role, $roles)) {
        abort(403, 'Bạn không có quyền truy cập trang này.');
    }
    return $next($request);
}
```

### Route groups

```php
// routes/web.php

// Public
Route::group([], function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/titles', [Public\TitleController::class, 'index'])->name('titles.index');
    Route::get('/titles/{title:slug}', [Public\TitleController::class, 'show'])->name('titles.show');
    Route::get('/persons/{person}', [Public\PersonController::class, 'show'])->name('persons.show');
    Route::get('/studios/{studio}', [Public\StudioController::class, 'show'])->name('studios.show');
});

// Guest auth flow
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Public registration verification flow
Route::get('/register/pending', [RegisteredUserController::class, 'pending'])->name('verification.pending');
Route::get('/register/verify/{token}', VerifyPendingRegistrationController::class)->name('register.verify');

// Authenticated users
Route::middleware('auth')->group(function () {
    Route::get('/feed', [FeedController::class, 'index'])->name('feed');
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
});

// Authenticated + verified users
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/titles/{title}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/helpful', [ReviewController::class, 'helpful'])->name('reviews.helpful');
});

// Moderator+Admin
Route::middleware(['auth', 'role:MODERATOR,ADMIN'])->prefix('moderate')->group(function () {
    Route::get('/reviews', [Moderate\ReviewController::class, 'index']);
    Route::patch('/reviews/{review}/hide', [Moderate\ReviewController::class, 'hide']);
    Route::get('/audit-log', [Moderate\AuditLogController::class, 'index']);
});

// Admin only
Route::middleware(['auth', 'role:ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::resource('titles',    Admin\TitleController::class);
    Route::resource('persons',   Admin\PersonController::class);
    Route::resource('studios',   Admin\StudioController::class);
    Route::resource('genres',    Admin\GenreController::class);
    Route::resource('countries', Admin\CountryController::class);
    Route::resource('languages', Admin\LanguageController::class);
    Route::resource('users',     Admin\UserController::class)->only(['index','show','update']);
    Route::resource('seasons',   Admin\SeasonController::class)->except(['index']);
    Route::resource('episodes',  Admin\EpisodeController::class)->except(['index']);
});
```

---

## File Upload Flow

```
User uploads poster → StoreMovieRequest validates (mimes:jpg,png,webp, max:5120)
                     → TitleService::handlePosterUpload()
                          → $file->store('titles/posters', 'public')
                          → Intervention\Image resize to 800×1200
                          → Lưu path vào $title->poster_path
                     → Storage::url($path) → accessible tại /storage/titles/posters/xxx.jpg
```

---

## Quyết định thiết kế (ADR)

| Quyết định        | Lựa chọn                        | Lý do                                                           |
| ----------------- | ------------------------------- | --------------------------------------------------------------- |
| Frontend bridge   | Inertia.js                      | Không cần API layer, auth session native, SEO tốt hơn SPA thuần |
| Auth scaffold     | Laravel Breeze (Vue)            | Nhẹ, customizable, tích hợp Inertia sẵn                         |
| Rating update     | MySQL Trigger                   | Tránh N+1 aggregate query; `avg_rating` luôn sẵn trong `titles` |
| Title unification | Bảng `titles` + `title_type`    | Chuẩn IMDB/TMDB, tránh schema phân tán                          |
| Permission        | Enum column `role` trên `users` | Đơn giản hơn spatie/permission cho 3 role cố định               |
| Image processing  | Intervention Image v3           | Native Laravel support, resize/optimize poster                  |
| Slug generation   | spatie/laravel-sluggable        | Unique slug tự động, có collision handling                      |
