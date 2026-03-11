<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    languages: { type: Object, required: true },
    filters:   { type: Object, default: () => ({}) },
});

const search    = ref(props.filters.search ?? '');
const confirmId = ref(null);
let debounce    = null;

function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('admin.languages.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 350);
}

function destroy(id) {
    router.delete(route('admin.languages.destroy', id), {
        preserveScroll: true,
        onSuccess: () => confirmId.value = null,
    });
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">Ngôn ngữ</h1>
            <Link :href="route('admin.languages.create')">
                <Button variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Thêm ngôn ngữ
                </Button>
            </Link>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative flex-1 min-w-48">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input v-model="search" @input="onSearch" type="search" placeholder="Tìm ngôn ngữ..." class="input-base pl-9" />
            </div>
            <p class="text-[var(--color-text-muted)] text-sm ml-auto shrink-0">{{ languages.total }} kết quả</p>
        </div>

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ISO Code</th>
                        <th>Tên ngôn ngữ</th>
                        <th>Số phim</th>
                        <th class="text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lang in languages.data" :key="lang.language_id">
                        <td class="w-24">
                            <span class="inline-block bg-[var(--color-bg-elevated)] text-[var(--color-text-secondary)] text-xs font-mono font-bold px-2 py-0.5 rounded">{{ lang.iso_code }}</span>
                        </td>
                        <td class="font-semibold text-[var(--color-text-primary)] text-sm">{{ lang.language_name }}</td>
                        <td class="text-sm text-[var(--color-text-muted)]">{{ lang.titles_count ?? 0 }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="route('admin.languages.edit', lang.language_id)">
                                    <Button variant="ghost" size="sm">Sửa</Button>
                                </Link>
                                <Button variant="danger" size="sm" @click="confirmId = lang.language_id">Xoá</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!languages.data.length">
                        <td colspan="4" class="text-center text-[var(--color-text-muted)] py-12 text-sm">Không có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="languages.last_page > 1" :meta="languages" />
    </div>

    <ConfirmModal
        :show="!!confirmId"
        title="Xoá ngôn ngữ?"
        message="Hành động này không thể hoàn tác."
        confirm-label="Xoá"
        @confirm="destroy(confirmId)"
        @cancel="confirmId = null"
    />
</template>
