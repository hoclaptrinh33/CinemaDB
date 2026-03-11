<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const { t, locale } = useI18n();

const props = defineProps({
    tab:         { type: String, default: 'reviews' },
    reviews:     { type: Object, default: null },
    comments:    { type: Object, default: null },
    nominations: { type: Object, default: null },
});

const tabs = computed(() => [
    { key: 'reviews',     label: t('activityLog.tabReviews'),     icon: '⭐' },
    { key: 'comments',    label: t('activityLog.tabComments'),    icon: '💬' },
    { key: 'nominations', label: t('activityLog.tabNominations'), icon: '🎯' },
]);

function switchTab(key) {
    router.get(route('activity-log.index'), { tab: key }, { preserveState: false, preserveScroll: false });
}

const activeData = computed(() => {
    if (props.tab === 'reviews')     return props.reviews;
    if (props.tab === 'comments')    return props.comments;
    if (props.tab === 'nominations') return props.nominations;
    return null;
});

function formatDate(val) {
    if (!val) return '';
    return new Date(val).toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-US', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

const ratingStars = (r) => '★'.repeat(Math.round(r / 2)) + '☆'.repeat(5 - Math.round(r / 2));
</script>

<template>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
        <!-- Page title -->
        <div class="mb-8">
            <h1 class="font-display font-black text-2xl text-[var(--color-text-primary)]">📋 {{ t('activityLog.title') }}</h1>
            <p class="text-[var(--color-text-muted)] text-sm mt-1">{{ t('activityLog.subtitle') }}</p>
        </div>

        <!-- Tabs -->
        <div class="flex items-center gap-1 p-1 rounded-xl bg-[var(--color-bg-elevated)] border border-[var(--color-border)] w-fit mb-6">
            <button
                v-for="t in tabs"
                :key="t.key"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                :class="tab === t.key
                    ? 'bg-[var(--color-accent)] text-white shadow'
                    : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-overlay)]'"
                @click="switchTab(t.key)"
            >
                <span>{{ t.icon }}</span>
                <span>{{ t.label }}</span>
            </button>
        </div>

        <!-- Content area -->
        <div v-if="!activeData" class="flex items-center justify-center py-20 text-[var(--color-text-muted)]">
            {{ t('common.loading') }}
        </div>

        <!-- ── REVIEWS TAB ── -->
        <template v-else-if="tab === 'reviews'">
            <div v-if="reviews.data.length === 0" class="flex flex-col items-center py-20 gap-3 text-[var(--color-text-muted)]">
                <span class="text-4xl">⭐</span>
                <p>{{ t('activityLog.noReviews') }}</p>
                <Link :href="route('titles.index')" class="btn btn-primary mt-2">{{ t('activityLog.exploreMovies') }}</Link>
            </div>
            <div v-else class="space-y-3">
                <div
                    v-for="r in reviews.data"
                    :key="r.review_id"
                    class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg-elevated)] p-4 hover:border-[var(--color-accent)]/40 transition-colors"
                >
                    <div class="flex items-start gap-4">
                        <!-- Poster -->
                        <div class="shrink-0 w-12 h-16 rounded-lg overflow-hidden bg-zinc-800">
                            <img v-if="r.title_poster" :src="r.title_poster" :alt="r.title_name" class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-xl">🎬</div>
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <Link
                                    v-if="r.title_slug"
                                    :href="route('titles.show', r.title_slug)"
                                    class="font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-accent)] transition-colors truncate max-w-xs"
                                >{{ r.title_name }}</Link>
                                <span v-else class="font-semibold text-[var(--color-text-primary)] truncate max-w-xs">{{ r.title_name }}</span>
                                <span class="text-xs text-[var(--color-text-muted)] shrink-0">{{ formatDate(r.created_at) }}</span>
                            </div>
                            <!-- Rating -->
                            <p class="text-yellow-400 text-sm mt-1 tracking-wider">{{ ratingStars(r.rating) }} <span class="text-[var(--color-text-muted)] text-xs ml-1">{{ r.rating }}/10</span></p>
                            <!-- Text -->
                            <p v-if="r.review_text" class="text-sm text-[var(--color-text-secondary)] mt-1.5 line-clamp-2 leading-relaxed">
                                <span v-if="r.has_spoilers" class="text-amber-400 text-xs font-medium mr-1">[Spoiler]</span>
                                {{ r.review_text }}
                            </p>
                            <!-- Helpful votes -->
                            <div class="flex items-center gap-3 mt-2">
                                <span class="text-xs text-[var(--color-text-muted)]">👍 {{ t('activityLog.helpfulVotes', { n: r.helpful_votes }) }}</span>
                                <span class="text-xs px-1.5 py-0.5 rounded bg-zinc-700/40 text-zinc-400">{{ r.title_type }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="reviews.last_page > 1" class="flex items-center justify-center gap-2 pt-4">
                    <Link
                        v-for="p in reviews.links"
                        :key="p.label"
                        :href="p.url ? (p.url + '&tab=reviews') : '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="p.active
                            ? 'bg-[var(--color-accent)] text-white'
                            : p.url
                            ? 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)]'
                            : 'text-[var(--color-text-muted)] cursor-default opacity-40'"
                        v-html="p.label"
                    />
                </div>
            </div>
        </template>

        <!-- ── COMMENTS TAB ── -->
        <template v-else-if="tab === 'comments'">
            <div v-if="comments.data.length === 0" class="flex flex-col items-center py-20 gap-3 text-[var(--color-text-muted)]">
                <span class="text-4xl">💬</span>
                <p>{{ t('activityLog.noComments') }}</p>
            </div>
            <div v-else class="space-y-3">
                <div
                    v-for="c in comments.data"
                    :key="c.comment_id"
                    class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg-elevated)] p-4 hover:border-[var(--color-accent)]/40 transition-colors"
                >
                    <div class="flex items-start justify-between gap-2 flex-wrap mb-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <Link
                                v-if="c.title_slug"
                                :href="route('titles.show', c.title_slug)"
                                class="text-sm font-semibold text-[var(--color-accent)] hover:underline"
                            >{{ c.title_name }}</Link>
                            <span v-if="c.parent_id" class="text-xs px-1.5 py-0.5 rounded bg-blue-500/15 text-blue-400">{{ t('activityLog.reply') }}</span>
                        </div>
                        <span class="text-xs text-[var(--color-text-muted)]">{{ formatDate(c.created_at) }}</span>
                    </div>
                    <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed">{{ c.content }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs text-[var(--color-text-muted)]">❤️ {{ t('activityLog.likes', { n: c.likes }) }}</span>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="comments.last_page > 1" class="flex items-center justify-center gap-2 pt-4">
                    <Link
                        v-for="p in comments.links"
                        :key="p.label"
                        :href="p.url ? (p.url + '&tab=comments') : '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="p.active
                            ? 'bg-[var(--color-accent)] text-white'
                            : p.url
                            ? 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)]'
                            : 'text-[var(--color-text-muted)] cursor-default opacity-40'"
                        v-html="p.label"
                    />
                </div>
            </div>
        </template>

        <!-- ── NOMINATIONS TAB ── -->
        <template v-else-if="tab === 'nominations'">
            <div v-if="nominations.data.length === 0" class="flex flex-col items-center py-20 gap-3 text-[var(--color-text-muted)]">
                <span class="text-4xl">🎯</span>
                <p>{{ t('activityLog.noNominations') }}</p>
                <Link :href="route('titles.index')" class="btn btn-primary mt-2">{{ t('activityLog.exploreMovies') }}</Link>
            </div>
            <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                <div
                    v-for="n in nominations.data"
                    :key="n.nomination_id"
                    class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg-elevated)] overflow-hidden hover:border-[var(--color-accent)]/40 transition-colors group"
                >
                    <Link :href="n.title_slug ? route('titles.show', n.title_slug) : '#'" class="block">
                        <div class="aspect-[2/3] bg-zinc-800 overflow-hidden">
                            <img v-if="n.title_poster" :src="n.title_poster" :alt="n.title_name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div v-else class="w-full h-full flex items-center justify-center text-3xl">🎬</div>
                        </div>
                        <div class="p-2">
                            <p class="text-xs font-medium text-[var(--color-text-primary)] truncate leading-snug">{{ n.title_name }}</p>
                            <p class="text-[10px] text-[var(--color-text-muted)] mt-0.5">{{ formatDate(n.created_at) }}</p>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="nominations.last_page > 1" class="flex items-center justify-center gap-2 pt-6">
                <Link
                    v-for="p in nominations.links"
                    :key="p.label"
                    :href="p.url ? (p.url + '&tab=nominations') : '#'"
                    class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                    :class="p.active
                        ? 'bg-[var(--color-accent)] text-white'
                        : p.url
                        ? 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)]'
                        : 'text-[var(--color-text-muted)] cursor-default opacity-40'"
                    v-html="p.label"
                />
            </div>
        </template>
    </div>
</template>
