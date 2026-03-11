# 07 — Tính năng nâng cao

## Tổng quan

Dự án đã triển khai nhiều tính năng vượt ra ngoài 3 tính năng nâng cao ban đầu:

| #   | Tính năng                                               | Công nghệ chính             | Phase    |
| --- | ------------------------------------------------------- | --------------------------- | -------- |
| 1   | **Smart Search** — Tìm kiếm siêu tốc, chịu lỗi chính tả | Laravel Scout + Meilisearch | Phase 10 |
| 2   | **Social Collections** — Mạng xã hội điện ảnh           | Eloquent + Inertia          | Phase 11 |
| 3   | **Gamification** — Huy hiệu, cấp bậc, điểm uy tín       | Queue + Event + Observer    | Phase 12 |
| 4   | **Social Graph** — Follow/Feed/Public Profiles          | Eloquent + FeedService      | Phase 11 |
| 5   | **TMDB Integration** — Import phim tự động              | Guzzle + Queue Jobs         | Phase 11 |
| 6   | **GIF Support** — Tenor API trong reviews/comments      | TenorService proxy          | Phase 11 |
| 7   | **Discussion System** — Bình luận với reply, like, hide | Eloquent + Events           | Phase 11 |
| 8   | **Collections Extended** — Cộng tác, nominations, notes | Eloquent + Policies         | Phase 11 |

---

## 1 — Smart Search (Tìm kiếm siêu tốc)

### Vấn đề với LIKE `%...%`

| Tiêu chí       | `LIKE %...%`           | Laravel Scout + Meilisearch  |
| -------------- | ---------------------- | ---------------------------- |
| Tốc độ         | Chậm (full-table scan) | < 50ms (indexed)             |
| Sai chính tả   | ❌ Không tìm được      | ✅ "bat man" → "Batman"      |
| Instant search | ❌ Cần submit form     | ✅ Kết quả theo từng phím gõ |
| Lọc đa tầng    | Truy vấn phức tạp      | ✅ Filters + Facets native   |
| Tiếng Việt     | Không xử lý tốt        | ✅ Cấu hình được             |

### Setup Meilisearch trong Docker

Thêm vào `compose.yaml`:

```yaml
meilisearch:
    image: "getmeili/meilisearch:latest"
    ports:
        - "${FORWARD_MEILISEARCH_PORT:-7700}:7700"
    environment:
        MEILI_NO_ANALYTICS: "${MEILISEARCH_NO_ANALYTICS:-false}"
        MEILI_MASTER_KEY: "${MEILISEARCH_KEY}"
    volumes:
        - "sail-meilisearch:/meili_data"
    networks:
        - sail
    healthcheck:
        test:
            [
                "CMD",
                "wget",
                "--no-verbose",
                "--spider",
                "http://meilisearch:7700/health",
            ]
        retries: 3
        timeout: 5s

volumes:
    sail-mysql:
        driver: local
    sail-meilisearch: # ← thêm volume này
        driver: local
```

### Cài đặt Laravel Scout + Meilisearch

```bash
# PHP packages
./vendor/bin/sail composer require laravel/scout meilisearch/meilisearch-php http-interop/http-factory-guzzle

# Publish Scout config
./vendor/bin/sail artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

Cập nhật `.env`:

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=your-master-key-here
```

### Cấu hình Model `Title` với Scout

```php
// app/Models/Title.php
use Laravel\Scout\Searchable;

class Title extends Model
{
    use HasSlug, Searchable;

    // Chỉ index những trường cần search
    public function toSearchableArray(): array
    {
        return [
            'title_id'     => $this->title_id,
            'title_name'   => $this->title_name,
            'description'  => $this->description,
            'title_type'   => $this->title_type,
            'release_year' => $this->release_date?->year,
            'avg_rating'   => $this->avg_rating,
            'language'     => $this->language?->language_name,
            'visibility'   => $this->visibility,
        ];
    }

    // Chỉ index title PUBLIC
    public function shouldBeSearchable(): bool
    {
        return $this->visibility === 'PUBLIC';
    }
}
```

### Cấu hình Meilisearch Index Settings

```php
// database/migrations/xxxx_configure_meilisearch_index.php
// Chạy một lần để cấu hình index settings trên Meilisearch

use Meilisearch\Client;

public function up(): void
{
    $client = app(\Laravel\Scout\Engines\MeilisearchEngine::class)->getMeilisearch();

    $client->index('titles')->updateSettings([
        'searchableAttributes' => ['title_name', 'description'],
        'filterableAttributes' => ['title_type', 'release_year', 'avg_rating', 'visibility', 'language'],
        'sortableAttributes'   => ['avg_rating', 'release_year', 'rating_count'],
        'typoTolerance' => [
            'enabled' => true,
            'minWordSizeForTypos' => ['oneTypo' => 4, 'twoTypos' => 8],
        ],
        'rankingRules' => [
            'words', 'typo', 'proximity', 'attribute', 'sort', 'exactness',
            'avg_rating:desc',  // Ưu tiên phim rating cao hơn
        ],
    ]);
}
```

### Controller — Instant Search API

```php
// app/Http/Controllers/Api/SearchController.php
public function __invoke(Request $request): JsonResponse
{
    $query   = $request->get('q', '');
    $filters = [];

    if ($request->filled('type'))    $filters[] = "title_type = '{$request->type}'";
    if ($request->filled('year'))    $filters[] = "release_year = {$request->year}";
    if ($request->filled('min_rating')) $filters[] = "avg_rating >= {$request->min_rating}";

    $results = Title::search($query, function (Index $index, string $query, array $options) use ($filters) {
        $options['filter']       = implode(' AND ', $filters) ?: null;
        $options['limit']        = 10;
        $options['attributesToHighlight'] = ['title_name'];
        return $index->search($query, $options);
    })->get();

    return response()->json([
        'results' => $results->map(fn($t) => [
            'id'         => $t->title_id,
            'name'       => $t->title_name,
            'slug'       => $t->slug,
            'type'       => $t->title_type,
            'year'       => $t->release_date?->year,
            'rating'     => $t->avg_rating,
            'poster_url' => $t->poster_url,
        ]),
    ]);
}
```

Route:

```php
// routes/web.php
Route::get('/api/search', \App\Http\Controllers\Api\SearchController::class);
```

### Vue Component — Instant Search Bar

```vue
<!-- resources/js/Components/UI/SmartSearchBar.vue -->
<script setup>
import { ref, watch } from "vue";
import axios from "axios";
import { useDebounceFn } from "@vueuse/core"; // npm install @vueuse/core

const query = ref("");
const results = ref([]);
const loading = ref(false);
const showDropdown = ref(false);

const search = useDebounceFn(async () => {
    if (query.value.length < 2) {
        results.value = [];
        return;
    }
    loading.value = true;
    const { data } = await axios.get("/api/search", {
        params: { q: query.value },
    });
    results.value = data.results;
    showDropdown.value = true;
    loading.value = false;
}, 200); // Debounce 200ms

watch(query, search);
</script>

<template>
    <div class="relative">
        <input
            v-model="query"
            type="text"
            placeholder="Tìm phim, diễn viên, đạo diễn..."
            class="w-full bg-bg-elevated text-text-primary px-4 py-2 rounded-lg
             border border-border focus:border-accent focus:outline-none"
        />
        <!-- Spinner -->
        <Spinner v-if="loading" class="absolute right-3 top-2.5 w-5 h-5" />

        <!-- Dropdown results -->
        <div
            v-if="showDropdown && results.length"
            class="absolute top-full left-0 right-0 mt-1 bg-bg-elevated
                border border-border rounded-lg shadow-2xl z-50 overflow-hidden"
        >
            <a
                v-for="item in results"
                :key="item.id"
                :href="`/titles/${item.slug}`"
                class="flex items-center gap-3 px-4 py-3 hover:bg-bg-surface transition-colors"
            >
                <img
                    :src="item.poster_url"
                    class="w-8 h-12 object-cover rounded"
                    :alt="item.name"
                />
                <div>
                    <p class="text-text-primary font-medium">{{ item.name }}</p>
                    <p class="text-text-muted text-sm">
                        {{ item.type }} · {{ item.year }} · ⭐ {{ item.rating }}
                    </p>
                </div>
            </a>
        </div>
    </div>
</template>
```

### Đồng bộ index khi dữ liệu thay đổi

Scout tự động sync khi `create/update/delete` Model nếu dùng `Searchable` trait. Để index lại toàn bộ:

```bash
# Index tất cả titles vào Meilisearch
./vendor/bin/sail artisan scout:import "App\Models\Title"

# Xoá và index lại
./vendor/bin/sail artisan scout:flush "App\Models\Title"
./vendor/bin/sail artisan scout:import "App\Models\Title"
```

---

## 2 — Social Collections (Mạng xã hội điện ảnh)

### Tính năng

- User có thể tạo nhiều **bộ sưu tập (Collection)** tên tuỳ ý
- Thêm bất kỳ title nào vào collection
- Collection có thể **Public** (chia sẻ link) hoặc **Private**
- Danh sách collections nổi bật trên trang chủ
- Xem collections của người dùng khác (nếu Public)

### Database Schema

```sql
-- Migration: create_collections_table
CREATE TABLE collections (
    collection_id INT AUTO_INCREMENT,
    user_id       INT NOT NULL,
    name          VARCHAR(200) NOT NULL,
    slug          VARCHAR(220) NOT NULL,
    description   TEXT,
    visibility    VARCHAR(10) DEFAULT 'PUBLIC' CHECK (visibility IN ('PUBLIC','PRIVATE')),
    cover_title_id INT NULL,           -- Dùng poster của title này làm cover
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_collections      PRIMARY KEY (collection_id),
    CONSTRAINT uq_collection_slug  UNIQUE (slug),
    CONSTRAINT fk_col_user         FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_col_cover        FOREIGN KEY (cover_title_id) REFERENCES titles(title_id) ON DELETE SET NULL
);

CREATE TABLE collection_titles (
    collection_id INT NOT NULL,
    title_id      INT NOT NULL,
    added_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note          TEXT NULL,           -- User có thể ghi chú riêng cho mỗi phim trong collection
    CONSTRAINT pk_collection_titles PRIMARY KEY (collection_id, title_id),
    CONSTRAINT fk_ct_collection     FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE CASCADE,
    CONSTRAINT fk_ct_title          FOREIGN KEY (title_id) REFERENCES titles(title_id) ON DELETE CASCADE
);

CREATE INDEX idx_collections_user ON collections(user_id);
CREATE INDEX idx_collections_vis  ON collections(visibility);
```

### Models

```php
// app/Models/Collection.php
class Collection extends Model
{
    use HasSlug;

    protected $primaryKey = 'collection_id';
    protected $fillable = ['user_id','name','description','visibility','cover_title_id'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['user_id', 'name'])
            ->saveSlugsTo('slug');
    }

    public function user(): BelongsTo      { return $this->belongsTo(User::class, 'user_id'); }
    public function cover(): BelongsTo     { return $this->belongsTo(Title::class, 'cover_title_id', 'title_id'); }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class, 'collection_titles', 'collection_id', 'title_id')
            ->withPivot('added_at', 'note')
            ->orderByPivot('added_at', 'desc');
    }

    public function scopePublic(Builder $q): Builder  { return $q->where('visibility', 'PUBLIC'); }
    public function scopeForUser(Builder $q, int $userId): Builder { return $q->where('user_id', $userId); }

    public function getCoverUrlAttribute(): string
    {
        return $this->cover?->poster_url ?? asset('images/collection-default.jpg');
    }
}
```

### Controller

```php
// app/Http/Controllers/CollectionController.php
class CollectionController extends Controller
{
    // GET /users/{user}/collections
    public function index(User $user): Response
    {
        $collections = Collection::forUser($user->user_id)
            ->when(auth()->id() !== $user->user_id, fn($q) => $q->public())
            ->withCount('titles')
            ->latest()
            ->get();

        return Inertia::render('Collections/Index', [
            'owner'       => $user->only('user_id','username'),
            'collections' => $collections,
        ]);
    }

    // GET /collections/{collection:slug}
    public function show(Collection $collection): Response
    {
        if ($collection->visibility === 'PRIVATE' && $collection->user_id !== auth()->id()) {
            abort(403, 'Bộ sưu tập này là riêng tư.');
        }

        return Inertia::render('Collections/Show', [
            'collection' => $collection->load(['user','titles' => fn($q) => $q->published()]),
        ]);
    }

    // POST /collections
    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $collection = auth()->user()->collections()->create($request->validated());
        return redirect()->route('collections.show', $collection->slug)
            ->with('success', 'Bộ sưu tập đã được tạo.');
    }

    // POST /collections/{collection}/titles
    public function addTitle(Collection $collection, Request $request): RedirectResponse
    {
        $this->authorize('update', $collection);
        $collection->titles()->syncWithoutDetaching([
            $request->title_id => ['note' => $request->note],
        ]);
        return back()->with('success', 'Đã thêm vào bộ sưu tập.');
    }

    // DELETE /collections/{collection}/titles/{title}
    public function removeTitle(Collection $collection, Title $title): RedirectResponse
    {
        $this->authorize('update', $collection);
        $collection->titles()->detach($title->title_id);
        return back()->with('success', 'Đã xoá khỏi bộ sưu tập.');
    }
}
```

### Routes bổ sung

```php
Route::middleware('auth')->group(function () {
    Route::resource('collections', CollectionController::class)
        ->except(['index']);
    Route::post('/collections/{collection}/titles', [CollectionController::class, 'addTitle'])
        ->name('collections.titles.add');
    Route::delete('/collections/{collection}/titles/{title}', [CollectionController::class, 'removeTitle'])
        ->name('collections.titles.remove');
});

// Public — xem collections của user khác
Route::get('/users/{user}/collections', [CollectionController::class, 'index'])
    ->name('users.collections');
Route::get('/collections/{collection:slug}', [CollectionController::class, 'show'])
    ->name('collections.show');
```

### Quick-add từ trang chi tiết phim

```vue
<!-- Component: AddToCollectionDropdown.vue -->
<!-- Hiện ra khi user click "➕ Thêm vào bộ sưu tập" trên trang Show.vue -->
<script setup>
import { router, usePage } from "@inertiajs/vue3";
const props = defineProps(["collections", "titleId"]);
const add = (collectionId) =>
    router.post(`/collections/${collectionId}/titles`, {
        title_id: props.titleId,
    });
</script>

<template>
    <Dropdown>
        <template #trigger>
            <Button variant="ghost">➕ Bộ sưu tập</Button>
        </template>
        <div class="p-2 min-w-48">
            <button
                v-for="col in collections"
                :key="col.collection_id"
                @click="add(col.collection_id)"
                class="w-full text-left px-3 py-2 rounded hover:bg-bg-elevated text-sm"
            >
                {{ col.name }}
                <span class="text-text-muted ml-1"
                    >({{ col.titles_count }})</span
                >
            </button>
            <hr class="border-border my-1" />
            <a
                href="/collections/create"
                class="block px-3 py-2 text-accent text-sm"
            >
                + Tạo bộ sưu tập mới
            </a>
        </div>
    </Dropdown>
</template>
```

---

## 3 — Gamification (Game hóa trải nghiệm)

### Thiết kế hệ thống

```
User hành động → Event fired → Listener xử lý → Badge / Điểm được cộng
```

### Database Schema

```sql
-- Bảng định nghĩa các huy hiệu
CREATE TABLE badges (
    badge_id    INT AUTO_INCREMENT,
    slug        VARCHAR(100) NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(500),
    icon_path   VARCHAR(500),           -- SVG/PNG icon
    tier        VARCHAR(20) CHECK (tier IN ('BRONZE','SILVER','GOLD','PLATINUM')),
    CONSTRAINT pk_badges  PRIMARY KEY (badge_id),
    CONSTRAINT uq_badge_slug UNIQUE (slug)
);

-- Huy hiệu user đã đạt được
CREATE TABLE user_badges (
    user_id     INT NOT NULL,
    badge_id    INT NOT NULL,
    earned_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_user_badges   PRIMARY KEY (user_id, badge_id),
    CONSTRAINT fk_ub_user       FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_ub_badge      FOREIGN KEY (badge_id) REFERENCES badges(badge_id)
);

-- Điểm uy tín (Reputation)
ALTER TABLE users ADD COLUMN reputation INT DEFAULT 0;

CREATE INDEX idx_users_reputation ON users(reputation);
```

### Danh sách Huy hiệu (Badge Catalog)

| Slug                | Tên                     | Điều kiện                            | Tier   |
| ------------------- | ----------------------- | ------------------------------------ | ------ |
| `first-review`      | Nhà phê bình mới        | Viết review đầu tiên                 | BRONZE |
| `critic-apprentice` | Nhà phê bình tập sự     | 10 reviews                           | BRONZE |
| `critic-regular`    | Nhà phê bình thường trú | 50 reviews                           | SILVER |
| `critic-master`     | Bậc thầy phê bình       | 200 reviews                          | GOLD   |
| `helpful-voice`     | Giọng nói hữu ích       | Nhận 10 lượt "Helpful"               | BRONZE |
| `trusted-critic`    | Nhà phê bình đáng tin   | Nhận 100 lượt "Helpful"              | SILVER |
| `cinephile`         | Mọt phim                | Review phim từ 5 thể loại khác nhau  | SILVER |
| `collector`         | Nhà sưu tầm             | Tạo 5 bộ sưu tập                     | BRONZE |
| `early-bird`        | Người đi trước          | Review trong vòng 7 ngày phim ra mắt | GOLD   |

### Hệ thống điểm Reputation

#### Nhóm 1 — Tương tác nhẹ (Low Effort)

> Giữ chân user, tạo thói quen truy cập hàng ngày.

| Hành động                   | Điểm | Cơ chế & Chống spam                                                                                                   |
| --------------------------- | ---- | --------------------------------------------------------------------------------------------------------------------- |
| Đánh giá phim (chỉ thả sao) | +2   | Cột `rating` trong `reviews`. Khóa `UNIQUE (title_id, user_id)` — mỗi user chỉ đánh giá 1 phim 1 lần, chặn farm điểm. |

#### Nhóm 2 — Đóng góp nội dung (High Effort)

> Khuyến khích viết dài, chất lượng thay vì chỉ click.

| Hành động                    | Điểm | Cơ chế & Chống spam                                                                                                                                            |
| ---------------------------- | ---- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Viết review có tâm (≥ 50 từ) | +10  | Backend đếm `str_word_count($review_text)`. Chỉ cộng 1 lần / phim. Nếu sửa từ ngắn thành dài → cộng bù phần còn thiếu. Copy-paste nội dung trùng lập → bỏ qua. |
| Viết review ngắn (< 50 từ)   | +3   | Cộng cơ bản cho mọi review có text.                                                                                                                            |

#### Nhóm 3 — Chất lượng cộng đồng (Community Value)

> Yếu tố “gây nghiện” nhất — buộc user viết thật hay để “câu” vote từ người khác.

| Hành động                   | Điểm | Cơ chế                                                                                                                     |
| --------------------------- | ---- | -------------------------------------------------------------------------------------------------------------------------- |
| Nhận 1 lượt "Hữ u ích"      | +5   | Tác giả review được cộng ngay khi người khác bấm nút. Cột `helpful_votes` tăng không ảnh hưởng, chỬ `reputation` mới tăng. |
| Helpful bị rút lại (unlike) | −5   | Ngược lại hoàn toàn.                                                                                                       |

#### Nhóm 4 — Thưởng khi đạt Badge

| Tier     | Điểm cộng thêm |
| -------- | -------------- |
| BRONZE   | +10            |
| SILVER   | +25            |
| GOLD     | +50            |
| PLATINUM | +100           |

#### Nhóm 5 — Phạt (Penalty)

> Bảo vệ sự công bằng — bắt buộc phải có nếu đưa web vào thực tế.

| Tình huống           | Điểm                         | Cơ chế                                                                                                                       |
| -------------------- | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| User tự xoá review   | Trừ lại toàn bộ điểm đã nhận | Trừ cả điểm viết bài + toàn bộ `helpful_votes × 5`. Lưu các giá trị này trong `reviews` trước khi xóa.                       |
| Admin xóa do vi phạm | −50 điểm (gãy)               | Spoiler không đánh cờ, nội dung xúc phạm, spam. Admin có nút "Xóa & Phạt" riîng — đối tác với nút "Xóa" thường (không phạt). |

### Cấp bậc (Rank) theo Reputation

| Cấp bậc              | Reputation  | Icon | Màu hiển thị       |
| -------------------- | ----------- | ---- | ------------------ |
| Khán giả phổ thông   | 0 – 49      | 🏟️   | `text-zinc-400`    |
| Thợ cày phim         | 50 – 199    | 🍿   | `text-green-500`   |
| Kẻ sành phim         | 200 – 499   | 🎬   | `text-emerald-500` |
| Nhà phê bình uy tín  | 500 – 999   | ✍️   | `text-blue-500`    |
| Huyền thoại phòng vé | 1000 – 2499 | 🏆   | `text-purple-500`  |
| Bậc thầy điện ảnh    | 2500+       | 👑   | `text-yellow-500`  |

### Events & Listeners

```
app/Events/
├── ReviewCreated.php
├── ReviewDeleted.php
└── HelpfulVoteToggled.php

app/Listeners/
├── AwardBadgesOnReview.php
├── UpdateReputationOnReview.php
└── UpdateReputationOnHelpful.php
```

```php
// app/Events/ReviewCreated.php
class ReviewCreated
{
    public function __construct(public readonly Review $review) {}
}

// app/Listeners/AwardBadgesOnReview.php
class AwardBadgesOnReview implements ShouldQueue  // Chạy async qua Queue
{
    public function handle(ReviewCreated $event): void
    {
        $user = $event->review->user;
        $reviewCount = $user->reviews()->count();

        $badgesToCheck = [
            1  => 'first-review',
            10 => 'critic-apprentice',
            50 => 'critic-regular',
            200 => 'critic-master',
        ];

        foreach ($badgesToCheck as $threshold => $slug) {
            if ($reviewCount >= $threshold) {
                $this->awardBadge($user, $slug);
            }
        }
    }

    private function awardBadge(User $user, string $slug): void
    {
        $badge = Badge::where('slug', $slug)->first();
        if (! $badge) return;

        // syncWithoutDetaching = không bị lỗi nếu đã có badge
        $attached = $user->badges()->syncWithoutDetaching([$badge->badge_id]);

        if (! empty($attached['attached'])) {
            // Cộng reputation khi đạt badge mới
            $reputation = match($badge->tier) {
                'BRONZE'   => 10,
                'SILVER'   => 25,
                'GOLD'     => 50,
                'PLATINUM' => 100,
                default    => 0,
            };
            $user->increment('reputation', $reputation);

            // TODO: Gửi notification cho user
        }
    }
}
```

### Triển khai lógic cộng điểm trong ReviewService

```php
// app/Services/ReviewService.php

/**
 * Tạo review mới và cộng điểm reputation.
 * - Review có text dài >= 50 từ: +10 điểm
 * - Review có text ngắn / chỉ rating:  +3  điểm
 * - Chỉ rating (không kèm text):     +2  điểm
 */
public function create(Title $title, array $data): Review
{
    $review = $title->reviews()->create([
        ...$data,
        'user_id' => auth()->id(),
    ]);

    $points = $this->calcWritePoints($review->review_text);
    $review->update(['reputation_earned' => $points]);  // lưu để trừ lại khi xóa
    auth()->user()->increment('reputation', $points);

    event(new ReviewCreated($review));
    return $review;
}

/**
 * Sửa review: cộng bù nếu user bổ sung text đủ dài (< 50 từ → >= 50 từ).
 */
public function update(Review $review, array $data): Review
{
    $oldPoints = $review->reputation_earned ?? 0;
    $newPoints = $this->calcWritePoints($data['review_text'] ?? $review->review_text);
    $diff = $newPoints - $oldPoints;

    $review->update([...$data, 'reputation_earned' => $newPoints]);

    if ($diff > 0) {
        $review->user->increment('reputation', $diff);
    }
    return $review;
}

/**
 * Xóa review do user tự xóa: trừ toàn bộ điểm đã kiếm.
 */
public function delete(Review $review): void
{
    $totalEarned = ($review->reputation_earned ?? 0)
                 + ($review->helpful_votes * 5);

    $review->user->decrement('reputation', max(0, $totalEarned));
    $review->delete();
    event(new ReviewDeleted($review));
}

/**
 * Admin xóa và phạt: trừ toàn bộ điểm + 50 điểm phạt.
 */
public function adminDeleteWithPenalty(Review $review): void
{
    $totalEarned = ($review->reputation_earned ?? 0)
                 + ($review->helpful_votes * 5);

    $review->user->decrement('reputation', $totalEarned + 50);
    $review->delete();
}

/**
 * Toggle helpful vote: tác giả review được +5 / -5.
 */
public function toggleHelpful(Review $review, User $voter): bool
{
    $alreadyVoted = $review->helpfulVoters()->where('user_id', $voter->user_id)->exists();

    if ($alreadyVoted) {
        $review->helpfulVoters()->detach($voter->user_id);
        $review->decrement('helpful_votes');
        $review->user->decrement('reputation', 5);
        return false; // vote đã bị rút
    }

    $review->helpfulVoters()->attach($voter->user_id);
    $review->increment('helpful_votes');
    $review->user->increment('reputation', 5);
    event(new HelpfulVoteToggled($review, added: true));
    return true; // vote mới
}

// --- Helper ---

private function calcWritePoints(?string $text): int
{
    if (blank($text)) return 2;           // chỉ thả sao
    $wordCount = str_word_count(strip_tags($text));
    return $wordCount >= 50 ? 10 : 3;    // có tâm vs ngắn
}
```

> **Cột `reputation_earned` cần thêm vào bảng `reviews`:**
>
> ```sql
> ALTER TABLE reviews ADD COLUMN reputation_earned INT DEFAULT 0;
> ```
>
> Trong Laravel: tạo migration `add_reputation_earned_to_reviews_table`.

> **Bảng pivot `review_helpful_votes` — chống double helpful vote:**
>
> ```sql
> CREATE TABLE review_helpful_votes (
>     review_id INT NOT NULL,
>     user_id   INT NOT NULL,
>     voted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
>     CONSTRAINT pk_review_helpful_votes PRIMARY KEY (review_id, user_id),
>     CONSTRAINT fk_rhv_review FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
>     CONSTRAINT fk_rhv_user   FOREIGN KEY (user_id)   REFERENCES users(user_id)   ON DELETE CASCADE
> );
> ```
>
> Relationship trong model `Review`:
>
> ```php
> public function helpfulVoters(): BelongsToMany
> {
>     return $this->belongsToMany(User::class, 'review_helpful_votes', 'review_id', 'user_id')
>         ->withPivot('voted_at');
> }
> ```

### Đăng ký trong `EventServiceProvider`

```php
protected $listen = [
    ReviewCreated::class => [
        AwardBadgesOnReview::class,
        UpdateReputationOnReview::class,
    ],
    HelpfulVoteToggled::class => [
        UpdateReputationOnHelpful::class,
    ],
];
```

### Frontend — Hiển thị Badge & Rank

```vue
<!-- resources/js/Components/User/UserBadges.vue -->
<script setup>
defineProps({ badges: Array, reputation: Number });

const getRank = (rep) => {
    if (rep >= 2500)
        return {
            label: "Bậc thầy điện ảnh",
            icon: "👑",
            color: "text-yellow-500",
        };
    if (rep >= 1000)
        return {
            label: "Huyền thoại phòng vé",
            icon: "🏆",
            color: "text-purple-500",
        };
    if (rep >= 500)
        return {
            label: "Nhà phê bình uy tín",
            icon: "✍️",
            color: "text-blue-500",
        };
    if (rep >= 200)
        return { label: "Kẻ sành phim", icon: "🎬", color: "text-emerald-500" };
    if (rep >= 50)
        return { label: "Thợ cày phim", icon: "🍿", color: "text-green-500" };
    return { label: "Khán giả phổ thông", icon: "🏟️", color: "text-zinc-400" };
};
</script>

<template>
    <div class="space-y-3">
        <!-- Rank -->
        <div class="flex items-center gap-2">
            <span class="text-2xl">{{ getRank(reputation).icon }}</span>
            <div>
                <p class="font-semibold" :class="getRank(reputation).color">
                    {{ getRank(reputation).label }}
                </p>
                <p class="text-text-muted text-sm">
                    {{ reputation }} điểm uy tín
                </p>
            </div>
        </div>

        <!-- Badges grid -->
        <div class="flex flex-wrap gap-2">
            <div
                v-for="badge in badges"
                :key="badge.badge_id"
                class="group relative"
            >
                <img
                    :src="badge.icon_path"
                    :alt="badge.name"
                    class="w-10 h-10 rounded-full border-2"
                    :class="{
                        'border-amber-600': badge.tier === 'BRONZE',
                        'border-slate-400': badge.tier === 'SILVER',
                        'border-yellow-400': badge.tier === 'GOLD',
                        'border-cyan-300': badge.tier === 'PLATINUM',
                    }"
                />
                <!-- Tooltip -->
                <div
                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-bg-elevated
                    text-text-primary text-xs rounded shadow-lg whitespace-nowrap
                    opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"
                >
                    {{ badge.name }}
                </div>
            </div>
        </div>
    </div>
</template>
```

### Notification khi đạt badge

Khi user đạt badge mới, gửi in-app notification (có thể dùng Laravel Notifications):

```php
// app/Notifications/BadgeEarned.php
class BadgeEarned extends Notification implements ShouldQueue
{
    public function __construct(public readonly Badge $badge) {}

    public function via(object $notifiable): array
    {
        return ['database'];  // Lưu vào bảng notifications
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'badge_name' => $this->badge->name,
            'badge_slug' => $this->badge->slug,
            'badge_tier' => $this->badge->tier,
            'icon_path'  => $this->badge->icon_path,
        ];
    }
}
```

Vue component hiển thị notification dạng toast khi load trang:

```vue
<!-- Trong AppLayout.vue — tự động show toast khi có badge mới -->
<script setup>
import { onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";

onMounted(async () => {
    if (usePage().props.auth.user) {
        const { data } = await axios.get("/api/notifications/unread");
        data.forEach((n) => {
            if (n.type === "badge_earned") showBadgeToast(n.data);
        });
    }
});
</script>
```

---

## Tóm tắt: Migration & Packages cần bổ sung

### PHP Packages mới

```bash
./vendor/bin/sail composer require \
    laravel/scout \
    meilisearch/meilisearch-php \
    http-interop/http-factory-guzzle
```

### NPM Packages mới

```bash
./vendor/bin/sail npm install @vueuse/core
```

### Migrations mới

| File                                    | Mục đích                                  |
| --------------------------------------- | ----------------------------------------- |
| `create_collections_table`              | Bộ sưu tập của user                       |
| `create_collection_titles_table`        | Pivot: collection ↔ title                 |
| `create_badges_table`                   | Định nghĩa huy hiệu                       |
| `create_user_badges_table`              | Huy hiệu user đã đạt                      |
| `add_reputation_to_users_table`         | Thêm cột `reputation` vào `users`         |
| `create_user_follows_table`             | Quan hệ follow                            |
| `create_feed_items_table`               | Activity feed                             |
| `create_title_comments_table`           | Hệ thống bình luận                        |
| `create_title_comment_likes_table`      | Like comment                              |
| `create_collection_collaborators_table` | Cộng tác viên collection                  |
| `create_collection_nominations_table`   | Đề cử collection                          |
| `create_collection_title_notes_table`   | Ghi chú + watched per-title               |
| `create_collection_comments_table`      | Bình luận trên collection                 |
| `create_collection_comment_likes_table` | Like comment trên collection              |
| `create_tmdb_import_logs_table`         | Lịch sử import TMDB                       |
| `create_user_activity_logs_table`       | Log hoạt động user                        |
| `create_title_nominations_table`        | Đề cử title                               |
| `create_title_media_table`              | Gallery media                             |
| `create_genres_table`                   | Thể loại phim                             |
| `add_soft_deletes_to_core_tables`       | Soft delete cho titles, reviews, comments |
| `expand_badges_for_gamification`        | Thêm `condition_type`, `condition_value`  |
| `add_gamification_progress_fields`      | Các trường progress trong users           |
| `add_performance_indexes`               | Indexes tối ưu query                      |

### Models mới

- `Collection`, `CollectionCollaborator`, `CollectionComment`, `CollectionNomination`, `CollectionTitleNote`
- `Badge`, `UserBadge` (pivot)
- `Comment` (title_comments, hỗ trợ thread)
- `FeedItem`, `UserActivityLog`
- `Genre`, `TitleMedia`, `Nomination`
- `TmdbImportLog`

### Lệnh Scout

```bash
# Import titles vào Meilisearch
./vendor/bin/sail artisan scout:import "App\Models\Title"

# Kiểm tra Meilisearch dashboard
# Truy cập http://localhost:7700 trong browser
```

---

## 4 — Social Graph (Follow / Feed / Public Profiles)

### Tính năng

- User có thể follow/unfollow người khác
- Activity Feed hiển thị hoạt động gần nhất của những người đang follow
- Trang profile công khai `/users/{username}`: xem badges, collections, activity calendar

### Database Schema

```sql
CREATE TABLE user_follows (
    follower_id  INT NOT NULL,
    following_id INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_follows PRIMARY KEY (follower_id, following_id),
    CONSTRAINT fk_follower  FOREIGN KEY (follower_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_following FOREIGN KEY (following_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE feed_items (
    feed_item_id  BIGINT AUTO_INCREMENT,
    user_id       INT NOT NULL,               -- Người thực hiện hành động
    activity_type VARCHAR(50) NOT NULL,       -- review_created | collection_created | badge_earned | ...
    subject_type  VARCHAR(100),               -- Polymorphic: Review, Collection, Badge, ...
    subject_id    BIGINT,
    metadata      JSON,                       -- Extra data: title_name, poster_url, ...
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_feed_items PRIMARY KEY (feed_item_id),
    CONSTRAINT fk_feed_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### Events & Notifications

- **UserFollowed** event → `SendFollowNotification` listener → gửi `NewFollower` notification
- `FeedService::record()` được gọi sau: tạo review, tạo collection, nhận badge

### Routes

```php
// Follow / Unfollow
Route::post('/users/{user}/follow', [FollowController::class, 'store'])->name('users.follow');
Route::delete('/users/{user}/follow', [FollowController::class, 'destroy'])->name('users.unfollow');

// Activity Feed
Route::get('/feed', [FeedController::class, 'index'])->name('feed');

// Public Profile
Route::get('/users/{username}', [UserProfileController::class, 'show'])->name('users.show');
```

---

## 5 — TMDB Integration (Import phim tự động)

### Tổng quan

Tích hợp [The Movie Database API](https://www.themoviedb.org/documentation/api) để import metadata phim, series, và thông tin diễn viên tự động.

### Cấu hình

```env
TMDB_API_KEY=your_api_key
TMDB_BASE_URL=https://api.themoviedb.org/3
TMDB_IMAGE_BASE=https://image.tmdb.org/t/p
```

### Luồng import

```
Admin nhập tên/TMDB ID
    → TmdbService::search() / TmdbService::getMovie()
    → Preview metadata
    → Admin xác nhận
    → ImportTmdbTitleJob (Queue)
        → TmdbImportService::import()
            → Tạo/cập nhật Title
            → Tải poster → Cloudinary
            → Match/tạo Person, Studio
            → Gán cast/crew
            → Ghi TmdbImportLog
```

### Queue Jobs

```php
// app/Jobs/ImportTmdbTitleJob.php
class ImportTmdbTitleJob implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 120;

    public function handle(TmdbImportService $service): void
    {
        $service->import($this->tmdbId, $this->titleType);
    }
}

// app/Jobs/DiscoverTmdbTitlesJob.php
// Bulk discover và queue nhiều imports cùng lúc (cron-based)
```

### Admin UI

Route: `GET /admin/tmdb-import` → Page `Admin/TmdbImport.vue`

- Form search theo tên / TMDB ID
- Preview card: poster, tên, năm, rating TMDB
- Nút "Import" → POST `/admin/tmdb-import`
- Tab "Logs" → `/admin/tmdb-import/logs` xem lịch sử

---

## 6 — GIF Support (Tenor API)

### Mục đích

Cho phép user đính kèm GIF vào **review** và **comment** để tăng tính biểu cảm.

### Kiến trúc

Tenor API key không lộ ra client — tất cả request đi qua server proxy:

```
Client (ReviewForm / CommentForm)
    → GET /api/gifs?q=happy  (auth required, throttle 30/min)
        → TenorController::search()
            → TenorService::search()
                → Tenor API (key ẩn server-side)
            → Trả về JSON [{url, preview_url, id}]
```

```env
TENOR_API_KEY=your_key
TENOR_LOCALE=vi_VN
```

GIF URL được lưu trong cột `gif_url` trên `reviews` và `title_comments`.

---

## 7 — Discussion System (Bình luận)

### Schema

```sql
CREATE TABLE title_comments (
    comment_id  BIGINT AUTO_INCREMENT,
    title_id    INT NOT NULL,
    user_id     INT NOT NULL,
    parent_id   BIGINT NULL,         -- NULL = top-level, có giá trị = reply
    body        TEXT NOT NULL,
    gif_url     VARCHAR(500) NULL,
    is_hidden   TINYINT(1) DEFAULT 0,
    deleted_at  TIMESTAMP NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_comments PRIMARY KEY (comment_id),
    CONSTRAINT fk_comment_title FOREIGN KEY (title_id) REFERENCES titles(title_id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user  FOREIGN KEY (user_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_parent FOREIGN KEY (parent_id) REFERENCES title_comments(comment_id) ON DELETE CASCADE
);

CREATE TABLE title_comment_likes (
    user_id    INT NOT NULL,
    comment_id BIGINT NOT NULL,
    CONSTRAINT pk_comment_likes PRIMARY KEY (user_id, comment_id)
);
```

### Phân quyền

| Hành động   | Điều kiện                               |
| ----------- | --------------------------------------- |
| Tạo comment | auth + verified                         |
| Sửa comment | auth + là tác giả                       |
| Xoá comment | auth + là tác giả; hoặc ADMIN/MODERATOR |
| Ẩn comment  | auth + role ADMIN hoặc MODERATOR        |
| Like/Unlike | auth + verified                         |

Route ẩn comment: `POST /comments/{comment}/hide` — middleware `role:ADMIN,MODERATOR`.

---

## 8 — Collections Extended

Ngoài tính năng cơ bản (tạo/sửa/xoá/thêm title), collections đã được mở rộng với:

### Cộng tác viên (Collaborators)

```sql
CREATE TABLE collection_collaborators (
    collection_id INT NOT NULL,
    user_id       INT NOT NULL,
    status        VARCHAR(20) DEFAULT 'PENDING',  -- PENDING | ACCEPTED
    invited_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_collab PRIMARY KEY (collection_id, user_id)
);
```

Flow: Owner mời → user nhận notification `CollectionInvited` → user accept → có quyền thêm/xoá title.

### Ghi chú + Watched (CollectionTitleNote)

```sql
CREATE TABLE collection_title_notes (
    collection_id INT NOT NULL,
    title_id      INT NOT NULL,
    user_id       INT NOT NULL,    -- Ai ghi (owner hoặc collaborator)
    note          TEXT NULL,
    watched       TINYINT(1) DEFAULT 0,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_ctn PRIMARY KEY (collection_id, title_id, user_id)
);
```

Route `PUT /collections/{slug}/titles/{title}/note` → `CollectionTitleNoteController::upsert()`
Route `POST /collections/{slug}/titles/{title}/watch` → `CollectionTitleNoteController::toggleWatch()`

### Publish / Copy / Cover

- **Publish**: chuyển collection sang trạng thái "published" (hiển thị nổi bật)
- **Copy**: tạo bản sao collection của người khác về account mình
- **Cover image**: upload ảnh bìa riêng lên Cloudinary

### Nominations

User có thể đề cử collection lên danh sách nominations cộng đồng. Quota giới hạn qua `NominationQuota` service.

---

## Tóm tắt: Migration & Packages cần bổ sung
