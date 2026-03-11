<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';

defineOptions({ layout: AppLayout });

const { t } = useI18n();

const props = defineProps({
    collections: { type: Object, required: true }, // LengthAwarePaginator
    q:           { type: String, default: '' },
});

const search  = ref(props.q);
const loading = ref(false);

const doSearch = useDebounceFn((val) => {
    router.get(
        route('collections.public.index'),
        { q: val },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['collections', 'q'],
            onStart:  () => { loading.value = true; },
            onFinish: () => { loading.value = false; },
        },
    );
}, 350);

watch(search, (val) => doSearch(val));

function clearSearch() {
    search.value = '';
}
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="font-display font-black text-2xl text-[var(--color-text-primary)] mb-6">
            📋 {{ t('collections.publicIndexTitle') }}
        </h1>

        <!-- Smart Search bar -->
        <div class="mb-8 max-w-lg">
            <div class="relative">
                <!-- Search icon / spinner -->
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-[var(--color-text-muted)]">
                    <svg v-if="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 6.65 16.65L16.65 16.65z" />
                    </svg>
                    <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                    </svg>
                </span>

                <input
                    v-model="search"
                    type="text"
                    :placeholder="t('collections.searchPlaceholder')"
                    class="w-full pl-9 pr-8 py-2.5 text-sm rounded-xl bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] focus:outline-none focus:border-[var(--color-accent)] focus:ring-1 focus:ring-[var(--color-accent)]/30 transition-all"
                />

                <!-- Clear button -->
                <button
                    v-if="search"
                    type="button"
                    @click="clearSearch"
                    class="absolute inset-y-0 right-2.5 flex items-center text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Search hint -->
            <p class="mt-1.5 text-[11px] text-[var(--color-text-muted)] pl-1">
                {{ t('collections.searchHint') }}
            </p>
        </div>

        <!-- Results count when searching -->
        <p v-if="q" class="text-xs text-[var(--color-text-muted)] mb-4">
            {{ t('collections.searchResultCount', { n: collections.total, q }) }}
        </p>

        <!-- Empty state -->
        <div v-if="collections.data.length === 0" class="text-center py-16 text-[var(--color-text-muted)]">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 6.65 16.65L16.65 16.65z" />
            </svg>
            <p class="text-sm">{{ q ? t('collections.noSearchResults', { q }) : t('collections.noPublicLists') }}</p>
        </div>

        <!-- Grid -->
        <div
            v-else
            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 transition-opacity duration-200"
            :class="{ 'opacity-50 pointer-events-none': loading }"
        >
            <Link
                v-for="c in collections.data"
                :key="c.collection_id"
                :href="route('collections.show', c.slug)"
                class="group block rounded-xl overflow-hidden border border-[var(--color-border)] bg-[var(--color-bg-elevated)] hover:border-[var(--color-accent)]/50 transition-colors"
            >
                <!-- Cover -->
                <div class="aspect-video bg-[var(--color-bg-base)] relative overflow-hidden">
                    <img v-if="c.cover_image_url" :src="c.cover_image_url" :alt="c.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                    <div v-else-if="c.auto_cover_urls && c.auto_cover_urls.length > 1" class="grid grid-cols-2 w-full h-full">
                        <img v-for="(url, i) in c.auto_cover_urls.slice(0, 4)" :key="i" :src="url" class="w-full h-full object-cover" loading="lazy" />
                    </div>
                    <img v-else-if="c.auto_cover_urls && c.auto_cover_urls.length === 1" :src="c.auto_cover_urls[0]" :alt="c.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                    <div v-else class="w-full h-full flex items-center justify-center opacity-20">
                        <svg class="w-10 h-10 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                    </div>
                </div>

                <div class="p-3 space-y-1">
                    <div class="flex items-start justify-between gap-1">
                        <p class="font-semibold text-sm text-[var(--color-text-primary)] truncate group-hover:text-[var(--color-accent)] transition-colors">
                            {{ c.name }}
                        </p>
                        <span class="shrink-0 text-[10px] font-mono text-[var(--color-text-muted)] border border-[var(--color-border)] rounded px-1 py-0.5 leading-none">#{{ c.collection_id }}</span>
                    </div>
                    <p class="text-xs text-[var(--color-text-muted)]">
                        by <span class="text-[var(--color-text-secondary)]">{{ c.owner.name }}</span>
                    </p>
                    <div class="flex items-center gap-3 text-xs text-[var(--color-text-muted)]">
                        <span>{{ t('collections.titlesCount', { n: c.titles_count }) }}</span>
                        <span class="flex items-center gap-1 text-emerald-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.11a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                            </svg>
                            {{ t('collections.nominationCount', { n: c.nomination_count }) }}
                        </span>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Pagination -->
        <div v-if="collections.last_page > 1" class="mt-8 flex justify-center gap-2">
            <Link
                v-for="link in collections.links"
                :key="link.label"
                :href="link.url ?? '#'"
                :class="[
                    'px-3 py-1.5 text-sm rounded-lg border transition-colors',
                    link.active
                        ? 'bg-[var(--color-accent)] text-white border-[var(--color-accent)]'
                        : link.url
                            ? 'border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)]'
                            : 'border-transparent text-[var(--color-text-muted)] cursor-default opacity-50'
                ]"
                v-html="link.label"
            />
        </div>
    </div>
</template>
