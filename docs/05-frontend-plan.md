# 05 — Frontend Plan

## Tổng quan

- **Framework:** Vue 3 (Composition API + `<script setup>`)
- **Bridge:** Inertia.js v2
- **CSS:** Tailwind CSS v4 (via `@tailwindcss/vite`)
- **Icons:** Heroicons v2 (`@heroicons/vue`)
- **State management:** Inertia shared data (không cần Pinia cho scope này)
- **HTTP:** Inertia router (không dùng Axios trực tiếp cho form/navigation)

---

## Design System

### Palette màu (Cinema dark theme)

```css
/* Định nghĩa CSS variables trong resources/css/app.css */
@theme {
    --color-bg-base: #0d0d0d; /* Nền tối chính */
    --color-bg-surface: #1a1a1a; /* Card, panel */
    --color-bg-elevated: #252525; /* Dropdown, modal */
    --color-accent: #e50914; /* Đỏ Netflix — CTA, highlight */
    --color-accent-hover: #b2070f;
    --color-text-primary: #f0f0f0;
    --color-text-secondary: #a0a0a0;
    --color-text-muted: #666666;
    --color-border: #2e2e2e;
    --color-rating-gold: #f5c518; /* Vàng IMDb cho sao rating */
}
```

### Typography

| Biến             | Font                      | Dùng cho       |
| ---------------- | ------------------------- | -------------- |
| `--font-display` | Inter / Plus Jakarta Sans | Tiêu đề lớn    |
| `--font-body`    | Inter                     | Văn bản thường |
| `--font-mono`    | JetBrains Mono            | Code, ID       |

---

## Cấu trúc thư mục `resources/js/`

```
resources/js/
├── app.js                          # Entry: mount Vue + Inertia
├── bootstrap.js                    # Axios token setup
│
├── Layouts/
│   ├── AppLayout.vue               # Public: header, footer, nav, flash
│   └── AdminLayout.vue             # Admin: sidebar + topbar
│
├── Components/
│   ├── UI/
│   │   ├── Button.vue              # Variants: primary, ghost, danger
│   │   ├── Badge.vue               # Genre tag, status badge
│   │   ├── Modal.vue               # Confirm modal (xoá, ẩn)
│   │   ├── Pagination.vue          # Từ Laravel paginator meta
│   │   ├── Alert.vue               # Flash success/error
│   │   ├── Spinner.vue
│   │   └── Dropdown.vue
│   ├── Form/
│   │   ├── TextInput.vue
│   │   ├── Textarea.vue
│   │   ├── Select.vue
│   │   ├── DateInput.vue
│   │   ├── FileUpload.vue          # Drag & drop, preview ảnh
│   │   └── StarRating.vue          # Input rating 1-10 (click/hover)
│   ├── Title/
│   │   ├── TitleCard.vue           # Poster + title + rating + type badge
│   │   ├── TitleGrid.vue           # Responsive grid của TitleCard
│   │   ├── TitleHero.vue           # Banner backdrop lớn (trang chủ)
│   │   └── TitleFilterBar.vue      # Search + filter by type/year/language
│   ├── Person/
│   │   ├── PersonCard.vue          # Ảnh + tên + vai trò
│   │   └── CastList.vue            # Danh sách cast/crew horizontal scroll
│   └── Review/
│       ├── ReviewCard.vue          # Avatar + rating + text + spoiler toggle
│       ├── ReviewForm.vue          # Gửi review mới
│       └── ReviewList.vue
│
└── Pages/
    ├── Home.vue                    # Trang chủ public
    │
    ├── Titles/
    │   ├── Index.vue               # Danh sách + filter + search
    │   └── Show.vue                # Chi tiết: thông tin, cast, reviews
    │
    ├── Persons/
    │   └── Show.vue                # Filmography của 1 người
    │
    ├── Studios/
    │   └── Show.vue                # Trang studio
    │
    ├── Auth/                       # Scaffold bởi Laravel Breeze
    │   ├── Login.vue
    │   ├── Register.vue
    │   ├── VerifyEmail.vue         # Màn hình chờ user bấm link xác thực trong email
    │   └── ForgotPassword.vue
    │
    └── Admin/
        ├── Dashboard.vue           # Stats cards + charts
        ├── Titles/
        │   ├── Index.vue           # Bảng titles + search + paginate
        │   └── Form.vue            # Dùng chung create/edit
        ├── Persons/
        │   ├── Index.vue
        │   └── Form.vue
        ├── Studios/
        │   ├── Index.vue
        │   └── Form.vue
        ├── Users/
        │   └── Index.vue           # Quản lý user, đổi role
        ├── Reviews/
        │   └── Index.vue           # Kiểm duyệt review
        └── AuditLog/
            └── Index.vue           # Lịch sử thay đổi title
```

---

## Layouts

### `AppLayout.vue`

```
┌─────────────────────────────────────────────────────────┐
│ 🎬 CinemaDB   [SmartSearchBar]  [vi/en] [🔔] [Account] │  ← Header (sticky)
├────┬────────────────────────────────────────────────┤
│ Trang chủ | Phim | Series | Diễn viên | Leaderboard | Feed  │  ← Navbar
├─────────────────────────────────────────────────────────┤
│                                                          │
│              <slot />   (page content)                   │
│                                                          │
├─────────────────────────────────────────────────────────┤
│              Footer: © 2026 CinemaDB                     │
└─────────────────────────────────────────────────────────┘
```

**Notification bell (🔔)**: hiển số unread ("9+" nếu >9). Click mở dropdown panel 380px listing notifications gần nhất. Badge toast tự dismiss sau 6s.

**LanguageSwitcher**: Chọn vi/en, lưu vào localStorage, reactive toàn trang qua `i18n.js` system.

### `AdminLayout.vue`

```
┌──────────┬───────────────────────────────────────────┐
│          │ ≡  CinemaDB Admin        👤 Admin ɞ    🔔     │ ← Topbar
│ Sidebar  ├───────────────────────────────────────────┤
│          │                                               │
│ Dashboard│         <slot />  (page content)              │
│ Titles   │                                               │
│ Persons  │                                               │
│ Studios  │                                               │
│ Users    │                                               │
│ Reviews  │                                               │
│ Badges   │                                               │
│ TMDB     │                                               │
│ Audit Log│                                               │
└──────────┴───────────────────────────────────────────┘
```

---

## Pages quan trọng

### `Home.vue`

```
┌──────────────────────────────────────────────────────────┐
│         [HERO BANNER — Featured title backdrop]          │
│         "Oppenheimer"   ⭐ 8.9   [Xem chi tiết]         │
├──────────────────────────────────────────────────────────┤
│  🔥 Mới nhất                              [Xem tất cả →] │
│  [Card] [Card] [Card] [Card] [Card] [Card]               │
├──────────────────────────────────────────────────────────┤
│  🎭 Hành động                             [Xem tất cả →] │
│  [Card] [Card] [Card] [Card] [Card] [Card]               │
└──────────────────────────────────────────────────────────┘
```

---

## Ghi chú cho flow đăng ký

- `Register.vue`: chỉ thu thập thông tin và submit đăng ký.
- Sau khi submit thành công, frontend chuyển user tới `VerifyEmail.vue` để hướng dẫn kiểm tra mailbox.
- User chỉ được xem là đăng nhập sau khi bấm link trong email và backend redirect về trang chủ.
- Không mô tả UX theo kiểu `register xong vào app ngay`; đó không còn là flow hiện tại.

### `Titles/Show.vue`

```
┌──────────────────────────────────────────────────────────┐
│  [Backdrop ảnh mờ]                                       │
│  ┌─────────┐  "Inception" (2010)                         │
│  │ Poster  │  ⭐ 8.8 · 245 lượt · 148 phút · MOVIE      │
│  │  2:3    │  Ngôn ngữ: English  |  Quốc gia: USA        │
│  └─────────┘  [▶ Trailer]  [AddToCollectionDropdown]     │
│                [NominateButton]                          │
│  Hãng: Warner Bros                                       │
│  Mô tả: ...                                              │
├──────────────────────────────────────────────────────────┤
│  Diễn viên chính  (horizontal scroll)                    │
│  [PersonCard] [PersonCard] [PersonCard]...               │
├──────────────────────────────────────────────────────────┤
│  Đạo diễn / Biên kịch                                    │
│  ...                                                     │
├──────────────────────────────────────────────────────────┤
│  Media Gallery  [MediaGallery]                           │
│  [Ảnh] [Ảnh] [Ảnh]...                                   │
├──────────────────────────────────────────────────────────┤
│  Đánh giá & Review (245)                                 │
│  [ReviewForm — nếu đã login, hỗ trợ GIF]                 │
│  [ReviewCard] [ReviewCard]...   [Paginate]               │
├──────────────────────────────────────────────────────────┤
│  Thảo luận (Comments)                                    │
│  [CommentForm]                                           │
│  [CommentCard] [CommentCard]...                          │
└──────────────────────────────────────────────────────────┘
```

### `Profile/Show.vue` (Public Profile)

```
┌──────────────────────────────────────────────────────────┐
│  [ProfileHero — cover image + avatar + username + rank]  │
│  [ProfileStats — reviews, followers, following]          │
│  [ProfileLevel — thanh tiến độ reputation]               │
│  [Follow/Unfollow button nếu khác người dùng]            │
├──────────────────────────────────────────────────────────┤
│  Huy hiệu   [UserBadges grid]                            │
├──────────────────────────────────────────────────────────┤
│  Lịch sử hoạt động  [ActivityCalendar]                   │
├──────────────────────────────────────────────────────────┤
│  Collections  [ProfileCollections grid]                  │
└──────────────────────────────────────────────────────────┘
```

### `Collections/Show.vue`

```
┌──────────────────────────────────────────────────────────┐
│  [Cover image]  "Tên bộ sưu tập"  by @username           │
│  Mô tả: ...   [Visibility: PUBLIC/PRIVATE]               │
│  [Copy collection] [Nominate] [Edit nếu là owner]        │
├──────────────────────────────────────────────────────────┤
│  Danh sách phim (n titles)                               │
│  [TitleCard + ghi chú + trạng thái watched]              │
│  [TitleCard] ...                                         │
├──────────────────────────────────────────────────────────┤
│  Cộng tác viên  [avatars]                                │
├──────────────────────────────────────────────────────────┤
│  Bình luận  [CollectionCommentForm]                      │
│  [CommentCard] [CommentCard]...                          │
└──────────────────────────────────────────────────────────┘
```

### `Leaderboards/Index.vue`

Bảng xếp hạng top users theo reputation. Hiển thị: rank, avatar, username, cấp bậc (`RankBadge`), reputation, số reviews.

### `Admin/TmdbImport.vue`

Form tìm kiếm phim trên TMDB bằng tên/ID, xem preview metadata, bấm Import để kích hoạt `ImportTmdbTitleJob`.

### `Admin/Titles/Form.vue` (Create + Edit)

```
┌──────────────────────────────────────────────────────────┐
│  Thêm Title mới / Chỉnh sửa "Inception"                  │
├───────────────────┬──────────────────────────────────────┤
│  [FileUpload      │  Title Name *        [Input]         │
│   Poster         │  Original Title       [Input]         │
│   drag & drop]  │  Type *   [Select: MOVIE/SERIES/...]  │
│                  │  Release Date         [DateInput]     │
│  [FileUpload      │  Runtime (phút)       [Input]        │
│   Backdrop]      │  Status               [Select]       │
│                  │  Visibility           [Select]       │
│                  │  Language             [Select]       │
│                  │  Trailer URL          [Input]        │
├───────────────────┴──────────────────────────────────────┤
│  Mô tả          [Textarea]                               │
├──────────────────────────────────────────────────────────┤
│  Hãng sản xuất  [Multi-select Studio]                    │
├──────────────────────────────────────────────────────────┤
│           [Huỷ]            [Lưu title]                   │
└──────────────────────────────────────────────────────────┘
```

---

## `app.js` — Entry point

```js
import "./bootstrap";
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import AppLayout from "./Layouts/AppLayout.vue";

createInertiaApp({
    title: (title) => `${title} — CinemaDB`,
    resolve: (name) => {
        const page = resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        );
        page.then((module) => {
            // Gán layout mặc định nếu page chưa set
            module.default.layout = module.default.layout ?? AppLayout;
        });
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: "#e50914", // Progress bar màu đỏ accent
    },
});
```

---

## Shared Props (Inertia)

Khai báo trong `HandleInertiaRequests` middleware:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user() ? [
                'id'       => $request->user()->user_id,
                'username' => $request->user()->username,
                'role'     => $request->user()->role,
            ] : null,
        ],
        'flash' => [
            'success' => session('success'),
            'error'   => session('error'),
        ],
    ];
}
```

Truy cập trong Vue:

```vue
<script setup>
import { usePage } from "@inertiajs/vue3";
const { auth, flash } = usePage().props;
</script>
```

---

## Hệ thống i18n (Đa ngôn ngữ)

Dự án tự xây i18n nhẹ — **không dùng vue-i18n package**. Kiến trúc:

```
resources/js/
├── i18n.js                  # Khởi tạo reactive locale, expose $t()
└── locales/
    ├── vi.js                # Tất cả chuỗi UI tiếng Việt (mặc định)
    └── en.js                # Tất cả chuỗi UI tiếng Anh
```

`LanguageSwitcher.vue` lưu lựa chọn vào `localStorage`. `useLocaleContent.js` dùng để chọn đúng trường nội dung title/person (`title_name_vi` vs `title_name`) theo ngôn ngữ hiện tại.

---

## Composables & Utils

| File                              | Mục đích                                                                                                                                                   |
| --------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `composables/useRank.js`          | Trả về `{ label, icon, color }` theo `reputation`. **Phải đồng bộ** với `LevelUpService.php`                                                               |
| `composables/useLocaleContent.js` | Reactive getter cho trường vi/en của title/person                                                                                                          |
| `utils/notifHelpers.js`           | Format tiêu đề + icon thông báo theo `type` (badge earned, level up, helpful, ...). Dùng trong `AppLayout` notification panel và `Notifications/Index.vue` |

---

## `vite.config.js` cập nhật

```js
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
});
```
