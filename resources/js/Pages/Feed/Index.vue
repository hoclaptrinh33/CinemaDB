<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

defineProps({
    feedItems:      { type: Object, required: true },
    followingCount: { type: Number, default: 0 },
});

// ── Activity type config ──────────────────────────────────────────────
const activityConfig = {
    review_created: {
        icon: '⭐',
        label: (m) => `đã đánh giá ${m.title_name ?? ''}`,
        color: 'text-yellow-400',
    },
    comment_created: {
        icon: '💬',
        label: (m) => `đã bình luận về ${m.title_name ?? ''}`,
        color: 'text-blue-400',
    },
    nomination_created: {
        icon: '🏆',
        label: (m) => `đã đề cử ${m.title_name ?? ''}`,
        color: 'text-purple-400',
    },
    collection_created: {
        icon: '📚',
        label: (m) => `đã tạo bộ sưu tập "${m.collection_name ?? ''}"`,
        color: 'text-emerald-400',
    },
    collection_title_added: {
        icon: '➕',
        label: (m) => `đã thêm ${m.title_name ?? ''} vào "${m.collection_name ?? ''}"`,
        color: 'text-teal-400',
    },
    badge_earned: {
        icon: '🏅',
        label: (m) => `đã đạt huy hiệu "${m.badge_name ?? ''}"`,
        color: 'text-amber-400',
    },
};

function cfg(type) {
    return activityConfig[type] ?? { icon: '📌', label: () => type, color: 'text-[var(--color-text-muted)]' };
}

function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60) return 'Vừa xong';
    if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} ngày trước`;
    return new Date(dateStr).toLocaleDateString('vi-VN');
}

function titleLink(item) {
    const m = item.metadata;
    if (['review_created', 'comment_created', 'nomination_created', 'collection_title_added'].includes(item.activity_type)) {
        if (m?.title_slug) return route('titles.show', m.title_slug);
    }
    if (item.activity_type === 'collection_created' && m?.collection_slug) {
        return route('collections.show', m.collection_slug);
    }
    return null;
}
</script>

<template>
    <Head title="Bảng tin" />

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold font-display text-[var(--color-text-primary)]">Bảng tin</h1>
                <p class="text-sm text-[var(--color-text-muted)] mt-0.5">
                    Hoạt động từ {{ followingCount }} người bạn đang theo dõi
                </p>
            </div>
        </div>

        <!-- Empty state — not following anyone -->
        <div v-if="followingCount === 0" class="card flex flex-col items-center justify-center py-16 gap-4 text-center">
            <div class="w-16 h-16 rounded-full bg-[var(--color-bg-elevated)] flex items-center justify-center text-3xl">
                📡
            </div>
            <div>
                <p class="font-semibold text-[var(--color-text-primary)]">Bảng tin trống</p>
                <p class="text-sm text-[var(--color-text-muted)] mt-1">
                    Hãy theo dõi các thành viên khác để thấy hoạt động của họ tại đây.
                </p>
            </div>
            <Link :href="route('leaderboards.index')" class="btn btn-primary text-sm">
                Khám phá thành viên
            </Link>
        </div>

        <!-- Empty feed but following someone -->
        <div
            v-else-if="feedItems.data.length === 0"
            class="card flex flex-col items-center justify-center py-16 gap-4 text-center"
        >
            <div class="w-16 h-16 rounded-full bg-[var(--color-bg-elevated)] flex items-center justify-center text-3xl">
                🌙
            </div>
            <div>
                <p class="font-semibold text-[var(--color-text-primary)]">Chưa có hoạt động nào</p>
                <p class="text-sm text-[var(--color-text-muted)] mt-1">
                    Những người bạn đang theo dõi chưa có hoạt động gần đây.
                </p>
            </div>
        </div>

        <!-- Feed items -->
        <template v-else>
            <article
                v-for="item in feedItems.data"
                :key="item.id"
                class="card p-4 flex gap-4 group hover:border-[var(--color-accent)]/30 transition-colors"
            >
                <!-- Actor avatar -->
                <Link :href="route('users.show', item.actor.username)" class="shrink-0">
                    <div class="w-10 h-10 rounded-full bg-[var(--color-accent)] flex items-center justify-center text-white font-bold overflow-hidden">
                        <img
                            v-if="item.actor.avatar_url"
                            :src="item.actor.avatar_url"
                            :alt="item.actor.username"
                            class="w-full h-full object-cover"
                        />
                        <span v-else>{{ item.actor.username[0].toUpperCase() }}</span>
                    </div>
                </Link>

                <!-- Content -->
                <div class="flex-1 min-w-0 space-y-2">
                    <!-- Description line -->
                    <p class="text-sm leading-snug">
                        <Link
                            :href="route('users.show', item.actor.username)"
                            class="font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-accent)] transition-colors"
                        >{{ item.actor.name }}</Link>
                        <span class="text-[var(--color-text-secondary)]"> {{ cfg(item.activity_type).label(item.metadata) }}</span>
                    </p>

                    <!-- Poster + title chip for title-linked activities -->
                    <div v-if="item.metadata?.poster_url || item.metadata?.title_name" class="flex items-center gap-3">
                        <img
                            v-if="item.metadata.poster_url"
                            :src="item.metadata.poster_url"
                            :alt="item.metadata.title_name"
                            class="w-10 h-14 object-cover rounded-md bg-[var(--color-bg-elevated)] shrink-0"
                        />
                        <div>
                            <component
                                :is="titleLink(item) ? Link : 'span'"
                                :href="titleLink(item) ?? undefined"
                                class="text-sm font-medium text-[var(--color-text-primary)] hover:text-[var(--color-accent)] transition-colors line-clamp-2"
                            >{{ item.metadata.title_name ?? item.metadata.collection_name }}</component>
                            <!-- Star rating for reviews -->
                            <div v-if="item.activity_type === 'review_created' && item.metadata.rating" class="flex items-center gap-0.5 mt-0.5">
                                <span
                                    v-for="i in 5"
                                    :key="i"
                                    :class="i <= Math.round(item.metadata.rating / 2) ? 'text-yellow-400' : 'text-[var(--color-bg-elevated)]'"
                                    class="text-xs"
                                >★</span>
                                <span class="text-xs text-[var(--color-text-muted)] ml-1">{{ item.metadata.rating }}/10</span>
                            </div>
                        </div>
                    </div>

                    <!-- Badge chip -->
                    <div v-if="item.activity_type === 'badge_earned' && item.metadata?.badge_name" class="flex items-center gap-2">
                        <span class="text-lg">{{ cfg('badge_earned').icon }}</span>
                        <span class="text-sm font-semibold" :class="cfg('badge_earned').color">{{ item.metadata.badge_name }}</span>
                        <span v-if="item.metadata.badge_tier" class="text-xs px-1.5 py-0.5 rounded-full bg-[var(--color-bg-elevated)] text-[var(--color-text-muted)] uppercase tracking-wider">{{ item.metadata.badge_tier }}</span>
                    </div>

                    <!-- Timestamp -->
                    <p class="text-xs text-[var(--color-text-muted)]">{{ timeAgo(item.created_at) }}</p>
                </div>

                <!-- Activity icon badge -->
                <div class="shrink-0 self-start">
                    <span class="text-xl" :title="item.activity_type">{{ cfg(item.activity_type).icon }}</span>
                </div>
            </article>

            <!-- Pagination -->
            <Pagination v-if="feedItems.last_page > 1" :meta="feedItems" />
        </template>
    </div>
</template>
