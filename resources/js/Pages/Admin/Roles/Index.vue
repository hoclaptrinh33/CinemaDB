<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    roles:   { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search    = ref(props.filters.search ?? '');
const confirmId = ref(null);
let debounce    = null;

function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('admin.roles.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 350);
}

function destroy(id) {
    router.delete(route('admin.roles.destroy', id), {
        preserveScroll: true,
        onSuccess: () => confirmId.value = null,
    });
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">Vai trò phim</h1>
            <Link :href="route('admin.roles.create')">
                <Button variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Thêm vai trò
                </Button>
            </Link>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative flex-1 min-w-48">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input v-model="search" @input="onSearch" type="search" placeholder="Tìm vai trò..." class="input-base pl-9" />
            </div>
            <p class="text-[var(--color-text-muted)] text-sm ml-auto shrink-0">{{ roles.total }} kết quả</p>
        </div>

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tên vai trò</th>
                        <th>Số người dùng</th>
                        <th class="text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in roles.data" :key="r.role_id">
                        <td class="font-semibold text-[var(--color-text-primary)] text-sm">{{ r.role_name }}</td>
                        <td class="text-sm text-[var(--color-text-muted)]">{{ r.persons_count ?? 0 }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="route('admin.roles.edit', r.role_id)">
                                    <Button variant="ghost" size="sm">Sửa</Button>
                                </Link>
                                <Button variant="danger" size="sm" @click="confirmId = r.role_id">Xoá</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!roles.data.length">
                        <td colspan="3" class="text-center text-[var(--color-text-muted)] py-12 text-sm">Không có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="roles.last_page > 1" :meta="roles" />
    </div>

    <ConfirmModal
        :show="!!confirmId"
        title="Xoá vai trò?"
        message="Hành động này không thể hoàn tác. Dữ liệu liên quan sẽ bị ảnh hưởng."
        confirm-label="Xoá"
        @confirm="destroy(confirmId)"
        @cancel="confirmId = null"
    />
</template>
