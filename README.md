<p align="center"><strong>CinemaDB</strong> — Hệ thống quản lý hồ sơ điện ảnh</p>

## Tech Stack

- **Backend**: Laravel 12, PHP 8.4, MySQL 8.4
- **Frontend**: Vue 3 + Inertia.js + Tailwind CSS v4
- **Search**: Laravel Scout + Meilisearch
- **Media**: Cloudinary
- **Auth**: Laravel Breeze + Google OAuth (Socialite)
- **Container**: Laravel Sail (Docker)

## Yêu cầu

- Docker Desktop (cho Laravel Sail)
- PHP 8.2+ & Composer 2 (nếu chạy local)
- Node.js 20+

## Cài đặt nhanh (Docker)

```bash
# 1. Copy biến môi trường
cp .env.example .env

# 2. Cài PHP dependencies
docker run --rm -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 3. Khởi động containers
./vendor/bin/sail up -d

# 4. Sinh app key
./vendor/bin/sail artisan key:generate

# 5. Chạy migrations + seed dữ liệu mẫu
./vendor/bin/sail artisan migrate:fresh --seed

# 6. Build frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# 7. Index Meilisearch
./vendor/bin/sail artisan scout:import "App\Models\Title"
```

Truy cập: **http://localhost**

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

## Chạy development server

```bash
composer dev
# Khởi động đồng thời: PHP server, Queue worker, Pail logs, Vite
```

## Chạy tests

```bash
composer test
# hoặc
php artisan test
```

## Luồng đăng ký tài khoản

Luồng đăng ký hiện tại của hệ thống là:

1. Người dùng nhập `name`, `username`, `email`, `password` và bấm `Đăng ký`.
2. Hệ thống tạo tài khoản ở trạng thái chưa xác thực email và gửi mail xác thực.
3. Người dùng mở email và nhấn vào link xác thực.
4. Hệ thống đánh dấu `email_verified_at`, xác thực thành công và tự động đăng nhập user.
5. User được chuyển về trang chủ với trạng thái đã đăng nhập.

Lưu ý: sau khi submit form đăng ký, user chưa được đăng nhập ngay. Việc đăng nhập chỉ diễn ra sau khi bấm đúng link xác thực trong email.

## Cấu hình biến môi trường quan trọng

| Biến                     | Mô tả                                                    |
| ------------------------ | -------------------------------------------------------- |
| `CLOUDINARY_URL`         | `cloudinary://api_key:api_secret@cloud_name`             |
| `MEILISEARCH_HOST`       | URL tới Meilisearch (mặc định `http://meilisearch:7700`) |
| `MEILISEARCH_KEY`        | Master key của Meilisearch                               |
| `TMDB_READ_ACCESS_TOKEN` | Token đọc từ TMDB API                                    |
| `GOOGLE_CLIENT_ID`       | Google OAuth Client ID                                   |
| `GOOGLE_CLIENT_SECRET`   | Google OAuth Client Secret                               |
| `RESEND_API_KEY`         | API key Resend cho email production                      |

## Tài khoản mặc định (sau seed)

| Role      | Email               | Password   |
| --------- | ------------------- | ---------- |
| ADMIN     | `admin@example.com` | `password` |
| MODERATOR | `mod@example.com`   | `password` |
| USER      | `user@example.com`  | `password` |

## Tài liệu dự án

Xem thư mục [`docs/`](docs/) để biết chi tiết kiến trúc, schema, và roadmap.
