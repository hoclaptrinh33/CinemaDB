<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    reviews: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search    = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const confirmId = ref(null);
let debounce    = null;

function query() {
    router.get(route('admin.reviews.index'), {
        search: search.value,
        status: statusFilter.value,
    }, { preserveState: true, replace: true });
}

function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(query, 350);
}

function destroy(id) {
    router.delete(route('admin.reviews.destroy', id), { preserveScroll: true, onSuccess: () => confirmId.value = null });
}

function updateStatus(id, status) {
    router.patch(route('admin.reviews.status', id), { status }, { preserveScroll: true });
}

function fmtDate(d) { return new Date(d).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }); }
</script>

<template>
    <div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">Reviews</h1>
    </div>
        <div class="flex items-center gap-2">
            <div class="relative flex-1 min-w-48">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input v-model="search" @input="onSearch" type="search" placeholder="Tìm username / tên phim..." class="input-base pl-9" />
            </div>
            <div class="w-36 shrink-0">
                <select v-model="statusFilter" class="input-base" @change="query">
                    <option value="">Tất cả</option>
                    <option value="VISIBLE">Visible</option>
                    <option value="HIDDEN">Hidden</option>
                </select>
            </div>
            <p class="text-[var(--color-text-muted)] text-sm ml-auto shrink-0">{{ reviews.total }} reviews</p>
        </div>

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr><th>Người dùng</th><th>Phim</th><th>Điểm</th><th>Spoiler</th><th>Trạng thái</th><th>Ngày</th><th class="text-right">Hành động</th></tr>
                </thead>
                <tbody>
                    <tr v-for="r in reviews.data" :key="r.review_id">
                        <td class="font-semibold text-[var(--color-text-primary)] text-sm">{{ r.user?.username ?? '—' }}</td>
                        <td class="max-w-[180px]">
                            <span class="text-sm text-[var(--color-text-muted)] truncate block">{{ r.title?.title_name ?? '—' }}</span>
                        </td>
                        <td>
                            <span v-if="r.rating" class="flex items-center gap-1 text-[var(--color-gold)] font-mono font-bold text-sm">
                                <svg class="w-3.5 h-3.5 star-filled" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" /></svg>
                                {{ r.rating }}
                            </span>
                            <span v-else class="text-[var(--color-text-muted)] text-sm">—</span>
                        </td>
                        <td>
                            <Badge v-if="r.has_spoilers" variant="warning">Spoiler</Badge>
                            <span v-else class="text-[var(--color-text-muted)] text-xs">—</span>
                        </td>
                        <td>
                            <Badge :variant="r.moderation_status === 'VISIBLE' ? 'success' : 'danger'">{{ r.moderation_status }}</Badge>
                        </td>
                        <td class="font-mono text-xs text-[var(--color-text-muted)]">{{ fmtDate(r.created_at) }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    v-if="r.moderation_status === 'VISIBLE'"
                                    variant="secondary"
                                    size="sm"
                                    @click="updateStatus(r.review_id, 'HIDDEN')"
                                >Ẩn</Button>
                                <Button
                                    v-else
                                    variant="ghost"
                                    size="sm"
                                    @click="updateStatus(r.review_id, 'VISIBLE')"
                                >Hiện</Button>
                                <Button variant="danger" size="sm" @click="confirmId = r.review_id">Xoá</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!reviews.data.length">
                        <td colspan="7" class="text-center text-[var(--color-text-muted)] py-12 text-sm">Không có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="reviews.last_page > 1" :meta="reviews" />
    </div>

    <ConfirmModal :show="!!confirmId" title="Xoá review?" message="Review này sẽ bị xoá vĩnh viễn." confirm-label="Xoá" @confirm="destroy(confirmId)" @cancel="confirmId = null" />
</template>
