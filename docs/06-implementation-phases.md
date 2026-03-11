# 06 — Lộ trình triển khai (Implementation Phases)

## Tổng quan timeline

| Phase  | Nội dung                       | Skill cần                         | Ước tính |
| ------ | ------------------------------ | --------------------------------- | -------- |
| **0**  | Môi trường & cài packages      | —                                 | 1 ngày   |
| **1**  | Database schema & migrations   | `data-engineer`                   | 2 ngày   |
| **2**  | Models & Eloquent              | —                                 | 1 ngày   |
| **3**  | Controllers, Routes, Services  | `code-refactoring-refactor-clean` | 3 ngày   |
| **4**  | Seeders & Factories            | —                                 | 1 ngày   |
| **5**  | Frontend Vue 3 + Inertia       | `frontend-design`                 | 5 ngày   |
| **6**  | File Upload & Media            | —                                 | 1 ngày   |
| **7**  | Search & Filter (cơ bản)       | —                                 | 1 ngày   |
| **8**  | Testing                        | —                                 | 2 ngày   |
| **9**  | Polish & Deploy prep           | —                                 | 1 ngày   |
| **10** | Smart Search — Meilisearch     | `data-engineer` + `ai-engineer`   | 2 ngày   |
| **11** | Social Watchlist               | `frontend-design`                 | 2 ngày   |
| **12** | Gamification                   | `code-refactoring-refactor-clean` | 3 ngày   |
| **13** | Auth & Email Verification UX   | —                                 | 1 ngày   |
| **14** | Stabilization & Prod Readiness | —                                 | 2 ngày   |
| **15** | Staging Rehearsal              | —                                 | 1 ngày   |
| **16** | Production Deploy              | —                                 | 1 ngày   |

---

## Phase 0 — Cài đặt môi trường

### Checklist

- [ ] Tạo file `.env` từ `.env.example`
- [ ] Cấu hình database trong `.env`
- [ ] Cài Laravel Breeze với stack Inertia + Vue
- [ ] Cài thêm packages PHP cần thiết
- [ ] Cài thêm packages JS cần thiết
- [ ] Tạo storage symlink

### Lệnh thực thi

```bash
# 1. Copy .env và sinh app key
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate

# 2. Sửa .env: đổi DB_CONNECTION về mysql
# DB_CONNECTION=mysql
# DB_HOST=mysql
# DB_PORT=3306
# DB_DATABASE=movie_database
# DB_USERNAME=sail
# DB_PASSWORD=password

# 3. Cài Laravel Breeze (Inertia + Vue stack)
./vendor/bin/sail composer require laravel/breeze --dev
./vendor/bin/sail artisan breeze:install vue

# 4. Cài thêm PHP packages
./vendor/bin/sail composer require \
    spatie/laravel-sluggable \
    intervention/image-laravel

# 5. Cài JS packages
./vendor/bin/sail npm install \
    @heroicons/vue

# 6. Build
./vendor/bin/sail npm run build

# 7. Storage symlink
./vendor/bin/sail artisan storage:link
```

### Cấu hình `.env`

```env
# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=movie_database
DB_USERNAME=sail
DB_PASSWORD=password

# Meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=masterKey
SCOUT_DRIVER=meilisearch
FORWARD_MEILISEARCH_PORT=7700
```

---

## Phase 1 — Database Schema

### Checklist

- [ ] Sửa migration `create_users_table` — thêm `role`, `username`, `is_active`
- [ ] Tạo migration `create_countries_table`
- [ ] Tạo migration `create_languages_table`
- [ ] Tạo migration `create_titles_table` — thêm cột `slug`
- [ ] Tạo migration `create_series_table`
- [ ] Tạo migration `create_seasons_table`
- [ ] Tạo migration `create_episodes_table`
- [ ] Tạo migration `create_studios_table`
- [ ] Tạo migration `create_title_studios_table`
- [ ] Tạo migration `create_persons_table`
- [ ] Tạo migration `create_roles_table`
- [ ] Tạo migration `create_title_person_roles_table`
- [ ] Tạo migration `create_reviews_table`
- [ ] Tạo migration `create_title_audit_log_table`
- [ ] Tạo migration `create_db_objects` — triggers, views, procedures
- [ ] Tạo các file SQL trong `database/sql/`
- [ ] Chạy và verify: `migrate:fresh`

### Lệnh thực thi

```bash
# Tạo migration files
./vendor/bin/sail artisan make:migration create_countries_table
./vendor/bin/sail artisan make:migration create_languages_table
./vendor/bin/sail artisan make:migration create_titles_table
./vendor/bin/sail artisan make:migration create_series_table
./vendor/bin/sail artisan make:migration create_seasons_table
./vendor/bin/sail artisan make:migration create_episodes_table
./vendor/bin/sail artisan make:migration create_studios_table
./vendor/bin/sail artisan make:migration create_title_studios_table
./vendor/bin/sail artisan make:migration create_persons_table
./vendor/bin/sail artisan make:migration create_roles_table
./vendor/bin/sail artisan make:migration create_title_person_roles_table
./vendor/bin/sail artisan make:migration create_reviews_table
./vendor/bin/sail artisan make:migration create_title_audit_log_table
./vendor/bin/sail artisan make:migration create_db_objects

# Chạy migrations
./vendor/bin/sail artisan migrate:fresh

# Verify
./vendor/bin/sail artisan db:show
```

> **Lưu ý về triggers trong migration:** Dùng `DB::unprepared()` để chạy SQL với `DELIMITER`. Xem chi tiết trong [03-database-schema.md](./03-database-schema.md#lưu-ý-migration-create_db_objectsphp).

---

## Phase 2 — Models & Eloquent

### Checklist

- [ ] Cập nhật `User` model
- [ ] Tạo `Title` model + HasSlug + scopes + accessors
- [ ] Tạo `Series`, `Season`, `Episode` models
- [ ] Tạo `Person` model
- [ ] Tạo `Role` model (đây là role trong phim, không phải user role)
- [ ] Tạo `Studio` model
- [ ] Tạo `Country`, `Language` models
- [ ] Tạo `Review` model
- [ ] Tạo `TitlePersonRole` pivot model (dùng `using()`)
- [ ] Tạo `TitleAuditLog` model (read-only)
- [ ] Verify relationships với `php artisan tinker`

### Lệnh thực thi

```bash
./vendor/bin/sail artisan make:model Title
./vendor/bin/sail artisan make:model Series
./vendor/bin/sail artisan make:model Season
./vendor/bin/sail artisan make:model Episode
./vendor/bin/sail artisan make:model Person
./vendor/bin/sail artisan make:model Role
./vendor/bin/sail artisan make:model Studio
./vendor/bin/sail artisan make:model Country
./vendor/bin/sail artisan make:model Language
./vendor/bin/sail artisan make:model Review
./vendor/bin/sail artisan make:model TitlePersonRole
./vendor/bin/sail artisan make:model TitleAuditLog
```

---

## Phase 3 — Controllers, Routes, Services

### Checklist

- [ ] Tạo `EnsureRole` middleware và đăng ký
- [ ] Tạo `HandleInertiaRequests` middleware (Breeze tạo sẵn) và cấu hình shared props
- [ ] Tạo `TitleService`, `ReviewService`, `PersonService`
- [ ] Tạo tất cả Form Requests
- [ ] Tạo tất cả Controllers (Admin, Public, Moderate)
- [ ] Cập nhật `routes/web.php` đầy đủ
- [ ] Test routes: `route:list`

### Lệnh thực thi

```bash
# Services (tạo thủ công hoặc dùng make:class nếu cài package)
mkdir -p app/Services

# Middleware
./vendor/bin/sail artisan make:middleware EnsureRole

# Form Requests
./vendor/bin/sail artisan make:request Admin/StoreTitleRequest
./vendor/bin/sail artisan make:request Admin/UpdateTitleRequest
./vendor/bin/sail artisan make:request Admin/StorePersonRequest
./vendor/bin/sail artisan make:request Admin/UpdatePersonRequest
./vendor/bin/sail artisan make:request Admin/StoreStudioRequest
./vendor/bin/sail artisan make:request StoreReviewRequest

# Controllers
./vendor/bin/sail artisan make:controller Admin/DashboardController
./vendor/bin/sail artisan make:controller Admin/TitleController --resource
./vendor/bin/sail artisan make:controller Admin/PersonController --resource
./vendor/bin/sail artisan make:controller Admin/StudioController --resource
./vendor/bin/sail artisan make:controller Admin/SeasonController
./vendor/bin/sail artisan make:controller Admin/EpisodeController
./vendor/bin/sail artisan make:controller Admin/ReviewController
./vendor/bin/sail artisan make:controller Admin/UserController
./vendor/bin/sail artisan make:controller Admin/AuditLogController
./vendor/bin/sail artisan make:controller Public/HomeController
./vendor/bin/sail artisan make:controller Public/TitleController
./vendor/bin/sail artisan make:controller Public/PersonController
./vendor/bin/sail artisan make:controller Moderate/ReviewController
./vendor/bin/sail artisan make:controller ReviewController

# Verify
./vendor/bin/sail artisan route:list
```

---

## Phase 4 — Seeders & Factories

### Checklist

- [ ] `CountrySeeder` — 50 quốc gia
- [ ] `LanguageSeeder` — 20 ngôn ngữ
- [ ] `RoleSeeder` — 7 vai trò phim
- [ ] `UserSeeder` — 3 accounts (admin, mod, user)
- [ ] `StudioSeeder` — 10 hãng sản xuất
- [ ] `TitleFactory` (Faker)
- [ ] `PersonFactory` (Faker)
- [ ] `TitleSeeder` — 20 phim mẫu với quan hệ đầy đủ
- [ ] Cập nhật `DatabaseSeeder`
- [ ] Chạy seed và verify

### Lệnh thực thi

```bash
./vendor/bin/sail artisan make:seeder CountrySeeder
./vendor/bin/sail artisan make:seeder LanguageSeeder
./vendor/bin/sail artisan make:seeder RoleSeeder
./vendor/bin/sail artisan make:seeder UserSeeder
./vendor/bin/sail artisan make:seeder StudioSeeder
./vendor/bin/sail artisan make:seeder TitleSeeder
./vendor/bin/sail artisan make:factory TitleFactory
./vendor/bin/sail artisan make:factory PersonFactory

# Chạy seed
./vendor/bin/sail artisan migrate:fresh --seed

# Verify
./vendor/bin/sail artisan tinker
>>> Title::count()
>>> User::count()
>>> Review::count()
```

---

## Phase 5 — Frontend Vue 3 + Inertia

> ⚠️ **Kích hoạt skill `frontend-design`** trước khi bắt đầu phase này.

### Checklist

- [ ] Cập nhật `vite.config.js` — thêm `@vitejs/plugin-vue` + Tailwind config
- [ ] Cập nhật `resources/css/app.css` — định nghĩa CSS variables + theme
- [ ] Cập nhật `resources/js/app.js` — setup Inertia + Vue + layout resolver
- [ ] Cập nhật blade shell `resources/views/app.blade.php`
- [ ] Tạo toàn bộ UI Components (Button, Badge, Modal, Pagination, ...)
- [ ] Tạo Form Components (TextInput, Select, FileUpload, StarRating, ...)
- [ ] Tạo `AppLayout.vue` và `AdminLayout.vue`
- [ ] Tạo Pages: Home, Titles/Index, Titles/Show, Persons/Show
- [ ] Tạo Admin Pages: Dashboard, Titles/Index, Titles/Form, ...
- [ ] Test trên mobile (responsive)

### Lệnh thực thi

```bash
# Cài thêm JS packages cần thiết
./vendor/bin/sail npm install @heroicons/vue

# Dev mode với HMR
./vendor/bin/sail npm run dev

# Build production
./vendor/bin/sail npm run build
```

---

## Phase 6 — File Upload & Media

### Checklist

- [ ] Cấu hình `config/filesystems.php` — disk `public`
- [ ] Tạo `storage:link` (đã làm ở Phase 0)
- [ ] Implement `TitleService::handleMediaUploads()` với Intervention Image
- [ ] Implement upload trong `PersonController` (profile photo)
- [ ] Implement upload trong `StudioController` (logo)
- [ ] Test upload ảnh > 5MB bị reject
- [ ] Test resize poster về đúng 800×1200

### Lệnh thực thi

```bash
# Publish Intervention Image config
./vendor/bin/sail artisan vendor:publish --provider="Intervention\Image\Laravel\ServiceProvider"
```

---

## Phase 7 — Search & Filter

### Checklist

- [ ] Implement `scopeFilter` trên `Title` model
- [ ] Implement `scopeFilter` trên `Person` model
- [ ] `TitleController@index` truyền `filters` và `paginator` sang Inertia
- [ ] `TitleFilterBar.vue` — debounced search, select filters
- [ ] Preserve filter state khi paginate (`.withQueryString()`)
- [ ] Test filter kết hợp: search + year + type

---

## Phase 8 — Testing

### Checklist

- [ ] Feature test: `TitleCrudTest` — guest bị redirect, admin CRUD
- [ ] Feature test: `ReviewTest` — guest không review được, user review 1 lần
- [ ] Feature test: `RoleMiddlewareTest` — check 403 đúng role
- [ ] Feature test: `RegistrationTest` — register → pending verify → click mail link → auto login
- [ ] Unit test: `Title` model scope filter
- [ ] Unit test: `Review` model — `uq_user_title` unique constraint
- [ ] Chạy tất cả tests pass

### Lệnh thực thi

```bash
./vendor/bin/sail artisan make:test TitleCrudTest
./vendor/bin/sail artisan make:test ReviewTest
./vendor/bin/sail artisan make:test RoleMiddlewareTest

# Chạy tests
./vendor/bin/sail artisan test

# Với coverage
./vendor/bin/sail artisan test --coverage
```

---

## Phase 9 — Polish & Deploy Prep

### Checklist

- [ ] Thêm `slug` column vào `titles` migration (nếu chưa có)
- [ ] Cấu hình `APP_ENV=production`, `APP_DEBUG=false` cho production
- [ ] Chạy `php artisan optimize` (cache routes, config, views)
- [ ] Review `php artisan route:list` — xóa route dư thừa
- [ ] Kiểm tra `php artisan about` — không còn warning
- [ ] Test full flow: Register → Verify Email → Auto Login → Browse → Review → Admin CRUD

### Lệnh thực thi

```bash
# Production optimization
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
./vendor/bin/sail artisan event:cache

# Build production assets
./vendor/bin/sail npm run build

# Final check
./vendor/bin/sail artisan about
./vendor/bin/sail artisan test
```

---

## Lệnh tiện ích thường dùng

```bash
# Reset DB và seed lại
./vendor/bin/sail artisan migrate:fresh --seed

# Mở tinker (REPL)
./vendor/bin/sail artisan tinker

# Xem tất cả routes
./vendor/bin/sail artisan route:list

# Xem logs real-time (Laravel Pail)
./vendor/bin/sail artisan pail

# Chạy code style fixer (Pint)
./vendor/bin/sail vendor/bin/pint

# Vào MySQL CLI
./vendor/bin/sail mysql

# Xem trạng thái Docker containers
./vendor/bin/sail ps
```

---

## Cấu trúc thư mục cuối cùng (dự kiến)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          (9 controllers)
│   │   ├── Public/         (3 controllers)
│   │   ├── Moderate/       (2 controllers)
│   │   └── ReviewController.php
│   ├── Middleware/
│   │   ├── EnsureRole.php
│   │   └── HandleInertiaRequests.php
│   └── Requests/
│       ├── Admin/          (6 requests)
│       └── StoreReviewRequest.php
├── Models/         (13 models)
└── Services/       (3 services)

database/
├── migrations/     (15 files)
├── seeders/        (6 seeders)
├── factories/      (2 factories)
└── sql/
    ├── triggers.sql
    ├── views.sql
    └── procedures.sql

resources/js/
├── Pages/          (~20 Vue pages)
├── Layouts/        (2 layouts)
└── Components/     (~20 components)

docs/               (7 markdown files — tài liệu này)
tests/
├── Feature/        (5 test files)
└── Unit/           (3 test files)
```

---

## Phase 10 — Smart Search (Meilisearch)

> ⚠️ **Kích hoạt skill `data-engineer`** và **`ai-engineer`** trước khi bắt đầu phase này.

### Checklist

- [x] Thêm service `meilisearch` vào `compose.yaml`
- [x] Thêm volume `sail-meilisearch` vào `compose.yaml`
- [x] Cài packages: `laravel/scout`, `meilisearch/meilisearch-php`, `http-interop/http-factory-guzzle`
- [x] Publish Scout config: `vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"`
- [x] Cấu hình `.env`: `SCOUT_DRIVER=meilisearch`, `MEILISEARCH_HOST`, `MEILISEARCH_KEY`
- [x] Thêm `Searchable` trait vào model `Title` + viết `toSearchableArray()`
- [x] Viết migration `configure_meilisearch_index` — cài index settings (typo tolerance, ranking rules, filterable attributes)
- [x] Tạo `Api/SearchController` — trả JSON kết quả tìm kiếm
- [x] Thêm route `GET /api/search`
- [x] Tạo Vue component `SmartSearchBar.vue` với debounce 200ms + dropdown results
- [x] Thay thế SearchBar cũ trong `AppLayout.vue` bằng `SmartSearchBar.vue`
- [x] Import index: `scout:import "App\Models\Title"`
- [x] Test: gõ sai chính tả, kết quả vẫn ra đúng

### Lệnh thực thi

```bash
# Cài packages
./vendor/bin/sail composer require laravel/scout meilisearch/meilisearch-php http-interop/http-factory-guzzle
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"

# Cài @vueuse/core cho debounce
./vendor/bin/sail npm install @vueuse/core

# Restart Sail để load Meilisearch container mới
./vendor/bin/sail down
./vendor/bin/sail up -d

# Import titles vào Meilisearch
./vendor/bin/sail artisan scout:import "App\Models\Title"

# Meilisearch dashboard: http://localhost:7700
```

---

## Phase 11 — Social Watchlist

> ⚠️ **Kích hoạt skill `frontend-design`** cho các trang Collections.

### Checklist

- [ ] Tạo migration `create_collections_table`
- [ ] Tạo migration `create_collection_titles_table`
- [ ] Tạo model `Collection` với `HasSlug`
- [ ] Cập nhật model `User` — thêm `hasMany(Collection::class)`
- [ ] Tạo `StoreCollectionRequest`, `UpdateCollectionRequest`
- [ ] Tạo `CollectionController` (index, show, store, update, destroy, addTitle, removeTitle)
- [ ] Thêm routes Collection vào `routes/web.php`
- [ ] Tạo `CollectionPolicy` — chỉ owner mới update/delete
- [ ] Tạo Pages: `Collections/Show.vue`, `Collections/Create.vue`, `Collections/Edit.vue`
- [ ] Tạo `Users/Collections.vue` — danh sách collections của user
- [ ] Tạo component `AddToCollectionDropdown.vue` — nhúng vào `Titles/Show.vue`
- [ ] Cập nhật `Titles/Show.vue` — thêm nút "➕ Bộ sưu tập"
- [ ] Seed: thêm collections mẫu vào `DatabaseSeeder`
- [ ] Test: user A không xem được collection PRIVATE của user B

### Lệnh thực thi

```bash
./vendor/bin/sail artisan make:migration create_collections_table
./vendor/bin/sail artisan make:migration create_collection_titles_table
./vendor/bin/sail artisan make:model Collection
./vendor/bin/sail artisan make:request StoreCollectionRequest
./vendor/bin/sail artisan make:request UpdateCollectionRequest
./vendor/bin/sail artisan make:controller CollectionController
./vendor/bin/sail artisan make:policy CollectionPolicy --model=Collection
./vendor/bin/sail artisan migrate
```

---

## Phase 12 — Gamification

> ⚠️ **Kích hoạt skill `code-refactoring-refactor-clean`** — Event/Listener pattern phải clean.

### Checklist

- [ ] Tạo migration `create_badges_table`
- [ ] Tạo migration `create_user_badges_table`
- [ ] Tạo migration `add_reputation_to_users_table`
- [ ] Tạo model `Badge`, cập nhật `User` — thêm `belongsToMany(Badge::class)`
- [ ] Seed badges: `BadgeSeeder` — 9 huy hiệu mặc định (xem [07-advanced-features.md](./07-advanced-features.md#danh-sách-huy-hiệu-badge-catalog))
- [ ] Tạo Events: `ReviewCreated`, `ReviewDeleted`, `HelpfulVoteToggled`
- [ ] Tạo Listeners (implement `ShouldQueue`): `AwardBadgesOnReview`, `UpdateReputationOnReview`, `UpdateReputationOnHelpful`
- [ ] Đăng ký Events trong `EventServiceProvider`
- [ ] Cập nhật `ReviewService::create()` — fire `ReviewCreated` event
- [ ] Cập nhật `ReviewController::helpful()` — fire `HelpfulVoteToggled` event
- [ ] Tạo `BadgeEarned` Notification (database channel)
- [ ] Tạo route `GET /api/notifications/unread`
- [ ] Tạo component `UserBadges.vue` — hiển thị badges + rank trên profile
- [ ] Tạo badge toast notification trong `AppLayout.vue`
- [ ] Cập nhật `Admin/Badges/` — CRUD huy hiệu cho admin
- [ ] Test: viết 10 reviews → tự động nhận badge `critic-apprentice`

### Lệnh thực thi

```bash
./vendor/bin/sail artisan make:migration create_badges_table
./vendor/bin/sail artisan make:migration create_user_badges_table
./vendor/bin/sail artisan make:migration add_reputation_to_users_table
./vendor/bin/sail artisan make:model Badge
./vendor/bin/sail artisan make:seeder BadgeSeeder
./vendor/bin/sail artisan make:event ReviewCreated
./vendor/bin/sail artisan make:event ReviewDeleted
./vendor/bin/sail artisan make:event HelpfulVoteToggled
./vendor/bin/sail artisan make:listener AwardBadgesOnReview --event=ReviewCreated
./vendor/bin/sail artisan make:listener UpdateReputationOnReview --event=ReviewCreated
./vendor/bin/sail artisan make:listener UpdateReputationOnHelpful --event=HelpfulVoteToggled
./vendor/bin/sail artisan make:notification BadgeEarned
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=BadgeSeeder

# Chạy queue worker để xử lý listeners async
./vendor/bin/sail artisan queue:work
```

---

## Phase 13 — Auth & Email Verification UX

### Checklist

- [x] Chuẩn hóa flow đăng ký: submit form không login ngay — `RegisteredUserController` không gọi `Auth::login()`, redirect sang `verification.pending`
- [x] Tạo hoặc hoàn thiện màn hình `Auth/VerifyEmail.vue` — xử lý cả 2 flow (unauthenticated register + logged-in email-change), có countdown resend timer
- [x] Tạo mail xác thực với CTA rõ ràng — `VerifyRegistrationEmail` notification, Vietnamese copy, token hết hạn sau 24h
- [x] `VerifyPendingRegistrationController` xử lý route `/register/verify/{token}` — đọc `Cache::get("reg_verify:{token}")`, mark verified, `Auth::login($user)`, redirect home
- [x] Auto login sau verify — `VerifyPendingRegistrationController` gọi `Auth::login()` sau khi verify thành công
- [x] Kiểm tra các route write actions dùng `auth` + `verified` — tất cả mutating routes (reviews, comments, collections, follow, profile) đều trong `middleware(['auth', 'verified'])`
- [x] **[BUG FIXED]** Fix `tests/Feature/Auth/RegistrationTest.php` — `test_new_users_can_register` đang assert `assertAuthenticated()` + `assertRedirect(home)` nhưng controller thực tế redirect sang `verification.pending` mà không login → test này sẽ FAIL
- [x] Thêm Feature test cho `VerifyPendingRegistrationController` — hiện chưa có test cho custom token flow (`register/verify/{token}`): test token hợp lệ, token hết hạn, token không tồn tại

> **Lưu ý hai flow song song:** App dùng custom token flow (`reg_verify:{token}` trong Cache) làm primary flow. Laravel signed-URL flow (`verify-email/{id}/{hash}`) vẫn tồn tại nhưng chỉ dùng khi user đã login muốn re-verify. `EmailVerificationTest.php` test flow signed-URL — không cần sửa.

### Kết quả mong đợi

- `RegistrationTest` phản ánh đúng flow thực tế: assert NOT authenticated sau `POST /register`, assert redirect `verification.pending`.
- Custom token flow có coverage: valid token → auto login, expired token → error message, invalid token → error message.
- Hệ thống chỉ mở quyền ghi dữ liệu cho user đã verify.

---

## Phase 14 — Stabilization & Production Readiness

### Checklist

- [x] Fix `RegistrationTest` (xem Phase 13) — làm toàn bộ test suite xanh, loại bỏ test cũ không còn khớp business flow
- [x] Chạy `npm run build`, `config:cache`, `route:cache`, `event:cache`, `view:cache` thành công
- [x] Xóa các file debug/dev khỏi root trước khi build artifact: `debug_show.php`, `debug_title.php`, `profile_queries.php`, `test_all_routes.js`
- [x] Chốt biến môi trường production — các biến cần đổi so với `.env.example` dev defaults:
    - `APP_ENV=production`, `APP_DEBUG=false`
    - `MAIL_MAILER=resend` (hiện đang `log`)
    - `LOG_LEVEL=error` (hiện đang `debug`)
    - `BCRYPT_ROUNDS=14` (hiện đang `12`)
    - `SCOUT_QUEUE=true` (hiện đang `false` — cần queue để không block request)
    - `QUEUE_CONNECTION=redis` (hiện đang `database` — nên swap sang Redis cho production)
    - Điền: `APP_KEY`, `DB_*`, `RESEND_API_KEY`, `CLOUDINARY_URL`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `TMDB_API_KEY`, `MEILISEARCH_HOST`, `MEILISEARCH_KEY`
- [x] Xác nhận queue worker bắt buộc: `DiscoverTmdbTitlesJob`, `ImportTmdbTitleJob`, Gamification listeners (`AwardBadgesOnReview`, `UpdateReputationOnReview`, `UpdateReputationOnHelpful`) đều implement `ShouldQueue`
- [x] Xác nhận scheduler production cho `php artisan schedule:run` (cron mỗi phút)
- [x] Viết rollback note cho migration hoặc release lỗi

### Lệnh thực thi

```bash
# Xóa debug scripts
rm debug_show.php debug_title.php profile_queries.php test_all_routes.js

# Test suite
./vendor/bin/sail artisan test

# Production caching
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan event:cache
./vendor/bin/sail artisan view:cache
./vendor/bin/sail npm run build

# Final check
./vendor/bin/sail artisan about
```

### Kết quả mong đợi

- Ứng dụng có baseline kỹ thuật đủ tin cậy để mang lên staging.
- Không còn file dev-only trong source tree, không còn biến `.env` sai giá trị production.

---

## Phase 15 — Staging Rehearsal

### Checklist

- [x] Tạo môi trường staging gần giống production nhất có thể (Docker Sail stack: MySQL 8.4, Meilisearch latest, Redis)
- [x] Deploy đúng artifact và đúng quy trình dự kiến dùng cho production
- [x] Chạy `php artisan migrate --force` — áp dụng 1 migration pending (`add_performance_indexes`)
- [x] Chạy `php artisan scout:import "App\Models\Title"` và verify Meilisearch có data — **26,231 titles indexed**
- [x] Kiểm tra typo-tolerance search thực tế trên staging data — "Avngers" → "The Avengers" ✅, SmartSearchBar public + admin đều hoạt động
- [x] Chạy queue worker và scheduler trên staging; verify không có failed jobs sau smoke test — **0 failed jobs**, scheduler `DiscoverTmdbTitlesJob` daily confirmed
- [x] **[BUG FIXED]** Fix Meilisearch healthcheck `localhost` → `127.0.0.1` trong `compose.yaml` (container resolve `localhost` sang IPv6 `[::1]` nhưng Meilisearch chỉ bind IPv4) — container từ `unhealthy` → `healthy`
- [x] Smoke test các flow chính: home ✅, /titles ✅, title detail ✅, admin dashboard ✅, admin CRUD ✅, search ✅, API /api/search ✅
- [x] Xác nhận routes bảo vệ đúng — tất cả `/admin/*` redirect 302 với guest
- [x] Chạy production caching: `config:cache`, `route:cache`, `event:cache`, `view:cache` — tất cả CACHED
- [x] Test suite: **140 tests / 306 assertions — 100% pass** trước khi staging

> ⚠️ **Blocking items cần real staging domain (chưa có domain thực):**
>
> - **Google OAuth callback**: `GOOGLE_REDIRECT_URI=/auth/google/callback` dạng relative path — cần đổi sang full URL `https://<staging-domain>/auth/google/callback` và cập nhật trong Google Cloud Console
> - **Email delivery**: `MAIL_MAILER=log` — email không được gửi thực tế. Đổi sang `MAIL_MAILER=resend` với Resend staging key để test email verification flow
> - **Cloudinary**: `CLOUDINARY_URL` set đúng, nhưng chưa test actual file upload trên staging data
> - **SCOUT_QUEUE**: Hiện `false` — đổi sang `true` cho production để không block HTTP requests khi index
> - **QUEUE_CONNECTION**: Hiện `database` — cân nhắc đổi sang `redis` cho production throughput

### Kết quả mong đợi

- Quy trình deploy được diễn tập trọn vẹn trước khi lên production.
- Các lỗi hạ tầng (OAuth callback, mail delivery, Cloudinary, Meilisearch) được phát hiện ở staging thay vì production.

---

## Phase 16 — Production Deploy

### Checklist

- [ ] Backup database hoặc xác nhận snapshot strategy trước release
- [ ] Bật maintenance mode: `php artisan down --secret="bypass-token"`
- [ ] Deploy artifact đã kiểm thử ở staging
- [ ] Chạy `php artisan migrate --force`
- [ ] Cache lại: `config:cache`, `route:cache`, `event:cache`, `view:cache`
- [ ] Chạy `php artisan queue:restart` — graceful restart tất cả queue workers đang chạy (không chỉ spawn worker mới)
- [ ] Chạy `php artisan scout:import "App\Models\Title"` nếu switching Meilisearch instance hoặc lần deploy đầu tiên
- [ ] Tắt maintenance mode: `php artisan up`
- [ ] Smoke test sau deploy: home, login, register → verify flow, review submit, admin login, search (typo-tolerance)
- [ ] Theo dõi logs, failed jobs, metrics trong 15-30 phút đầu sau deploy

### Rollback plan

```bash
# Nếu có migration lỗi:
php artisan migrate:rollback

# Redeploy artifact cũ + restart workers
php artisan queue:restart
```

### Kết quả mong đợi

- Release production hoàn tất với khả năng rollback và kiểm tra sau deploy rõ ràng.
- Team có checklist vận hành chứ không chỉ checklist code.
