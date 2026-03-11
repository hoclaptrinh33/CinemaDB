<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { notifIcon, notifTitle } from '@/utils/notifHelpers.js';

const props = defineProps({
    notifications: Object,
    filter:        { type: String, default: 'all' },
    selectedType:  { type: String, default: null },
    unreadCount:   { type: Number, default: 0 },
});

const localReadIds      = ref([]);
const localUnreadCount  = ref(props.unreadCount);

const TYPE_CATEGORIES = {
    new_follower:       'follow',
    review_helpful:     'helpful',
    comment_replied:    'comment',
    comment_liked:      'comment',
    badge_earned:       'system',
    level_up:           'system',
    collection_invited: 'system',
};

const GROUP_META = {
    follow:  { label: 'Theo dõi',         icon: '👤' },
    helpful: { label: 'Đánh giá hữu ích', icon: '👍' },
    comment: { label: 'Bình luận',         icon: '💬' },
    system:  { label: 'Hệ thống',          icon: '🏅' },
};

const TYPE_CHIPS = [
    { key: 'follow',  label: '👤 Theo dõi' },
    { key: 'helpful', label: '👍 Hữu ích' },
    { key: 'comment', label: '💬 Bình luận' },
    { key: 'system',  label: '🏅 Hệ thống' },
];

function isRead(n) {
    return n.read_at !== null || localReadIds.value.includes(n.id);
}

const showGrouped = computed(() => !props.selectedType && props.filter === 'all');

const sections = computed(() => {
    if (!showGrouped.value) {
        return [{ group: null, label: null, icon: null, items: props.notifications.data }];
    }
    const groups = { follow: [], helpful: [], comment: [], system: [] };
    for (const n of props.notifications.data) {
        const cat = TYPE_CATEGORIES[n.type] ?? 'system';
        groups[cat].push(n);
    }
    return Object.entries(groups)
        .filter(([, items]) => items.length > 0)
        .map(([key, items]) => ({ group: key, label: GROUP_META[key].label, icon: GROUP_META[key].icon, items }));
});

function navigate(params) {
    const query = {};
    if (params.filter && params.filter !== 'all') query.filter = params.filter;
    if (params.type) query.type = params.type;
    router.get(route('notifications.index'), query, { replace: true });
}

function setFilter(f) { navigate({ filter: f, type: props.selectedType }); }
function setType(type) { navigate({ filter: props.filter, type: type ?? undefined }); }

async function markRead(n) {
    if (isRead(n)) return;
    localReadIds.value.push(n.id);
    if (localUnreadCount.value > 0) localUnreadCount.value--;
    try {
        await axios.post(`/api/notifications/${n.id}/read`);
    } catch {
        const idx = localReadIds.value.indexOf(n.id);
        if (idx > -1) localReadIds.value.splice(idx, 1);
        localUnreadCount.value++;
    }
}

async function markAllRead() {
    const toMark = props.notifications.data.filter(n => !isRead(n));
    if (!toMark.length) return;
    const prevCount = localUnreadCount.value;
    toMark.forEach(n => localReadIds.value.push(n.id));
    localUnreadCount.value = 0;
    try {
        await axios.post('/api/notifications/read-all');
    } catch {
        toMark.forEach(n => {
            const idx = localReadIds.value.indexOf(n.id);
            if (idx > -1) localReadIds.value.splice(idx, 1);
        });
        localUnreadCount.value = prevCount;
    }
}

function handleClick(n) {
    markRead(n);
    if (n.data?.url) router.visit(n.data.url);
}
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

            <!-- ── Page header ── -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold font-display text-[var(--color-text-primary)]">Thông báo</h1>
                    <span
                        v-if="localUnreadCount > 0"
                        class="min-w-[24px] h-6 px-1.5 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center"
                    >{{ localUnreadCount > 99 ? '99+' : localUnreadCount }}</span>
                </div>
                <button
                    v-if="localUnreadCount > 0"
                    class="text-sm text-[var(--color-accent)] hover:underline"
                    @click="markAllRead"
                >Đánh dấu tất cả đã đọc</button>
            </div>

            <!-- ── Filter bar ── -->
            <div class="flex flex-wrap items-center gap-2 mb-6">
                <!-- Read status toggle -->
                <div class="flex rounded-xl bg-[var(--color-bg-elevated)] border border-[var(--color-border)] p-1 gap-0.5">
                    <button
                        v-for="f in [{ key: 'all', label: 'Tất cả' }, { key: 'unread', label: 'Chưa đọc' }]"
                        :key="f.key"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        :class="filter === f.key
                            ? 'bg-[var(--color-accent)] text-white shadow-sm'
                            : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]'"
                        @click="setFilter(f.key)"
                    >
                        {{ f.label }}
                        <span v-if="f.key === 'unread' && localUnreadCount > 0" class="ml-1.5 px-1.5 py-0.5 rounded-full bg-white/20 text-xs font-bold tabular-nums">{{ localUnreadCount }}</span>
                    </button>
                </div>

                <div class="w-px h-6 bg-[var(--color-border)]" />

                <!-- Type chips -->
                <button
                    v-for="chip in TYPE_CHIPS"
                    :key="chip.key"
                    class="px-3 py-1.5 rounded-xl text-sm font-medium transition-colors border"
                    :class="selectedType === chip.key
                        ? 'bg-[var(--color-accent-muted)] border-[var(--color-accent)] text-[var(--color-accent)]'
                        : 'border-[var(--color-border)] bg-[var(--color-bg-elevated)] text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-text-muted)]'"
                    @click="setType(selectedType === chip.key ? null : chip.key)"
                >{{ chip.label }}</button>
            </div>

            <!-- ── Notifications ── -->
            <div v-if="notifications.data.length > 0" class="space-y-6">
                <template v-for="section in sections" :key="section.group ?? 'flat'">
                    <div>
                        <!-- Section header (grouped view only) -->
                        <div v-if="section.group" class="flex items-center gap-2 mb-3">
                            <span class="text-base select-none">{{ section.icon }}</span>
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)]">{{ section.label }}</h2>
                            <span class="text-xs text-[var(--color-text-muted)] tabular-nums">({{ section.items.length }})</span>
                        </div>

                        <!-- Items card -->
                        <div class="rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg-elevated)] overflow-hidden divide-y divide-[var(--color-border)]">
                            <div
                                v-for="n in section.items"
                                :key="n.id"
                                class="flex items-start gap-3 px-4 py-3.5 hover:bg-[var(--color-bg-overlay)] transition-colors group"
                                :class="n.data?.url ? 'cursor-pointer' : 'cursor-default'"
                                @click="handleClick(n)"
                            >
                                <!-- Unread dot -->
                                <div class="shrink-0 mt-[18px]">
                                    <div
                                        class="w-2 h-2 rounded-full transition-all duration-200"
                                        :class="isRead(n) ? 'bg-transparent' : 'bg-blue-500'"
                                    />
                                </div>

                                <!-- Emoji icon -->
                                <span class="text-xl shrink-0 mt-1 select-none">{{ notifIcon[n.type] ?? '🔔' }}</span>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm leading-snug"
                                        :class="isRead(n) ? 'text-[var(--color-text-secondary)]' : 'text-[var(--color-text-primary)] font-medium'"
                                    >{{ notifTitle(n) }}</p>
                                    <p v-if="n.data?.preview" class="text-xs text-[var(--color-text-muted)] mt-0.5 truncate">{{ n.data.preview }}</p>
                                    <p class="text-[11px] text-[var(--color-text-muted)] mt-1">{{ n.created_at }}</p>
                                </div>

                                <!-- Mark read on hover -->
                                <button
                                    v-if="!isRead(n)"
                                    class="shrink-0 opacity-0 group-hover:opacity-100 p-1 rounded-lg text-[var(--color-text-muted)] hover:text-[var(--color-accent)] hover:bg-[var(--color-accent-muted)] transition-all mt-1"
                                    title="Đánh dấu đã đọc"
                                    @click.stop="markRead(n)"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ── Empty state ── -->
            <div v-else class="flex flex-col items-center justify-center py-20 gap-4 text-[var(--color-text-muted)]">
                <svg class="w-16 h-16 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <div class="text-center">
                    <p class="font-medium text-[var(--color-text-secondary)]">
                        {{ selectedType || filter === 'unread' ? 'Không có thông báo phù hợp' : 'Chưa có thông báo nào' }}
                    </p>
                    <p v-if="selectedType || filter === 'unread'" class="text-sm mt-1">
                        <button class="text-[var(--color-accent)] hover:underline" @click="navigate({})">Xem tất cả thông báo</button>
                    </p>
                </div>
            </div>

            <!-- ── Pagination ── -->
            <div v-if="notifications.last_page > 1" class="mt-8 flex items-center justify-center gap-4">
                <button
                    class="btn btn-ghost text-sm"
                    :disabled="!notifications.prev_page_url"
                    :class="{ 'opacity-30 pointer-events-none': !notifications.prev_page_url }"
                    @click="router.visit(notifications.prev_page_url, { replace: true })"
                >← Trước</button>
                <span class="text-sm text-[var(--color-text-muted)] tabular-nums">
                    Trang {{ notifications.current_page }} / {{ notifications.last_page }}
                </span>
                <button
                    class="btn btn-ghost text-sm"
                    :disabled="!notifications.next_page_url"
                    :class="{ 'opacity-30 pointer-events-none': !notifications.next_page_url }"
                    @click="router.visit(notifications.next_page_url, { replace: true })"
                >Sau →</button>
            </div>

    </div>
</template>
