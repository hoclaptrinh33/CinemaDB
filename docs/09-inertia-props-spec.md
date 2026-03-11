# 09 — Inertia Props Spec

> Tài liệu này mô tả **toàn bộ props** mà mỗi Inertia page nhận từ controller.  
> Dùng làm "contract" giữa backend và frontend — tránh refactor sau này.

---

## Shared Props (mọi page đều có)

Khai báo trong `HandleInertiaRequests::share()`:

```ts
// Luôn có trong usePage().props
interface SharedProps {
    auth: {
        user: {
            id: number;
            username: string;
            role: "ADMIN" | "MODERATOR" | "USER";
            reputation: number;
            avatar: string | null;
        } | null;
        can: {
            accessAdmin: boolean;
            accessModerate: boolean;
        };
    };
    flash: {
        success: string | null;
        error: string | null;
    };
}
```

---

## Public Pages

### `Home` — `HomeController@index`

```ts
interface HomeProps {
    featured: Title; // 1 phim nổi bật cho hero banner
    latest: Title[]; // 12 phim mới nhất
    topRated: Title[]; // 12 phim được đánh giá cao nhất
    genres: Genre[]; // Tất cả genres (dùng filter bar)
}

interface Title {
    id: number;
    title_name: string;
    slug: string;
    title_type: "MOVIE" | "SERIES" | "EPISODE";
    release_date: string | null;
    runtime_mins: number | null;
    poster_url: string;
    backdrop_url: string | null;
    avg_rating: number | null;
    review_count: number;
}

interface Genre {
    id: number;
    genre_name: string;
    slug: string;
}
```

---

### `Titles/Index` — `Public\TitleController@index`

```ts
interface TitlesIndexProps {
    titles: Paginated<Title>;
    filters: {
        search: string | null;
        type: "MOVIE" | "SERIES" | "EPISODE" | null;
        year: number | null;
        language_id: number | null;
        genre_id: number | null;
        sort: "latest" | "rating" | "title" | null;
    };
    languages: { id: number; language_name: string }[];
    genres: Genre[];
    years: number[]; // Các năm có dữ liệu phim, để populate filter
}

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
}
```

---

### `Titles/Show` — `Public\TitleController@show`

```ts
interface TitleShowProps {
    title: TitleDetail;
    cast: CastMember[]; // persons với role = Actor, sắp xếp theo cast_order
    crew: CrewMember[]; // Director, Writer, Producer, ...
    reviews: Paginated<ReviewItem>;
    userReview: UserReview | null; // Review của auth user (nếu có)
    relatedTitles: Title[]; // Phim tương tự (cùng genre, giới hạn 8)
    can: {
        review: boolean; // Được viết review không?
        toggleVisibility: boolean; // Mod/Admin: ẩn/hiện title
    };
}

interface TitleDetail extends Title {
    original_title: string | null;
    description: string | null;
    trailer_url: string | null;
    status: string | null;
    budget: number | null;
    revenue: number | null;
    language: { id: number; language_name: string } | null;
    studios: { id: number; studio_name: string; logo_url: string | null }[];
    genres: Genre[];
    countries: { id: number; country_name: string; country_code: string }[];
    // Series riêng
    seasons_count?: number;
    episodes_count?: number;
}

interface CastMember {
    person_id: number;
    full_name: string;
    photo_url: string | null;
    character_name: string | null;
    cast_order: number;
}

interface CrewMember {
    person_id: number;
    full_name: string;
    photo_url: string | null;
    role_name: string; // "Director", "Writer", ...
}

interface ReviewItem {
    id: number;
    user: {
        id: number;
        username: string;
        avatar: string | null;
        reputation: number;
    };
    rating: number | null;
    review_text: string | null;
    contains_spoiler: boolean;
    helpful_count: number;
    voted_helpful: boolean; // Auth user đã vote helpful chưa?
    created_at: string;
    moderation_status: "VISIBLE" | "HIDDEN";
}

interface UserReview extends ReviewItem {
    can: { update: boolean; delete: boolean };
}
```

---

### `Persons/Show` — `Public\PersonController@show`

```ts
interface PersonShowProps {
    person: {
        person_id: number;
        full_name: string;
        birth_name: string | null;
        date_of_birth: string | null;
        nationality: string | null;
        photo_url: string | null;
        biography: string | null;
    };
    filmography: {
        role_name: string; // "Actor", "Director", ...
        titles: (Title & { character_name?: string })[];
    }[];
}
```

---

### `Studios/Show` — `Public\StudioController@show`

```ts
interface StudioShowProps {
    studio: {
        studio_id: number;
        studio_name: string;
        country: string | null;
        logo_url: string | null;
        website: string | null;
        description: string | null;
    };
    titles: Paginated<Title>;
}
```

---

## Admin Pages

### `Admin/Dashboard` — `Admin\DashboardController@index`

```ts
interface AdminDashboardProps {
    stats: {
        total_titles: number;
        total_users: number;
        total_reviews: number;
        pending_reviews: number; // review VISIBLE chờ kiểm
        hidden_titles: number;
    };
    recentTitles: (Title & { created_by?: string })[];
    recentReviews: (ReviewItem & { title_name: string })[];
    recentUsers: {
        id: number;
        username: string;
        email: string;
        role: string;
        created_at: string;
    }[];
}
```

---

### `Admin/Titles/Index` — `Admin\TitleController@index`

```ts
interface AdminTitlesIndexProps {
    titles: Paginated<
        Title & {
            language_name: string | null;
            visibility:
                | "PUBLIC"
                | "HIDDEN"
                | "COPYRIGHT_STRIKE"
                | "GEO_BLOCKED";
        }
    >;
    filters: {
        search: string | null;
        type: string | null;
        visibility: string | null;
    };
}
```

---

### `Admin/Titles/Form` — `Admin\TitleController@create` / `@edit`

```ts
interface AdminTitleFormProps {
    title: TitleDetail | null; // null khi tạo mới
    languages: { id: number; language_name: string }[];
    studios: { id: number; studio_name: string }[];
    countries: { id: number; country_name: string; country_code: string }[];
    genres: Genre[];
    // Xác định mode:
    mode: "create" | "edit";
}
```

---

### `Admin/Persons/Index` & `Admin/Persons/Form`

```ts
interface AdminPersonsIndexProps {
    persons: Paginated<{
        person_id: number;
        full_name: string;
        photo_url: string | null;
        titles_count: number;
    }>;
    filters: { search: string | null };
}

interface AdminPersonFormProps {
    person: {
        person_id: number;
        full_name: string;
        birth_name: string | null;
        date_of_birth: string | null;
        nationality: string | null;
        photo_url: string | null;
        biography: string | null;
    } | null;
    nationalities: string[]; // Danh sách quốc tịch để autocomplete
    mode: "create" | "edit";
}
```

---

### `Admin/Users/Index` — `Admin\UserController@index`

```ts
interface AdminUsersIndexProps {
    users: Paginated<{
        user_id: number;
        username: string;
        email: string;
        role: "ADMIN" | "MODERATOR" | "USER";
        reputation: number;
        is_active: boolean;
        reviews_count: number;
        created_at: string;
    }>;
    filters: {
        search: string | null;
        role: string | null;
        active: boolean | null;
    };
}
```

---

### `Admin/Reviews/Index` — `Admin\ReviewController@index`

```ts
interface AdminReviewsIndexProps {
    reviews: Paginated<
        ReviewItem & {
            title_name: string;
            title_slug: string;
        }
    >;
    filters: {
        search: string | null;
        status: "VISIBLE" | "HIDDEN" | null;
    };
}
```

---

### `Admin/AuditLog/Index` — `Admin\AuditLogController@index`

```ts
interface AdminAuditLogProps {
    logs: Paginated<{
        log_id: number;
        title: { id: number; title_name: string };
        user: { id: number; username: string } | null;
        action: string;
        old_data: Record<string, unknown> | null;
        new_data: Record<string, unknown> | null;
        changed_at: string;
    }>;
    filters: {
        title_id: number | null;
        user_id: number | null;
        action: string | null;
    };
}
```

---

## Moderate Pages

### `Moderate/Reviews/Index` — `Moderate\ReviewController@index`

> Dùng chung với `Admin/Reviews/Index`, khác middleware route.

```ts
// Giống AdminReviewsIndexProps nhưng `can` khác:
interface ModerateReviewsIndexProps extends AdminReviewsIndexProps {
    // can.forceDelete chỉ ADMIN
    can: { forceDelete: boolean };
}
```

---

## Advanced Feature Pages (Phase 10-12)

### `Watchlist/Index` — `WatchlistController@index`

```ts
interface WatchlistIndexProps {
    lists: {
        watchlist_id: number;
        list_name: string;
        is_public: boolean;
        item_count: number;
        cover_url: string | null;
    }[];
    recentActivity: {
        action: "added" | "watched" | "reviewed";
        title: Title;
        at: string;
    }[];
}
```

---

### `Profile/Show` — `ProfileController@show`

```ts
interface ProfileShowProps {
    profileUser: {
        user_id: number;
        username: string;
        avatar_url: string | null;
        reputation: number;
        rank: {
            name: string;
            color: string; // Tailwind class, vd: "text-yellow-500"
            icon: string;
        };
        reviews_count: number;
        helpful_votes_received: number;
        joined_at: string;
    };
    recentReviews: ReviewItem[];
    badges: {
        badge_id: number;
        badge_name: string;
        icon: string;
        earned_at: string;
    }[];
    publicLists: {
        watchlist_id: number;
        list_name: string;
        item_count: number;
    }[];
    isOwner: boolean; // auth user xem profile của chính mình không?
}
```

---

## Conventions

### Pagination resource `TitleResource`

```php
// app/Http/Resources/TitleResource.php
class TitleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->title_id,
            'title_name'   => $this->title_name,
            'slug'         => $this->slug,
            'title_type'   => $this->title_type,
            'release_date' => $this->release_date?->format('Y-m-d'),
            'runtime_mins' => $this->runtime_mins,
            'poster_url'   => $this->poster_url,        // accessor
            'backdrop_url' => $this->backdrop_url,      // accessor
            'avg_rating'   => round($this->avg_rating ?? 0, 1),
            'review_count' => $this->review_count ?? 0,
            'visibility'   => $this->visibility,
        ];
    }
}
```

### Dùng `Inertia::render()` với Resource

```php
return Inertia::render('Titles/Index', [
    'titles'  => TitleResource::collection($titles),
    'filters' => $filters,
]);
```

### Nhận props trong Vue

```vue
<script setup lang="ts">
// Với TypeScript (tùy chọn, khuyến khích Phase 5+)
interface Props {
    titles: Paginated<Title>;
    filters: Record<string, unknown>;
}
const props = defineProps<Props>();
</script>
```

---

## `.d.ts` type stubs (tuỳ chọn)

Để không phải khai báo lại mỗi file, tạo file:

```ts
// resources/js/types/inertia.d.ts
import type { PageProps as InertiaPageProps } from "@inertiajs/core";

declare module "@inertiajs/core" {
    interface PageProps extends InertiaPageProps {
        auth: SharedProps["auth"];
        flash: SharedProps["flash"];
    }
}
```
