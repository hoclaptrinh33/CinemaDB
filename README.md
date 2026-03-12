<div align="center">

# 🎬 CinemaDB

**Hệ thống quản lý hồ sơ điện ảnh cộng đồng**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-blue?logo=php)](https://php.net)
[![Vue](https://img.shields.io/badge/Vue-3.x-green?logo=vue.js)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-v4-38bdf8?logo=tailwindcss)](https://tailwindcss.com)

</div>

---

## Giới thiệu

CinemaDB là một nền tảng quản lý hồ sơ điện ảnh cộng đồng, cho phép người dùng khám phá, đánh giá và chia sẻ thông tin về phim, series và nhân vật điện ảnh. Hệ thống tích hợp nhiều tính năng xã hội như follow, collections, gamification (huy hiệu, cấp bậc, điểm uy tín) và tìm kiếm full-text siêu tốc.

---

## Tech Stack

### Backend

| Thành phần | Công nghệ                   | Version |
| ---------- | --------------------------- | ------- |
| Framework  | Laravel                     | 12.x    |
| Ngôn ngữ   | PHP                         | 8.4     |
| Database   | MySQL                       | 8.4     |
| Auth       | Laravel Breeze + Socialite  | —       |
| Search     | Laravel Scout + Meilisearch | —       |
| Media      | Cloudinary                  | —       |
| Queue      | Database driver             | —       |
| Permission | spatie/laravel-permission   | ^6.x    |
| Slug       | spatie/laravel-sluggable    | ^3.x    |
| Image      | Intervention Image          | ^3.x    |
| Container  | Laravel Sail (Docker)       | ^1.x    |

### Frontend

| Thành phần   | Công nghệ               | Version |
| ------------ | ----------------------- | ------- |
| UI Framework | Vue 3 (Composition API) | ^3.x    |
| Bridge       | Inertia.js              | ^2.x    |
| CSS          | Tailwind CSS            | v4      |
| Build Tool   | Vite                    | ^7.x    |
| HTTP Client  | Axios                   | ^1.x    |
| Icons        | Heroicons               | ^2.x    |

### Hạ tầng

| Thành phần | Công nghệ                     |
| ---------- | ----------------------------- |
| Container  | Docker Compose (Laravel Sail) |
| Web Server | Nginx                         |
| Testing    | PHPUnit 11 + Pest             |
| Cache      | Database driver               |

---

## Tính năng

### 🌐 Public (không cần đăng nhập)

- Trang chủ với Featured Titles, mới nhất, theo thể loại
- Danh sách phim: lọc theo thể loại, năm, ngôn ngữ, quốc gia
- Tìm kiếm full-text siêu tốc (chịu lỗi chính tả) qua Meilisearch
- Chi tiết title: thông tin đầy đủ, cast & crew, reviews, trailer, media gallery
- Trang diễn viên / đạo diễn với filmography
- Trang studio với danh sách sản phẩm
- Bảng xếp hạng (Leaderboard) — top user theo reputation
- Collections công khai — browse bộ sưu tập cộng đồng
- Profile công khai `/users/{username}` — xem hồ sơ, badges, collections

### 👤 User (đã đăng nhập)

- Viết, chỉnh sửa, xoá review của mình (hỗ trợ GIF qua Tenor API)
- Đánh dấu "Helpful" cho review của người khác
- Bình luận / thảo luận trên trang chi tiết phim (có reply, like, ẩn)
- Tạo và quản lý bộ sưu tập (Collection) — công khai hoặc riêng tư
- Mời cộng tác viên cùng quản lý collection
- Ghi chú cá nhân và đánh dấu đã xem cho từng phim trong collection
- Chia sẻ collection bằng link public, copy collection của người khác
- Đề cử title vào collection cộng đồng (Nominations)
- Follow / Unfollow user khác
- Activity Feed — xem hoạt động từ những người đang follow
- Thông báo real-time (badge mới, follower mới, helpful vote, reply bình luận)
- Upload avatar và ảnh bìa profile
- Đăng nhập bằng Google (OAuth 2.0)

### 🛡️ Moderator

- Xem, ẩn và bỏ ẩn reviews vi phạm
- Xem audit log của titles

### ⚙️ Admin

- CRUD đầy đủ: Titles, Persons, Studios, Genres, Languages, Countries
- Quản lý Series → Seasons → Episodes
- Import phim tự động từ TMDB API
- Quản lý tài khoản người dùng (kích hoạt, đổi role, điều chỉnh reputation)
- Xem audit log đầy đủ

---

## Tính năng nâng cao

### 🔍 Smart Search

Tìm kiếm siêu tốc dưới 50ms với khả năng chịu lỗi chính tả, instant search theo từng phím gõ, lọc đa tầng và hỗ trợ tiếng Việt — thông qua Laravel Scout + Meilisearch.

### 🏆 Gamification

Hệ thống điểm uy tín (reputation), cấp bậc người dùng và huy hiệu (badges) được phát thưởng tự động qua Event/Listener khi người dùng có hoạt động tích cực (viết review, nhận helpful vote, đạt mốc...).

### 🌐 Social Graph

Follow/Unfollow người dùng, Activity Feed cá nhân hoá, hệ thống thông báo và public profile đầy đủ.

### 🎬 TMDB Integration

Import phim và người nổi tiếng tự động từ The Movie Database (TMDB) API thông qua Queue Jobs, giúp xây dựng dữ liệu nhanh chóng.

### 🖼️ GIF Support

Tích hợp Tenor API cho phép đính kèm GIF trực tiếp trong reviews và bình luận.

---

## Yêu cầu

- **Docker Desktop** (cho Laravel Sail)
- PHP 8.2+ & Composer 2 (nếu chạy local không dùng Docker)
- Node.js 20+

---

## Cài đặt nhanh (Docker — Khuyến nghị)

```bash
# 1. Copy biến môi trường
cp .env.example .env

# 2. Cài PHP dependencies (không cần cài PHP local)
docker run --rm -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 3. Khởi động tất cả containers (Laravel + MySQL + Meilisearch)
./vendor/bin/sail up -d

# 4. Sinh app key
./vendor/bin/sail artisan key:generate

# 5. Chạy migrations và seed dữ liệu mẫu
./vendor/bin/sail artisan migrate:fresh --seed

# 6. Cài và build frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 7. Import dữ liệu vào Meilisearch
./vendor/bin/sail artisan scout:import "App\Models\Title"
```

Truy cập: **http://localhost**

---

## Cài đặt local (không Docker)

```bash
cp .env.example .env
# Sửa DB_*, MEILISEARCH_*, CLOUDINARY_URL trong .env

composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install && npm run build
php artisan serve
```

---

## Chạy development server

```bash
composer dev
# Khởi động đồng thời: PHP server, Queue worker, Pail logs, Vite dev server
```

---

## Chạy tests

```bash
composer test
# hoặc
php artisan test
```

---

## Cấu hình biến môi trường quan trọng

| Biến                     | Mô tả                                                    |
| ------------------------ | -------------------------------------------------------- |
| `DB_DATABASE`            | Tên database MySQL                                       |
| `DB_USERNAME`            | Username MySQL                                           |
| `DB_PASSWORD`            | Password MySQL                                           |
| `CLOUDINARY_URL`         | `cloudinary://api_key:api_secret@cloud_name`             |
| `MEILISEARCH_HOST`       | URL tới Meilisearch (mặc định `http://meilisearch:7700`) |
| `MEILISEARCH_KEY`        | Master key của Meilisearch                               |
| `TMDB_READ_ACCESS_TOKEN` | Read Access Token từ TMDB API                            |
| `GOOGLE_CLIENT_ID`       | Google OAuth Client ID                                   |
| `GOOGLE_CLIENT_SECRET`   | Google OAuth Client Secret                               |
| `GOOGLE_REDIRECT_URI`    | Callback URL cho Google OAuth                            |
| `MAIL_MAILER`            | Driver gửi mail (`resend`, `smtp`, `log`...)             |
| `RESEND_API_KEY`         | API key Resend (cho email production)                    |
| `TENOR_API_KEY`          | API key Tenor (cho tính năng GIF)                        |

---

## Tài khoản mặc định (sau seed)

| Role      | Email               | Password   |
| --------- | ------------------- | ---------- |
| ADMIN     | `admin@example.com` | `password` |
| MODERATOR | `mod@example.com`   | `password` |
| USER      | `user@example.com`  | `password` |

---

## Cấu trúc dự án

```
app/
├── Console/Commands/       # Artisan commands
├── Enums/                  # UserRole enum
├── Events/                 # Domain events
├── Http/
│   ├── Controllers/        # Thin controllers
│   ├── Middleware/         # Custom middleware
│   └── Requests/           # Form Request validation
├── Jobs/                   # Queue jobs (TMDB import...)
├── Listeners/              # Event listeners (badges, reputation...)
├── Models/                 # Eloquent models
├── Notifications/          # Laravel notifications
├── Policies/               # Authorization policies
└── Services/               # Business logic layer
resources/
├── css/                    # Tailwind CSS entry
├── js/                     # Vue 3 components & pages
└── views/                  # Blade templates (app shell)
database/
├── factories/              # Model factories
├── migrations/             # Database migrations
└── seeders/                # Database seeders
docs/                       # Tài liệu kiến trúc chi tiết
```

---

## Tài liệu dự án

Xem thư mục [`docs/`](docs/) để biết chi tiết kiến trúc, database schema, kế hoạch triển khai và tính năng nâng cao.

| File                                                            | Nội dung                                     |
| --------------------------------------------------------------- | -------------------------------------------- |
| [01-project-overview.md](docs/01-project-overview.md)           | Tổng quan dự án & tech stack                 |
| [02-architecture.md](docs/02-architecture.md)                   | Kiến trúc hệ thống & request flow            |
| [03-database-schema.md](docs/03-database-schema.md)             | Schema database đầy đủ                       |
| [04-backend-plan.md](docs/04-backend-plan.md)                   | Kế hoạch backend                             |
| [05-frontend-plan.md](docs/05-frontend-plan.md)                 | Kế hoạch frontend & UI/UX                    |
| [06-implementation-phases.md](docs/06-implementation-phases.md) | Lộ trình triển khai theo phase               |
| [07-advanced-features.md](docs/07-advanced-features.md)         | Tính năng nâng cao (Search, Gamification...) |
| [08-authorization-plan.md](docs/08-authorization-plan.md)       | Phân quyền & authorization                   |
| [09-inertia-props-spec.md](docs/09-inertia-props-spec.md)       | Inertia props specification                  |

---

## License

Dự án này được phân phối theo giấy phép [MIT](LICENSE).
