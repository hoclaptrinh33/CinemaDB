<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import TrailerModal from '@/Components/UI/TrailerModal.vue';
import CastList from '@/Components/Person/CastList.vue';
import TitleGrid from '@/Components/Title/TitleGrid.vue';
import NominateButton from '@/Components/Title/NominateButton.vue';
import AddToCollectionDropdown from '@/Components/Title/AddToCollectionDropdown.vue';
import MediaGallery from '@/Components/Title/MediaGallery.vue';
import ReviewForm from '@/Components/Review/ReviewForm.vue';
import ReviewList from '@/Components/Review/ReviewList.vue';
import CommentList from '@/Components/Discussion/CommentList.vue';
import { useLocaleContent } from '@/composables/useLocaleContent.js';

defineOptions({ layout: AppLayout });

const props = defineProps({
    title:           { type: Object,  required: true },
    cast:            { type: Array,   default: () => [] },
    crew:            { type: Array,   default: () => [] },
    reviews:         { type: Object,  required: true },
    topHelpfulReviews: { type: Array, default: () => [] },
    userReview:      { type: Object,  default: null },
    relatedTitles:   { type: Array,   default: () => [] },
    media:           { type: Array,   default: () => [] },
    comments:        { type: Object,  required: true },
    nominations:     { type: Object,  default: () => ({ count: 0, userNominated: false, dailyLeft: 3 }) },
    userCollections: { type: Array,   default: () => [] },
    can:             { type: Object,  default: () => ({}) },
});

const auth = computed(() => usePage().props.auth);
const showTrailer   = ref(false);
const showReviewForm = ref(false);
const activeCastTab = ref('cast');
const { t } = useI18n();

const titleRef = computed(() => props.title);
const { name: localeName, description: localeDesc } = useLocaleContent(titleRef);

// ── Type badge ──────────────────────────────────────────────────────────────
const typeVariant = { MOVIE: 'movie', SERIES: 'series', EPISODE: 'episode' };
const typeLabel   = computed(() => ({
    MOVIE:   t('titles.movie'),
    SERIES:  t('titles.series'),
    EPISODE: t('titles.episode'),
}));

// ── Crew helpers ────────────────────────────────────────────────────────────
const directors = computed(() => props.crew.filter(c => c.role_name === 'Director'));
const writers   = computed(() => props.crew.filter(c => c.role_name === 'Writer'));

// ── Status label ────────────────────────────────────────────────────────────
const STATUS_MAP = {
    Released: 'Đã phát hành', 'In Production': 'Đang sản xuất',
    Planned: 'Đã lên kế hoạch', Ended: 'Đã kết thúc',
    Canceled: 'Đã hủy', 'Post Production': 'Hậu kỳ',
    'Returning Series': 'Đang tiếp tục',
};

// ── Description expand ─────────────────────────────────────────────────────
const descExpanded = ref(false);
const DESC_MAX     = 280;
const descShort    = computed(() =>
    localeDesc.value && localeDesc.value.length > DESC_MAX
        ? localeDesc.value.slice(0, DESC_MAX) + '…'
        : localeDesc.value
);

// ── Number formatting ───────────────────────────────────────────────────────
function fmt(n) {
    if (!n) return '—';
    return new Intl.NumberFormat('vi-VN').format(n);
}
function fmtCurrency(n) {
    if (!n) return '—';
    if (n >= 1_000_000_000) return '$' + (n / 1_000_000_000).toFixed(1) + ' tỷ';
    if (n >= 1_000_000)     return '$' + (n / 1_000_000).toFixed(1) + ' tr';
    return '$' + fmt(n);
}

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <!-- ════════════════════════════════════════════════════════════════
         SECTION A — Hero (backdrop + poster + info)
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="relative min-h-[520px]">
        <!-- Backdrop -->
        <div class="absolute inset-0 h-[520px] overflow-hidden">
            <img
                v-if="title.backdrop_url"
                :src="title.backdrop_url"
                :alt="localeName"
                class="w-full h-full object-cover object-center"
            />
            <div class="absolute inset-0 bg-gradient-to-b from-black/45 via-[var(--color-bg-base)]/72 to-[var(--color-bg-base)]" />
            <div class="absolute inset-0 bg-gradient-to-r from-[var(--color-bg-base)]/85 via-[var(--color-bg-base)]/30 to-transparent" />
        </div>

        <!-- Hero content -->
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 pt-20 sm:pt-28 pb-10">
            <div class="flex flex-col md:flex-row gap-8">

                <!-- Poster -->
                <div class="shrink-0 flex flex-col items-center gap-3">
                    <img
                        :src="title.poster_url"
                        :alt="localeName"
                        class="w-44 md:w-56 aspect-poster object-cover rounded-2xl shadow-2xl border border-[var(--color-border)]"
                    />
                    <!-- Collection button under poster on mobile -->
                    <div class="flex gap-2 md:hidden">
                        <AddToCollectionDropdown :title-id="title.title_id" :user-collections="userCollections" />
                    </div>
                </div>

                <!-- Info column -->
                <div class="flex-1 space-y-4 pt-2 min-w-0 hero-info-shell">

                    <!-- Type + year + runtime badges row -->
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge :variant="typeVariant[title.title_type]">{{ typeLabel[title.title_type] }}</Badge>
                        <span v-if="title.release_date" class="hero-meta-chip font-mono">
                            {{ title.release_date.slice(0, 4) }}
                        </span>
                        <span v-if="title.runtime_mins" class="hero-meta-chip">
                            {{ title.runtime_mins }} phút
                        </span>
                        <span v-if="title.seasons_count" class="hero-meta-chip">
                            {{ title.seasons_count }} mùa · {{ title.episodes_count }} tập
                        </span>
                        <Badge v-if="title.status" variant="default" class="!text-xs font-normal">
                            {{ STATUS_MAP[title.status] ?? title.status }}
                        </Badge>
                    </div>

                    <!-- Title name -->
                    <h1 class="font-display font-black text-3xl md:text-5xl text-white leading-tight drop-shadow-[0_3px_18px_rgba(0,0,0,0.8)]" style="text-wrap:balance">
                        {{ localeName }}
                    </h1>
                    <p v-if="title.original_title && title.original_title !== title.title_name" class="text-[var(--color-text-muted)] text-sm italic -mt-2">
                        {{ title.original_title }}
                    </p>

                    <!-- Rating + Nominations row -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div v-if="title.avg_rating" class="flex flex-wrap items-center gap-3">
                            <div class="rating-card">
                                <div class="rating-icon-wrap">
                                    <svg class="w-5 h-5 star-filled" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" />
                                    </svg>
                                </div>
                                <div class="flex items-end gap-1.5 leading-none">
                                    <span class="rating-score">{{ Number(title.avg_rating).toFixed(1) }}</span>
                                    <span class="rating-max">/10</span>
                                </div>
                            </div>

                            <div class="rating-count-chip">
                                <span class="rating-count-number">{{ fmt(title.rating_count) }}</span>
                                <span class="rating-count-label">lượt đánh giá</span>
                            </div>

                            <div class="rating-helper-chip">
                                Điểm cộng đồng
                            </div>
                        </div>
                        <div v-else class="text-[var(--color-text-secondary)] text-sm italic">Chưa có đánh giá</div>

                        <NominateButton :title-id="title.title_id" :nominations="nominations" />
                    </div>

                    <!-- Genres -->
                    <div v-if="title.genres?.length" class="flex flex-wrap gap-1.5">
                        <Badge v-for="g in title.genres" :key="g.genre_id" variant="genre">{{ g.genre_name }}</Badge>
                    </div>

                    <!-- Description -->
                    <div v-if="localeDesc" class="max-w-2xl">
                        <p class="text-[var(--color-text-primary)]/95 leading-relaxed text-sm md:text-base drop-shadow-[0_1px_8px_rgba(0,0,0,0.55)]">
                            {{ descExpanded ? localeDesc : descShort }}
                        </p>
                        <button
                            v-if="localeDesc.length > DESC_MAX"
                            type="button"
                            class="mt-1 text-xs text-[var(--color-accent)] hover:underline"
                            @click="descExpanded = !descExpanded"
                        >{{ descExpanded ? 'Thu gọn' : 'Xem thêm' }}</button>
                    </div>

                    <!-- Director / Writer inline -->
                    <dl v-if="directors.length || writers.length" class="flex flex-wrap gap-x-6 gap-y-1 text-sm drop-shadow-[0_1px_4px_rgba(0,0,0,0.5)]">
                        <div v-if="directors.length">
                            <dt class="text-[var(--color-text-secondary)] text-xs font-semibold uppercase tracking-wider inline">Đạo diễn: </dt>
                            <dd class="text-[var(--color-text-primary)] inline">{{ directors.map(d => d.full_name).join(', ') }}</dd>
                        </div>
                        <div v-if="writers.length">
                            <dt class="text-[var(--color-text-secondary)] text-xs font-semibold uppercase tracking-wider inline">Biên kịch: </dt>
                            <dd class="text-[var(--color-text-primary)] inline">{{ writers.map(w => w.full_name).join(', ') }}</dd>
                        </div>
                    </dl>

                    <!-- CTA row -->
                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <div class="hidden md:flex items-center gap-2">
                            <AddToCollectionDropdown :title-id="title.title_id" :user-collections="userCollections" />
                        </div>
                        <Button v-if="title.trailer_url" variant="primary" size="lg" @click="showTrailer = true">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653Z" />
                            </svg>
                            Xem Trailer
                        </Button>
                        <button type="button" class="quick-nav" @click="scrollTo('section-cast')">Diễn viên</button>
                        <button type="button" class="quick-nav" @click="scrollTo('section-reviews')">Đánh giá</button>
                        <button type="button" class="quick-nav" @click="scrollTo('section-discussion')">Thảo luận</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════
         Content sections
    ═══════════════════════════════════════════════════════════════════ -->
    <div class="relative z-0 max-w-7xl mx-auto px-4 sm:px-6 pb-20 space-y-14">

        <!-- ── SECTION B: Cast & Crew ──────────────────────────────── -->
        <section v-if="cast.length || crew.length" id="section-cast">
            <div class="flex items-center gap-4 mb-5 border-b border-[var(--color-border)] pb-2">
                <button type="button" :class="['section-tab', activeCastTab === 'cast' && 'active']" @click="activeCastTab = 'cast'">
                    {{ t('show.cast') }} ({{ cast.length }})
                </button>
                <button v-if="crew.length" type="button" :class="['section-tab', activeCastTab === 'crew' && 'active']" @click="activeCastTab = 'crew'">
                    {{ t('show.crew') }} ({{ crew.length }})
                </button>
            </div>
            <CastList :cast="activeCastTab === 'cast' ? cast : []" :crew="activeCastTab === 'crew' ? crew : []" />
        </section>

        <!-- ── SECTION C: Details grid ───────────────────────────── -->
        <section>
            <h2 class="section-heading">Thông tin chi tiết</h2>
            <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-4">
                <div v-if="title.language">
                    <dt class="meta-label">Ngôn ngữ gốc</dt>
                    <dd class="meta-value">{{ title.language.language_name }}</dd>
                </div>
                <div v-if="title.countries?.length">
                    <dt class="meta-label">Quốc gia</dt>
                    <dd class="meta-value">{{ title.countries.map(c => c.country_name).join(', ') }}</dd>
                </div>
                <div v-if="title.studios?.length">
                    <dt class="meta-label">Studio / Hãng phim</dt>
                    <dd class="meta-value">{{ title.studios.map(s => s.studio_name).join(', ') }}</dd>
                </div>
                <div v-if="title.status">
                    <dt class="meta-label">Trạng thái</dt>
                    <dd class="meta-value">{{ STATUS_MAP[title.status] ?? title.status }}</dd>
                </div>
                <div v-if="title.release_date">
                    <dt class="meta-label">Phát hành</dt>
                    <dd class="meta-value font-mono text-sm">{{ new Date(title.release_date).toLocaleDateString('vi-VN') }}</dd>
                </div>
                <div v-if="title.runtime_mins">
                    <dt class="meta-label">Thời lượng</dt>
                    <dd class="meta-value">{{ title.runtime_mins }} phút</dd>
                </div>
                <div v-if="title.seasons_count">
                    <dt class="meta-label">Mùa / Tập</dt>
                    <dd class="meta-value">{{ title.seasons_count }} mùa · {{ title.episodes_count }} tập</dd>
                </div>
                <div v-if="title.budget">
                    <dt class="meta-label">Ngân sách</dt>
                    <dd class="meta-value">{{ fmtCurrency(title.budget) }}</dd>
                </div>
                <div v-if="title.revenue">
                    <dt class="meta-label">Doanh thu</dt>
                    <dd class="meta-value">{{ fmtCurrency(title.revenue) }}</dd>
                </div>
                <div v-if="nominations.count">
                    <dt class="meta-label">Lượt đề cử</dt>
                    <dd class="meta-value text-amber-400 font-semibold">{{ fmt(nominations.count) }}</dd>
                </div>
            </dl>
        </section>

        <!-- ── SECTION D: Media gallery ────────────────────────── -->
        <section v-if="media.length">
            <h2 class="section-heading">Truyền thông &amp; Trailer</h2>
            <MediaGallery :media="media" />
        </section>

        <!-- ── SECTION E: Reviews ───────────────────────────────── -->
        <section id="section-reviews">
            <div class="flex items-center justify-between mb-5">
                <h2 class="section-heading !mb-0">
                    Đánh giá phim
                    <span class="text-[var(--color-text-muted)] font-normal text-base normal-case">({{ reviews.total }})</span>
                </h2>
            </div>

            <div v-if="auth.user && can.review" class="mb-6 space-y-3">
                <button
                    v-if="!showReviewForm"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-[var(--color-accent)]/50 bg-[var(--color-bg-elevated)] px-4 py-2 text-sm font-semibold text-[var(--color-text-primary)] hover:border-[var(--color-accent)] hover:text-[var(--color-accent)] transition-colors"
                    @click="showReviewForm = true"
                >
                    Viết đánh giá
                </button>

                <div v-else class="space-y-3">
                    <ReviewForm :title-id="title.title_id" :title-slug="title.slug" :user-review="userReview" />
                    <button
                        type="button"
                        class="text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]"
                        @click="showReviewForm = false"
                    >
                        Ẩn khung đánh giá
                    </button>
                </div>
            </div>
            <div v-else-if="auth.user && userReview" class="card p-4 mb-6 text-sm text-[var(--color-text-muted)] border-l-2 border-[var(--color-accent)]/40">
                Bạn đã đánh giá phim này. Mỗi người chỉ được đánh giá 1 lần.
            </div>
            <div v-else-if="!auth.user" class="card p-5 mb-6 text-center">
                <p class="text-[var(--color-text-muted)] text-sm">
                    <a :href="route('login')" class="text-[var(--color-accent)] hover:underline">Đăng nhập</a> để đánh giá phim.
                </p>
            </div>

            <ReviewList :reviews="reviews" :fallback-reviews="topHelpfulReviews" />
        </section>

        <!-- ── SECTION F: Discussion ────────────────────────────── -->
        <section id="section-discussion">
            <div class="flex items-center justify-between mb-2">
                <h2 class="section-heading !mb-0">
                    Thảo luận
                    <span class="text-[var(--color-text-muted)] font-normal text-base normal-case">({{ comments.total }})</span>
                </h2>
            </div>
            <p class="text-xs text-[var(--color-text-muted)] mb-5">
                Bình luận thoải mái nhiều lần, khác với đánh giá 1 lần ở trên. Hỗ trợ emoji &amp; GIF.
            </p>

            <div v-if="!auth.user" class="card p-5 mb-4 text-center">
                <p class="text-[var(--color-text-muted)] text-sm">
                    <a :href="route('login')" class="text-[var(--color-accent)] hover:underline">Đăng nhập</a> để tham gia thảo luận.
                </p>
            </div>

            <CommentList :comments="comments" :title-id="title.title_id" :can-comment="can.comment" />
        </section>

        <!-- ── SECTION G: Related titles ────────────────────────── -->
        <section v-if="relatedTitles.length">
            <h2 class="section-heading">Phim tương tự bạn có thể thích</h2>
            <TitleGrid :titles="relatedTitles" :cols="4" />
        </section>
    </div>

    <!-- Trailer modal -->
    <TrailerModal :show="showTrailer" :trailer-url="title.trailer_url" :title-name="localeName" @close="showTrailer = false" />
</template>

<style scoped>
.section-heading {
    font-family: var(--font-display);
    font-weight: 700;
    font-size: 1rem;
    color: var(--color-text-primary);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--color-border);
}
.section-tab {
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--color-text-muted);
    padding-bottom: 0.5rem;
    border-bottom: 2px solid transparent;
    transition: color 0.15s, border-color 0.15s;
}
.section-tab.active { color: var(--color-text-primary); border-color: var(--color-accent); }
.section-tab:hover:not(.active) { color: var(--color-text-secondary); }
.meta-label {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--color-text-muted);
    margin-bottom: 0.2rem;
}
.meta-value { color: var(--color-text-primary); font-size: 0.875rem; }
.quick-nav {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    text-decoration: underline;
    text-underline-offset: 2px;
    transition: color 0.15s;
    padding: 0.5rem 0.25rem;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
}
.quick-nav:hover { color: var(--color-accent); }

.hero-info-shell {
    background: linear-gradient(120deg, rgba(9, 9, 15, 0.68), rgba(9, 9, 15, 0.35));
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 16px;
    backdrop-filter: blur(4px);
    padding: 0.9rem 1rem 1rem;
}
.hero-meta-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.26rem 0.62rem;
    border-radius: 9999px;
    background: rgba(15, 15, 26, 0.72);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #c9c6d6;
    font-size: 0.78rem;
    line-height: 1;
}
.rating-card {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.45rem 0.75rem;
    border-radius: 12px;
    background: linear-gradient(180deg, rgba(245, 197, 24, 0.34), rgba(245, 197, 24, 0.2));
    border: 1px solid rgba(245, 197, 24, 0.4);
}
.rating-icon-wrap {
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 15, 26, 0.55);
}
.rating-score {
    font-family: var(--font-display);
    font-size: 1.8rem;
    font-weight: 800;
    color: #ffe17a;
    letter-spacing: -0.02em;
}
.rating-max {
    color: #d6d2c0;
    font-size: 0.8rem;
    margin-bottom: 0.2rem;
}
.rating-count-chip {
    display: inline-flex;
    align-items: baseline;
    gap: 0.38rem;
    padding: 0.4rem 0.62rem;
    border-radius: 10px;
    background: rgba(14, 16, 34, 0.74);
    border: 1px solid rgba(107, 114, 128, 0.38);
}
.rating-count-number {
    color: var(--color-text-primary);
    font-weight: 700;
    font-family: var(--font-display);
    font-size: 0.95rem;
}
.rating-count-label {
    color: var(--color-text-secondary);
    font-size: 0.73rem;
}
.rating-helper-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.32rem 0.58rem;
    border-radius: 9999px;
    background: rgba(34, 197, 94, 0.18);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #86efac;
    font-size: 0.72rem;
    font-weight: 600;
}
</style>
