# Movie Database — Tài liệu dự án

> Hệ thống quản lý hồ sơ điện ảnh (phim lẻ, series, tập phim) xây dựng trên **Laravel 12**, **Inertia.js + Vue 3**, **Tailwind CSS v4**, chạy trong **Docker (Laravel Sail + MySQL 8.4)**.

---

## Mục lục tài liệu

| File                                                         | Nội dung                                                       |
| ------------------------------------------------------------ | -------------------------------------------------------------- |
| [01-project-overview.md](./01-project-overview.md)           | Tổng quan dự án, mục tiêu, tech stack, skills cần dùng         |
| [02-architecture.md](./02-architecture.md)                   | Kiến trúc hệ thống, flow request, quyết định thiết kế          |
| [03-database-schema.md](./03-database-schema.md)             | Toàn bộ schema MySQL, bảng, view, trigger, function, procedure |
| [04-backend-plan.md](./04-backend-plan.md)                   | Models, Controllers, Routes, Middleware, Form Requests         |
| [05-frontend-plan.md](./05-frontend-plan.md)                 | Cấu trúc Vue 3, Pages, Components, Layouts, UI Design system   |
| [06-implementation-phases.md](./06-implementation-phases.md) | Lộ trình triển khai từng phase, lệnh chạy, checklist           |
| [07-advanced-features.md](./07-advanced-features.md)         | Smart Search (Meilisearch), Social Watchlist, Gamification     |
| [08-authorization-plan.md](./08-authorization-plan.md)       | Policies, Gates, ma trận phân quyền theo role                  |
| [09-inertia-props-spec.md](./09-inertia-props-spec.md)       | Inertia props contract cho từng page (Public + Admin)          |

---

## Khởi động nhanh

```bash
# 1. Khởi động Docker
./vendor/bin/sail up -d

# 2. Cài PHP dependencies
./vendor/bin/sail composer install

# 3. Copy .env và cấu hình
cp .env.example .env
./vendor/bin/sail artisan key:generate

# 4. Chạy migrations + seed dữ liệu mẫu
./vendor/bin/sail artisan migrate --seed

# 5. Cài JS dependencies + build frontend
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Truy cập: **http://localhost**

---

## Luồng đăng ký hiện tại

Flow đăng ký của dự án không phải là đăng ký xong đăng nhập ngay.

1. User nhập thông tin và submit form đăng ký.
2. Backend tạo tài khoản và gửi mail xác thực.
3. User nhấn link trong email.
4. Backend xác thực email thành công, tự động đăng nhập user.
5. User được redirect về trang chủ.

Khi đọc các tài liệu kiến trúc, backend, frontend và testing, hãy hiểu toàn bộ flow auth theo mô hình này.

---

## Tài khoản mặc định (sau seed)

| Role      | Email              | Password |
| --------- | ------------------ | -------- |
| ADMIN     | admin@cinema.local | password |
| MODERATOR | mod@cinema.local   | password |
| USER      | user@cinema.local  | password |
