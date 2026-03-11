<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    titles:    { type: Object, required: true },
    filters:   { type: Object, default: () => ({}) },
    languages: { type: Array,  default: () => [] },
    years:     { type: Array,  default: () => [] },
});

const form = reactive({
    search:      props.filters.search      ?? '',
    type:        props.filters.type        ?? '',
    year:        props.filters.year        ?? '',
    language_id: props.filters.language_id ?? '',
});
const confirmSlug = ref(null);
let debounce      = null;

const activeFiltersCount = computed(() =>
    [form.type, form.year, form.language_id].filter(Boolean).length
);

function submit() {
    const params = {};
    Object.entries(form).forEach(([k, v]) => { if (v !== '') params[k] = v; });
    router.get(route('admin.titles.index'), params, { preserveState: true, replace: true });
}

function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(submit, 350);
}

function clearFilters() {
    form.search      = '';
    form.type        = '';
    form.year        = '';
    form.language_id = '';
    submit();
}

function destroy(slug) {
    router.delete(route('admin.titles.destroy', slug), {
        preserveScroll: true,
        onSuccess: () => confirmSlug.value = null,
    });
}

const typeVariant = { MOVIE: 'movie', SERIES: 'series', EPISODE: 'episode' };
</script>

<template>
    <div class="space-y-4">
    <!-- Page header -->
    <div class="flex items-center justify-between">
        <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">Titles</h1>
        <Link :href="route('admin.titles.create')">
            <Button variant="primary" size="sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Thêm Title
            </Button>
        </Link>
    </div>
        <!-- Search / Filters -->
        <div class="flex items-center gap-2 flex-wrap">
            <div class="relative flex-1 min-w-48">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input v-model="form.search" @input="onSearch" type="search" placeholder="Tìm title..." class="input-base pl-9" />
            </div>
            <div class="w-36 shrink-0">
                <select
                    v-model="form.type"
                    class="input-base"
                    :class="{ 'border-[var(--color-accent)] !text-[var(--color-accent)]': form.type }"
                    @change="submit"
                >
                    <option value="">Tất cả loại</option>
                    <option value="MOVIE">Phim lẻ</option>
                    <option value="SERIES">Series</option>
                    <option value="EPISODE">Tập phim</option>
                </select>
            </div>
            <div class="w-28 shrink-0">
                <select
                    v-model="form.year"
                    class="input-base"
                    :class="{ 'border-[var(--color-accent)] !text-[var(--color-accent)]': form.year }"
                    @change="submit"
                >
                    <option value="">Tất cả năm</option>
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <div class="w-36 shrink-0">
                <select
                    v-model="form.language_id"
                    class="input-base"
                    :class="{ 'border-[var(--color-accent)] !text-[var(--color-accent)]': form.language_id }"
                    @change="submit"
                >
                    <option value="">Tất cả ngôn ngữ</option>
                    <option v-for="l in languages" :key="l.language_id" :value="l.language_id">{{ l.language_name }}</option>
                </select>
            </div>

            <!-- Clear filters -->
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

            <p class="text-[var(--color-text-muted)] text-sm ml-auto shrink-0">{{ titles.total }} kết quả</p>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Tên</th>
                        <th>Loại</th>
                        <th>Năm</th>
                        <th>Rating</th>
                        <th>Visibility</th>
                        <th class="text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in titles.data" :key="t.title_id">
                        <td class="w-12">
                            <img :src="t.poster_url" :alt="t.title_name" class="w-9 h-12 object-cover rounded-lg" v-if="t.poster_url" />
                            <div v-else class="w-9 h-12 rounded-lg bg-[var(--color-bg-elevated)]" />
                        </td>
                        <td>
                            <Link :href="route('titles.show', t.slug)" target="_blank" class="font-semibold text-[var(--color-text-primary)] hover:text-[var(--color-accent)] transition-colors text-sm">
                                {{ t.title_name }}
                            </Link>
                            <p v-if="t.original_title" class="text-xs text-[var(--color-text-muted)] mt-0.5 italic">{{ t.original_title }}</p>
                        </td>
                        <td>
                            <Badge :variant="typeVariant[t.title_type] ?? 'default'">{{ t.title_type }}</Badge>
                        </td>
                        <td class="font-mono text-sm text-[var(--color-text-muted)]">{{ t.release_date?.slice(0,4) ?? '—' }}</td>
                        <td>
                            <span v-if="t.avg_rating" class="flex items-center gap-1 text-[var(--color-gold)] font-mono font-bold text-sm">
                                <svg class="w-3.5 h-3.5 star-filled" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" /></svg>
                                {{ Number(t.avg_rating).toFixed(1) }}
                            </span>
                            <span v-else class="text-[var(--color-text-muted)] text-sm">—</span>
                        </td>
                        <td>
                            <Badge :variant="t.visibility === 'PUBLIC' ? 'success' : 'danger'">{{ t.visibility }}</Badge>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="route('admin.titles.edit', t.slug)">
                                    <Button variant="ghost" size="sm">Sửa</Button>
                                </Link>
                                <Button variant="danger" size="sm" @click="confirmSlug = t.slug">Xoá</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!titles.data.length">
                        <td colspan="7" class="text-center text-[var(--color-text-muted)] py-12 text-sm">Không có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="titles.last_page > 1" :meta="titles" />
    </div>

    <ConfirmModal
        :show="!!confirmSlug"
        title="Xoá title?"
        message="Hành động này không thể hoàn tác. Tất cả dữ liệu liên quan (reviews, cast...) sẽ bị xoá."
        confirm-label="Xoá"
        @confirm="destroy(confirmSlug)"
        @cancel="confirmSlug = null"
    />
</template>
