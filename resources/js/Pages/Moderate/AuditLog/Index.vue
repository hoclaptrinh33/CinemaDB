<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');
const actionFilter = ref(props.filters.action ?? '');
let debounce = null;

function query() {
    router.get(route('moderate.audit-logs.index'), {
        search: search.value,
        action: actionFilter.value,
    }, { preserveState: true, replace: true });
}

function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(query, 350);
}

function fmtDate(d) {
    return new Date(d).toLocaleString('vi-VN', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

const actionVariant = {
    INSERT: 'success',
    UPDATE: 'info',
    DELETE: 'danger',
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">Moderate Audit Log</h1>
        </div>

        <div class="flex flex-wrap gap-3">
            <div class="relative flex-1 max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input v-model="search" @input="onSearch" type="search" placeholder="Tìm title / người thực hiện..." class="input-base pl-9" />
            </div>
            <select v-model="actionFilter" class="input-base w-36" @change="query">
                <option value="">Tất cả hành động</option>
                <option value="INSERT">INSERT</option>
                <option value="UPDATE">UPDATE</option>
                <option value="DELETE">DELETE</option>
            </select>
            <p class="text-[var(--color-text-muted)] text-sm self-center">{{ logs.total }} bản ghi</p>
        </div>

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr><th>Thời gian</th><th>Người thực hiện</th><th>Hành động</th><th>Title</th><th>Thay đổi</th></tr>
                </thead>
                <tbody>
                    <tr v-for="log in logs.data" :key="log.id">
                        <td class="font-mono text-xs text-[var(--color-text-muted)] whitespace-nowrap">{{ fmtDate(log.changed_at) }}</td>
                        <td class="text-sm text-[var(--color-text-primary)] font-medium">{{ log.changed_by ?? '—' }}</td>
                        <td><Badge :variant="actionVariant[log.action] ?? 'default'">{{ log.action }}</Badge></td>
                        <td class="text-sm text-[var(--color-text-muted)] max-w-[180px]"><span class="truncate block">{{ log.title_name ?? `#${log.title_id}` }}</span></td>
                        <td class="max-w-xs">
                            <details v-if="log.changes" class="cursor-pointer">
                                <summary class="text-xs text-[var(--color-accent)] hover:underline select-none">Xem chi tiết</summary>
                                <pre class="text-xs text-[var(--color-text-muted)] mt-2 bg-[var(--color-bg-base)] rounded-lg p-2 overflow-auto max-h-32 font-mono">{{ JSON.stringify(log.changes, null, 2) }}</pre>
                            </details>
                            <span v-else class="text-[var(--color-text-muted)] text-xs">—</span>
                        </td>
                    </tr>
                    <tr v-if="!logs.data.length">
                        <td colspan="5" class="text-center text-[var(--color-text-muted)] py-12 text-sm">Không có bản ghi</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="logs.last_page > 1" :meta="logs" />
    </div>
</template>
