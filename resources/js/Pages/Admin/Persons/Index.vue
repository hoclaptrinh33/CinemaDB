<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    persons:   { type: Object, required: true },
    filters:   { type: Object, default: () => ({}) },
    countries: { type: Array,  default: () => [] },
});

const form = reactive({
    search:     props.filters.search     ?? '',
    country_id: props.filters.country_id ?? '',
    gender:     props.filters.gender     ?? '',
});

const confirmId = ref(null);
let debounce = null;

const activeFiltersCount = computed(() =>
    [form.country_id, form.gender].filter(Boolean).length
);

function submit() {
    const params = {};
    Object.entries(form).forEach(([k, v]) => { if (v !== '') params[k] = v; });
    router.get(route('admin.persons.index'), params, { preserveState: true, replace: true });
}

function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(submit, 350);
}

function clearFilters() {
    form.search = '';
    form.country_id = '';
    form.gender = '';
    submit();
}

function destroy(id) {
    router.delete(route('admin.persons.destroy', id), {
        preserveScroll: true,
        onSuccess: () => confirmId.value = null,
    });
}
</script>

<template>
    <div class="space-y-4">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">Persons</h1>
            <Link :href="route('admin.persons.create')">
                <Button variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Thêm Person
                </Button>
            </Link>
        </div>

        <!-- Filter bar -->
        <div class="flex items-center gap-2 flex-wrap">
            <!-- Search -->
            <div class="relative flex-1 min-w-48">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    v-model="form.search"
                    type="search"
                    placeholder="Tìm tên người..."
                    class="input-base pl-9"
                    @input="onSearch"
                />
            </div>

            <!-- Country filter -->
            <div class="w-44 shrink-0">
                <select v-model="form.country_id" class="input-base" @change="submit">
                    <option value="">Tất cả quốc gia</option>
                    <option v-for="c in countries" :key="c.country_id" :value="c.country_id">
                        {{ c.country_name }}
                    </option>
                </select>
            </div>

            <!-- Gender filter -->
            <div class="w-36 shrink-0">
                <select v-model="form.gender" class="input-base" @change="submit">
                    <option value="">Tất cả giới tính</option>
                    <option value="MALE">Nam</option>
                    <option value="FEMALE">Nữ</option>
                    <option value="OTHER">Khác</option>
                </select>
            </div>

            <!-- Clear filters button (shown when filters are active) -->
            <button
                v-if="activeFiltersCount > 0 || form.search"
                type="button"
                class="shrink-0 flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-[var(--color-border)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border-light)] transition-colors"
                @click="clearFilters"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Xoá bộ lọc
                <span v-if="activeFiltersCount > 0" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[var(--color-accent)] text-white text-[9px] font-bold">
                    {{ activeFiltersCount }}
                </span>
            </button>

            <!-- Result count -->
            <p class="text-[var(--color-text-muted)] text-sm ml-auto shrink-0">
                {{ persons.total }} kết quả
            </p>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Họ tên</th>
                        <th>Giới tính</th>
                        <th>Ngày sinh</th>
                        <th>Quốc tịch</th>
                        <th class="text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in persons.data" :key="p.person_id">
                        <td class="w-12">
                            <img :src="p.profile_url" :alt="p.full_name" class="w-9 h-12 object-cover rounded-lg object-top" />
                        </td>
                        <td>
                            <p class="font-semibold text-[var(--color-text-primary)] text-sm">{{ p.full_name }}</p>
                        </td>
                        <td>
                            <span v-if="p.gender" class="text-xs text-[var(--color-text-muted)]">{{ p.gender }}</span>
                            <span v-else class="text-[var(--color-text-muted)]">—</span>
                        </td>
                        <td class="font-mono text-xs text-[var(--color-text-muted)]">{{ p.birth_date ?? '—' }}</td>
                        <td class="text-sm text-[var(--color-text-muted)]">{{ p.country?.country_name ?? '—' }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="route('admin.persons.edit', p.person_id)">
                                    <Button variant="ghost" size="sm">Sửa</Button>
                                </Link>
                                <Button variant="danger" size="sm" @click="confirmId = p.person_id">Xoá</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!persons.data.length">
                        <td colspan="6" class="text-center text-[var(--color-text-muted)] py-12 text-sm">
                            Không có dữ liệu
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="persons.last_page > 1" :meta="persons" />
    </div>

    <ConfirmModal
        :show="!!confirmId"
        title="Xoá person?"
        message="Thao tác này sẽ xoá toàn bộ vai trò liên quan đến người này."
        confirm-label="Xoá"
        @confirm="destroy(confirmId)"
        @cancel="confirmId = null"
    />
</template>

