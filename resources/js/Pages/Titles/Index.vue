<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import TitleFilterBar from '@/Components/Title/TitleFilterBar.vue';
import TitleGrid from '@/Components/Title/TitleGrid.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Spinner from '@/Components/UI/Spinner.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

defineProps({
    titles:    { type: Object, required: true },
    filters:   { type: Object, required: true },
    languages: { type: Array, default: () => [] },
    genres:    { type: Array, default: () => [] },
    years:     { type: Array, default: () => [] },
});

const loading = ref(false);
router.on('start', () => loading.value = true);
router.on('finish', () => loading.value = false);
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <!-- Page header -->
        <div>
            <h1 class="font-display font-black text-3xl text-[var(--color-text-primary)]">{{ $t('titles.heading') }}</h1>
            <p class="text-[var(--color-text-muted)] text-sm mt-1">
                {{ titles.total.toLocaleString() }} {{ $t('titles.results', { n: '' }).replace(' {n}', '') }}
            </p>
        </div>

        <!-- Filter bar -->
        <TitleFilterBar
            :filters="filters"
            :languages="languages"
            :genres="genres"
            :years="years"
        />

        <!-- Grid or loading -->
        <div v-if="loading" class="flex justify-center py-20">
            <Spinner size="lg" />
        </div>

        <template v-else>
            <!-- Empty -->
            <div v-if="!titles.data.length" class="card p-8 sm:p-16 flex flex-col items-center gap-3 text-center">
                <svg class="w-12 h-12 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <p class="text-[var(--color-text-muted)]">{{ $t('titles.noResults') }}</p>
            </div>

            <TitleGrid v-else :titles="titles.data" />

            <!-- Pagination -->
            <Pagination v-if="titles.last_page > 1" :meta="titles" />
        </template>
    </div>
</template>
