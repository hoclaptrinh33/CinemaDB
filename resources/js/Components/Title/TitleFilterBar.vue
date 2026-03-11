<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    filters:   { type: Object, required: true }, // current active filters
    languages: { type: Array, default: () => [] },
    genres:    { type: Array, default: () => [] },
    years:     { type: Array, default: () => [] },
});

const form = reactive({
    search:      props.filters.search      ?? '',
    type:        props.filters.type        ?? '',
    year:        props.filters.year        ?? '',
    language_id: props.filters.language_id ?? '',
    genre_id:    props.filters.genre_id    ?? '',
    sort:        props.filters.sort        ?? 'latest',
});

const typeLabel = (val) => types.find(t => t.value === val)?.labelVi ?? val;

let debounceTimer = null;
watch(() => form.search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(submit, 400);
});

function submit() {
    const params = {};
    Object.entries(form).forEach(([k, v]) => { if (v !== '' && v !== null) params[k] = v; });
    router.get(route('titles.index'), params, { preserveState: true, replace: true });
}

function reset() {
    Object.assign(form, { search: '', type: '', year: '', language_id: '', genre_id: '', sort: 'latest' });
    submit();
}

const types = [
    { value: '',       labelVi: 'Tất cả',        labelEn: 'All types' },
    { value: 'MOVIE',  labelVi: 'Phim điện ảnh', labelEn: 'Movie' },
    { value: 'SERIES', labelVi: 'Phim bộ',       labelEn: 'TV Series' },
];
const sorts = [
    { value: 'latest', label: 'Mới nhất' },
    { value: 'rating', label: 'Đánh giá cao' },
    { value: 'title',  label: 'Tên A–Z' },
];
</script>

<template>
    <div class="card p-4 space-y-3">
        <!-- Row 1: Search + Sort -->
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    v-model="form.search"
                    type="search"
                    placeholder="Tìm tên phim, series..."
                    class="input-base pl-9"
                />
            </div>

            <!-- Sort -->
            <select v-model="form.sort" class="input-base w-full sm:w-44" @change="submit">
                <option v-for="s in sorts" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
        </div>

        <!-- Row 2: Type / Year / Language / Genre -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <!-- Type -->
            <select v-model="form.type" class="input-base" @change="submit">
                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.labelVi }} / {{ t.labelEn }}</option>
            </select>

            <!-- Year -->
            <select v-model="form.year" class="input-base" @change="submit">
                <option value="">Tất cả năm</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>

            <!-- Language -->
            <select v-model="form.language_id" class="input-base" @change="submit">
                <option value="">Tất cả ngôn ngữ / All languages</option>
                <option v-for="l in languages" :key="l.language_id" :value="l.language_id">{{ l.language_name }}</option>
            </select>

            <!-- Genre -->
            <select v-model="form.genre_id" class="input-base" @change="submit">
                <option value="">Tất cả thể loại / All genres</option>
                <option v-for="g in genres" :key="g.genre_id" :value="g.genre_id">
                    {{ g.genre_name_vi ?? g.genre_name }} / {{ g.genre_name }}
                </option>
            </select>
        </div>

        <!-- Active filters chips + reset -->
        <div
            v-if="filters.search || filters.type || filters.year || filters.language_id || filters.genre_id"
            class="flex items-center gap-2 flex-wrap"
        >
            <span class="text-xs text-[var(--color-text-muted)]">Đang lọc:</span>
            <span v-if="filters.search"      class="badge !bg-[var(--color-accent-muted)] !text-[var(--color-accent)]">{{ filters.search }}</span>
            <span v-if="filters.type"        class="badge !bg-[var(--color-bg-elevated)] !text-[var(--color-text-secondary)]">{{ typeLabel(filters.type) }}</span>
            <span v-if="filters.year"        class="badge !bg-[var(--color-bg-elevated)] !text-[var(--color-text-secondary)]">{{ filters.year }}</span>
            <button class="text-xs text-[var(--color-text-muted)] hover:text-[var(--color-accent)] transition-colors ml-auto" @click="reset">
                Xoá bộ lọc ×
            </button>
        </div>
    </div>
</template>
