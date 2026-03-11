# 01 — Tổng quan dự án

## Mục tiêu

Xây dựng một hệ thống quản lý hồ sơ điện ảnh (Cinema CMS) hỗ trợ:

- Quản lý **Phim lẻ (MOVIE)**, **Series (SERIES)**, và **Tập phim (EPISODE)**
- Quản lý **Diễn viên**, **Đạo diễn**, **Biên kịch** và các vai trò khác
- Quản lý **Hãng sản xuất (Studios)**, **Thể loại**, **Quốc gia**, **Ngôn ngữ**
- Hệ thống **đánh giá / review** có kiểm soát spoiler, helpful votes
- **Phân quyền 3 tầng**: ADMIN — MODERATOR — USER
- **Kiểm duyệt nội dung**: ẩn/geo-block/copyright strike
- Audit log tự động mọi thay đổi trên bảng `titles`

---

## Tech stack

### Backend

| Thành phần   | Công nghệ                                  | Version          |
| ------------ | ------------------------------------------ | ---------------- |
| Framework    | Laravel                                    | 12.x             |
| Ngôn ngữ     | PHP                                        | ^8.2             |
| Database     | MySQL                                      | 8.4 (via Docker) |
| ORM          | Eloquent                                   | built-in         |
| Auth         | Laravel Breeze (Inertia stack)             | ~2.x             |
| File Upload  | Cloudinary (production) / Local disk (dev) | —                |
| Image Resize | Intervention Image (Laravel)               | ^3.x             |
| Auto-slug    | spatie/laravel-sluggable                   | ^3.x             |
| Permission   | spatie/laravel-permission                  | ^6.x             |
| Container    | Laravel Sail (Docker)                      | ^1.x             |
| Search       | Laravel Scout + Meilisearch                | —                |
| TMDB API     | Guzzle HTTP (qua TmdbService)              | —                |
| GIF Search   | Tenor API (qua TenorService)               | —                |

### Frontend

| Thành phần   | Công nghệ               | Version                      |
| ------------ | ----------------------- | ---------------------------- |
| UI Framework | Vue 3 (Composition API) | ^3.x                         |
| Bridge       | Inertia.js              | ^2.x                         |
| CSS          | Tailwind CSS            | v4 (via `@tailwindcss/vite`) |
| Build tool   | Vite                    | ^7.x                         |
| HTTP client  | Axios                   | ^1.x                         |
| Icon set     | Heroicons               | ^2.x                         |

### Hạ tầng

| Thành phần       | Công nghệ                                 |
| ---------------- | ----------------------------------------- | --- |
| Containerization | Docker Compose (Laravel Sail)             |
| Web server       | Nginx (trong Sail)                        |
| Cache / Queue    | Database driver (có thể swap sang Redis)  |
| Testing          | PHPUnit 11 + Pest                         |
| i18n             | Vue i18n thủ công (locales/vi.js + en.js) | —   |

---

## Tính năng chính

### Public (không cần đăng nhập)

- Trang chủ: Featured titles, mới nhất, theo thể loại
- Danh sách phim: filter theo thể loại, năm, ngôn ngữ, quốc gia; tìm kiếm full-text
- Chi tiết title: thông tin đầy đủ, cast & crew, reviews, trailer, media gallery
- Trang diễn viên / đạo diễn: filmography
- Trang studio: danh sách sản phẩm
- **Bảng xếp hạng (Leaderboard)** — top user theo reputation
- **Collections công khai** — browse bộ sưu tập cộng đồng
- **Profile công khai** `/users/{username}` — xem hồ sơ, badges, collections, activity

### User (đã đăng nhập, role USER)

- Viết / chỉnh sửa / xoá review của mình (hỗ trợ GIF qua Tenor)
- Đánh dấu "Helpful" cho review của người khác
- Xem lịch sử review
- **Tạo và quản lý bộ sưu tập (Collection)** tuỳ tên, công khai hoặc riêng tư
- **Cộng tác trên collection** — mời người khác cùng quản lý
- **Ghi chú cá nhân** + đánh dấu đã xem cho từng phim trong collection
- **Chia sẻ bộ sưu tập** bằng link public, copy collection của người khác
- **Bình luận / Thảo luận** trên trang chi tiết phim
- **Đề cử title** vào collection cộng đồng (Nominations)
- **Follow / Unfollow** user khác
- **Activity Feed** — xem hoạt động từ những người đang follow
- **Thông báo** (badge mới, follower mới, helpful vote, reply)
- Upload avatar và ảnh bìa profile
- **Đăng nhập Google** (OAuth 2.0)

### Moderator (role MODERATOR)

- Xem và ẩn/bỏ ẩn reviews vi phạm
- Xem audit log của titles

### Admin (role ADMIN)

- CRUD toàn bộ: Titles, Persons, Studios
- Quản lý Series → Seasons → Episodes
- Quản lý tài khoản người dùng (kích hoạt, đổi role, điều chỉnh reputation)
- Xem audit log đầy đủ
- Dashboard thống kê
- Quản lý huy hiệu (Badges): tạo, sửa, cài điều kiện và tier
- **Import phim từ TMDB** — tìm kiếm theo tên/ID, import tự động poster + metadata
- Kiểm duyệt review (ẩn, xoá, đổi trạng thái)
- **CRUD danh mục** — Countries, Languages, Genres, Vai trò phim (Roles)

### Tính năng nâng cao

- **Smart Search** — tìm kiếm instant theo từng phím gõ, chịu lỗi chính tả, lọc đa tầng (Meilisearch)
- **Bộ sưu tập xã hội** — tạo danh sách phim riêng, cộng tác, đề cử, bình luận, copy (Letterboxd-style)
- **Gamification** — hệ thống huy hiệu (BRONZE/SILVER/GOLD/PLATINUM), cấp bậc theo điểm uy tín, notification khi đạt badge
- **Social Graph** — follow/unfollow, activity feed, public profiles
- **TMDB Integration** — import tự động từ The Movie Database API
- **GIF Support** — thêm GIF vào review/comment qua Tenor API
- **Đa ngôn ngữ (i18n)** — giao diện tiếng Việt và tiếng Anh

---

## Skills Agent cần sử dụng

### Đã cài sẵn (built-in)

| Skill                                 | Phase | Nhiệm vụ                                                                         |
| ------------------------------------- | ----- | -------------------------------------------------------------------------------- |
| **`frontend-design`**                 | 5, 11 | Thiết kế Vue 3 UI cinema dark theme, Tailwind v4, collection pages, poster grid  |
| **`code-refactoring-refactor-clean`** | 3, 12 | Thin controller, Service layer, Event/Listener Gamification — tuân thủ SOLID/DRY |
| **`data-engineer`**                   | 1, 10 | Thiết kế schema MySQL (trigger/view/procedure), tối ưu Meilisearch index         |
| **`ai-engineer`**                     | 10    | Tuning typo tolerance, ranking rules, synonym lists cho Smart Search             |
| **`webapp-testing`**                  | 8     | Feature/Unit tests, Playwright E2E testing                                       |

### Mới cài (vừa tải xuống)

| Skill                           | Nguồn                         | Phase   | Nhiệm vụ                                                                                 |
| ------------------------------- | ----------------------------- | ------- | ---------------------------------------------------------------------------------------- | --- | ------------------- | -------------------------------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------- |
| **`laravel-quality`**           | `leeovery/claude-laravel`     | 2, 3, 4 | Chuẩn code Laravel: naming conventions, Eloquent best practices, PHPDoc, Pint formatting |
| **`laravel-architecture`**      | `fusengine/agents`            | 3       | Service layer pattern, Repository pattern, thin controller, DI trong Laravel             |
| **`solid-php`**                 | `fusengine/agents`            | 2, 3    | Áp dụng SOLID principles vào model/service/controller PHP                                |
| **`visual-design-foundations`** | `wshobson/agents`             | 5, 11   | Nền tảng thiết kế visual: typography, spacing, color theory cho giao diện cinema         |
| **`tailwindcss`**               | `timelessco/recollect`        | 5, 11   | Best practices Tailwind CSS v4, utility patterns, responsive design                      |
| **`docker`**                    | `bobmatnyc/claude-mpm-skills` | 0, 9    | Docker Compose, Sail configuration, container networking, volume management              |
| **`meilisearch-admin`**         | `civitai/civitai`             | 10      | Cấu hình Meilisearch index, settings API, filterable/sortable attributes                 |     | **`ui-ux-pro-max`** | `nextlevelbuilder/ui-ux-pro-max-skill` | Phase 5, 11 | 67 UI styles, 96 color palettes, 57 font pairings, design system generator cho Vue + Tailwind. Dùng lệnh `/ui-ux-pro-max` |

---

## Cấu trúc thư mục dự kiến (tóm tắt)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # CRUD cho admin
│   │   ├── Public/          # Trang công khai
│   │   └── ReviewController.php
│   ├── Middleware/
│   │   └── EnsureRole.php
│   └── Requests/            # Form validation
├── Models/
│   ├── Title.php            # Movie / Series / Episode
│   ├── Series.php
│   ├── Season.php
│   ├── Episode.php
│   ├── Person.php
│   ├── Studio.php
│   ├── Review.php
│   ├── Country.php
│   ├── Language.php
│   ├── Role.php
│   ├── Collection.php           # Bộ sưu tập của user
│   ├── CollectionCollaborator.php
│   ├── CollectionComment.php
│   ├── CollectionNomination.php
│   ├── CollectionTitleNote.php
│   ├── Comment.php              # Bình luận trên trang title
│   ├── Nomination.php           # Đề cử title
│   ├── FeedItem.php             # Activity feed
│   ├── TitleMedia.php           # Gallery media
│   ├── UserActivityLog.php      # Log hoạt động user
│   ├── TmdbImportLog.php        # Log import từ TMDB
│   ├── Genre.php                # Thể loại phim
│   └── Badge.php                # Huy hiệu gamification
├── Events/
│   ├── ReviewCreated.php
│   ├── ReviewDeleted.php
│   ├── HelpfulVoteToggled.php
│   └── UserFollowed.php
├── Listeners/
│   ├── AwardBadgesOnReview.php
│   └── UpdateReputationOnHelpful.php
└── Services/                # Business logic tách riêng
    ├── TitleService.php
    ├── ReviewService.php
    ├── PersonService.php
    ├── StudioService.php
    ├── GamificationService.php
    ├── BadgeAwardService.php
    ├── BadgeConditionEvaluator.php
    ├── LevelUpService.php
    ├── FeedService.php
    ├── CloudinaryService.php    # Upload ảnh lên Cloudinary
    ├── TmdbService.php          # Gọi TMDB API
    ├── TmdbImportService.php    # Import title từ TMDB
    ├── TenorService.php         # Tìm GIF qua Tenor
    └── NominationQuota.php      # Giới hạn số lần đề cử

resources/js/
├── Pages/
│   ├── Admin/
│   └── Public/
├── Layouts/
│   ├── AppLayout.vue
│   └── AdminLayout.vue
└── Components/

database/
├── migrations/              # 15+ migration files
├── seeders/
└── factories/
```

---

## Ràng buộc & quyết định

- Dùng bảng `titles` duy nhất cho cả Movie/Series/Episode (discriminator `title_type`) — chuẩn hóa theo mô hình IMDB/TMDB
- `avg_rating` được **denormalize** trực tiếp trên `titles` và cập nhật bằng MySQL **Trigger** — tránh aggregate query nặng mỗi lần render
- Upload media dùng **Cloudinary** (production-ready CDN); `CloudinaryService` bọc toàn bộ logic upload, resize, delete
- **Local disk `public`** vẫn dùng khi develop offline (có thể swap qua env)
- Inertia.js thay vì SPA thuần: session auth native Laravel, không cần CORS, dễ SEO hơn
- **Soft deletes** được bật trên các bảng core (`titles`, `reviews`, `comments`) — dữ liệu không bị xoá vật lý
- **Google OAuth** tích hợp qua `google_id` column trên `users` và route auth Socialite
- **i18n** dùng hệ thống tự xây (không dùng vue-i18n package) — 2 locales: `vi.js` và `en.js`, composable `useLocaleContent.js`
