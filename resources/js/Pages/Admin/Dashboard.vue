<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/UI/Badge.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    stats:         { type: Object, required: true },
    recentTitles:  { type: Array, default: () => [] },
    recentReviews: { type: Array, default: () => [] },
    recentUsers:   { type: Array, default: () => [] },
});

function fmt(n) { return new Intl.NumberFormat('vi-VN').format(n ?? 0); }
function fmtDate(d) { return new Date(d).toLocaleDateString('vi-VN'); }

const statCards = (stats) => [
    { label: 'Tổng titles',     value: fmt(stats.total_titles),   icon: 'M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125v-1.5c0-.621.504-1.125 1.125-1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125', color: 'text-blue-400', bg: 'bg-blue-950/40 border-blue-900/50' },
    { label: 'Người dùng',      value: fmt(stats.total_users),    icon: 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75', color: 'text-green-400', bg: 'bg-green-950/40 border-green-900/50' },
    { label: 'Đánh giá',        value: fmt(stats.total_reviews),  icon: 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21', color: 'text-purple-400', bg: 'bg-purple-950/40 border-purple-900/50' },
    { label: 'Chờ kiểm duyệt',  value: fmt(stats.pending_reviews), icon: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z', color: 'text-amber-400', bg: 'bg-amber-950/40 border-amber-900/50' },
];
</script>

<template>
    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">Dashboard</h1>
    </div>

    <!-- Stats grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div
            v-for="s in statCards(stats)"
            :key="s.label"
            :class="['card border p-4 flex items-center gap-4', s.bg]"
        >
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', s.bg]">
                <svg :class="['w-5 h-5', s.color]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="s.icon" />
                </svg>
            </div>
            <div>
                <p class="text-[var(--color-text-muted)] text-xs font-semibold uppercase tracking-wider">{{ s.label }}</p>
                <p :class="['font-mono font-semibold tabular-nums text-3xl mt-0.5 tracking-tight', s.color]">{{ s.value }}</p>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Recent Titles -->
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[var(--color-border)]">
                <h3 class="font-display font-bold text-sm uppercase tracking-widest text-[var(--color-text-muted)]">Titles mới thêm</h3>
                <Link :href="route('admin.titles.index')" class="text-xs text-[var(--color-accent)] hover:underline">Xem tất cả →</Link>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Tên</th><th>Loại</th><th>Người thêm</th></tr>
                </thead>
                <tbody>
                    <tr v-for="t in recentTitles" :key="t.title_id">
                        <td>
                            <Link :href="route('admin.titles.edit', t.slug)" class="text-[var(--color-text-primary)] hover:text-[var(--color-accent)] transition-colors font-medium text-sm">
                                {{ t.title_name }}
                            </Link>
                        </td>
                        <td>
                            <Badge :variant="t.title_type === 'MOVIE' ? 'movie' : t.title_type === 'SERIES' ? 'series' : 'episode'">{{ t.title_type }}</Badge>
                        </td>
                        <td class="text-[var(--color-text-muted)] text-xs">{{ t.created_by ?? '—' }}</td>
                    </tr>
                    <tr v-if="!recentTitles.length">
                        <td colspan="3" class="text-center text-[var(--color-text-muted)] py-6 text-sm">Chưa có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Recent Reviews -->
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[var(--color-border)]">
                <h3 class="font-display font-bold text-sm uppercase tracking-widest text-[var(--color-text-muted)]">Đánh giá mới</h3>
                <Link :href="route('admin.reviews.index')" class="text-xs text-[var(--color-accent)] hover:underline">Xem tất cả →</Link>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Người dùng</th><th>Phim</th><th>Điểm</th></tr>
                </thead>
                <tbody>
                    <tr v-for="r in recentReviews" :key="r.review_id">
                        <td class="text-sm text-[var(--color-text-primary)] font-medium">{{ r.user?.username ?? '—' }}</td>
                        <td class="text-sm text-[var(--color-text-muted)] max-w-[160px]">
                            <span class="truncate block">{{ r.title?.title_name ?? '—' }}</span>
                        </td>
                        <td>
                            <span v-if="r.rating" class="flex items-center gap-1 text-[var(--color-gold)] font-mono text-sm font-bold">
                                <svg class="w-3.5 h-3.5 star-filled" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" /></svg>
                                {{ r.rating }}
                            </span>
                            <span v-else class="text-[var(--color-text-muted)] text-xs">—</span>
                        </td>
                    </tr>
                    <tr v-if="!recentReviews.length">
                        <td colspan="3" class="text-center text-[var(--color-text-muted)] py-6 text-sm">Chưa có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Recent Users -->
        <div class="card overflow-hidden lg:col-span-2">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[var(--color-border)]">
                <h3 class="font-display font-bold text-sm uppercase tracking-widest text-[var(--color-text-muted)]">Người dùng mới đăng ký</h3>
                <Link :href="route('admin.users.index')" class="text-xs text-[var(--color-accent)] hover:underline">Xem tất cả →</Link>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Username</th><th>Email</th><th>Role</th><th>Ngày đăng ký</th></tr>
                </thead>
                <tbody>
                    <tr v-for="u in recentUsers" :key="u.id">
                        <td class="font-medium text-[var(--color-text-primary)] text-sm">{{ u.username }}</td>
                        <td class="text-[var(--color-text-muted)] text-sm">{{ u.email }}</td>
                        <td>
                            <Badge :variant="u.role === 'ADMIN' ? 'accent' : u.role === 'MODERATOR' ? 'warning' : 'default'">{{ u.role }}</Badge>
                        </td>
                        <td class="text-[var(--color-text-muted)] text-xs font-mono">{{ fmtDate(u.created_at) }}</td>
                    </tr>
                    <tr v-if="!recentUsers.length">
                        <td colspan="4" class="text-center text-[var(--color-text-muted)] py-6 text-sm">Chưa có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
