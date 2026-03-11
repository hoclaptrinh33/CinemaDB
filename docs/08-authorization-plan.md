# 08 — Authorization Plan (Policies + Gates)

## Tổng quan

Hệ thống phân quyền dùng **2 lớp**:

| Lớp                    | Công cụ                 | Dùng cho                       |
| ---------------------- | ----------------------- | ------------------------------ |
| **Role-based routing** | `EnsureRole` Middleware | Chặn truy cập route theo role  |
| **Resource-level**     | Laravel Policies        | Ai được sửa/xoá bản ghi cụ thể |

---

## Roles

```php
// Enum định nghĩa trong app/Enums/UserRole.php
enum UserRole: string
{
    case ADMIN     = 'ADMIN';
    case MODERATOR = 'MODERATOR';
    case USER      = 'USER';
}
```

| Role        | Mô tả                                          |
| ----------- | ---------------------------------------------- |
| `ADMIN`     | Toàn quyền — quản lý dữ liệu, users, audit log |
| `MODERATOR` | Kiểm duyệt review, ẩn title, xem audit log     |
| `USER`      | Viết review, quản lý watchlist cá nhân         |

---

## Middleware: `EnsureRole`

```php
// app/Http/Middleware/EnsureRole.php
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            return redirect()->route('login');
        }

        if (! in_array($user->role, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}
```

**Áp dụng lên routes:**

```php
// routes/web.php
Route::prefix('admin')->middleware(['auth', 'role:ADMIN'])->group(function () {
    // Toàn bộ admin routes
});

Route::prefix('moderate')->middleware(['auth', 'role:ADMIN,MODERATOR'])->group(function () {
    // Review moderation, ẩn title
});

Route::middleware(['auth'])->group(function () {
    // Review CRUD, watchlist
});
```

---

## Policies

### Đăng ký Policies

```php
// app/Providers/AppServiceProvider.php
Gate::policy(Title::class,  TitlePolicy::class);
Gate::policy(Review::class, ReviewPolicy::class);
Gate::policy(User::class,   UserPolicy::class);
```

---

### `TitlePolicy`

```php
// app/Policies/TitlePolicy.php
class TitlePolicy
{
    // Admin có bypass tất cả
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    // Xem title ẩn: chỉ ADMIN/MODERATOR
    public function viewHidden(User $user): bool
    {
        return in_array($user->role, ['ADMIN', 'MODERATOR']);
    }

    // Tạo title mới: chỉ ADMIN
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    // Sửa title: ADMIN (before() đã xử lý)
    public function update(User $user, Title $title): bool
    {
        return false; // chỉ ADMIN mới tới được (before bypass)
    }

    // Xoá title: chỉ ADMIN
    public function delete(User $user, Title $title): bool
    {
        return false;
    }

    // Ẩn/unhide title: ADMIN hoặc MODERATOR
    public function toggleVisibility(User $user, Title $title): bool
    {
        return $user->isModerator();
    }
}
```

---

### `ReviewPolicy`

```php
// app/Policies/ReviewPolicy.php
class ReviewPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    // Tạo review: user đã active, chưa review title này
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    // Sửa review: chính chủ, review chưa bị ẩn bởi mod
    public function update(User $user, Review $review): bool
    {
        return $user->user_id === $review->user_id
            && $review->moderation_status !== 'HIDDEN';
    }

    // Xoá review: chính chủ
    public function delete(User $user, Review $review): bool
    {
        return $user->user_id === $review->user_id;
    }

    // Ẩn/hiện review: MODERATOR hoặc ADMIN
    public function moderate(User $user, Review $review): bool
    {
        return $user->isModerator();
    }

    // Xoá bởi mod (kèm penalty): MODERATOR/ADMIN
    public function forceDelete(User $user, Review $review): bool
    {
        return $user->isModerator();
    }

    // Vote helpful: user đã login, không phải chủ review
    public function voteHelpful(User $user, Review $review): bool
    {
        return $user->isActive()
            && $user->user_id !== $review->user_id;
    }
}
```

---

### `UserPolicy`

```php
// app/Policies/UserPolicy.php
class UserPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    // Xem danh sách users: chỉ ADMIN
    public function viewAny(User $user): bool
    {
        return false; // before() bypass cho ADMIN
    }

    // Đổi role / khoá account: chỉ ADMIN
    public function update(User $user, User $target): bool
    {
        // Admin không tự đổi role của mình
        return $user->user_id !== $target->user_id;
    }
}
```

---

## Dùng trong Controllers

```php
// Trong controller method
public function update(UpdateTitleRequest $request, Title $title): RedirectResponse
{
    $this->authorize('update', $title); // Ném 403 nếu không có quyền

    $this->titleService->update($title, $request->validated());
    return back()->with('success', 'Cập nhật thành công.');
}

public function toggleVisibility(Title $title): RedirectResponse
{
    $this->authorize('toggleVisibility', $title);
    // ...
}
```

---

## Dùng trong Inertia (truyền permissions xuống Vue)

Controller truyền `can` array xuống page:

```php
// Public\TitleController@show
return Inertia::render('Titles/Show', [
    'title' => $title,
    'can'   => [
        'review'         => auth()->check() && auth()->user()->can('create', Review::class),
        'toggleVisibility' => auth()->check() && auth()->user()->can('toggleVisibility', $title),
    ],
]);
```

Dùng trong Vue:

```vue
<script setup>
const props = defineProps({ title: Object, can: Object });
</script>

<template>
    <ReviewForm v-if="can.review" :title-id="title.id" />
    <button v-if="can.toggleVisibility" @click="hideTitle">Ẩn title</button>
</template>
```

---

## Shared Gate — `HandleInertiaRequests`

Với quyền **global** (không gắn với resource cụ thể), truyền qua shared props:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    $user = $request->user();

    return [
        ...parent::share($request),
        'auth' => [
            'user' => $user ? [
                'id'         => $user->user_id,
                'username'   => $user->username,
                'role'       => $user->role,
                'reputation' => $user->reputation,
                'avatar'     => $user->avatar_url,
            ] : null,
            'can' => [
                'accessAdmin'    => $user?->isAdmin() ?? false,
                'accessModerate' => $user && in_array($user->role, ['ADMIN', 'MODERATOR']),
            ],
        ],
        'flash' => [
            'success' => session('success'),
            'error'   => session('error'),
        ],
    ];
}
```

Dùng để ẩn/hiện menu Admin trong `AppLayout.vue`:

```vue
<script setup>
import { usePage } from "@inertiajs/vue3";
const { auth } = usePage().props;
</script>

<template>
    <Link v-if="auth.can.accessAdmin" href="/admin">Admin Panel</Link>
    <Link v-if="auth.can.accessModerate" href="/moderate">Kiểm duyệt</Link>
</template>
```

---

## Ma trận phân quyền (tổng hợp)

| Action                         | USER | MODERATOR | ADMIN |
| ------------------------------ | :--: | :-------: | :---: |
| Xem PUBLIC title               |  ✅  |    ✅     |  ✅   |
| Xem HIDDEN title               |  ❌  |    ✅     |  ✅   |
| Tạo / sửa / xoá title          |  ❌  |    ❌     |  ✅   |
| Ẩn / hiện title                |  ❌  |    ✅     |  ✅   |
| Viết review                    |  ✅  |    ✅     |  ✅   |
| Sửa review của mình            |  ✅  |    ✅     |  ✅   |
| Xoá review của mình            |  ✅  |    ✅     |  ✅   |
| Ẩn / xoá review của người khác |  ❌  |    ✅     |  ✅   |
| Vote helpful                   |  ✅  |    ✅     |  ✅   |
| Quản lý watchlist cá nhân      |  ✅  |    ✅     |  ✅   |
| Xem audit log                  |  ❌  |    ✅     |  ✅   |
| Quản lý users (đổi role, khoá) |  ❌  |    ❌     |  ✅   |
| Xem admin dashboard            |  ❌  |    ❌     |  ✅   |
