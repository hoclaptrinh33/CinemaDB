<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TitleHero from '@/Components/Title/TitleHero.vue';
import TitleGrid from '@/Components/Title/TitleGrid.vue';

defineOptions({ layout: AppLayout });

defineProps({
    featured:      { type: Array,  default: () => [] },
    latest:        { type: Array,  default: () => [] },
    topRated:      { type: Array,  default: () => [] },
    genres:        { type: Array,  default: () => [] },
    featuredLists: { type: Array,  default: () => [] },
});
</script>

<template>
    <!-- Hero -->
    <TitleHero v-if="featured.length" :titles="featured" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 space-y-14 py-10">
        <!-- Featured Lists -->
        <section v-if="featuredLists.length">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-display font-bold text-xl text-[var(--color-text-primary)] flex items-center gap-2">
                    <span>📋</span> {{ $t('home.featuredLists') }}
                </h2>
                <Link :href="route('collections.public.index')" class="text-sm text-[var(--color-text-muted)] hover:text-[var(--color-accent)] transition-colors">
                    {{ $t('home.viewAllLists') }}
                </Link>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                <Link
                    v-for="list in featuredLists"
                    :key="list.collection_id"
                    :href="route('collections.show', list.slug)"
                    class="group block rounded-xl overflow-hidden border border-[var(--color-border)] bg-[var(--color-bg-elevated)] hover:border-[var(--color-accent)]/50 transition-colors"
                >
                    <div class="aspect-video bg-[var(--color-bg-base)] relative overflow-hidden">
                        <img v-if="list.cover_image_url" :src="list.cover_image_url" :alt="list.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        <div v-else-if="list.auto_cover_urls && list.auto_cover_urls.length > 1" class="grid grid-cols-2 w-full h-full">
                            <img v-for="(url, i) in list.auto_cover_urls.slice(0, 4)" :key="i" :src="url" class="w-full h-full object-cover" loading="lazy" />
                        </div>
                        <img v-else-if="list.auto_cover_urls && list.auto_cover_urls.length === 1" :src="list.auto_cover_urls[0]" :alt="list.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        <div v-else class="w-full h-full flex items-center justify-center opacity-20">
                            <svg class="w-10 h-10 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="p-3 space-y-0.5">
                        <p class="font-semibold text-sm text-[var(--color-text-primary)] truncate group-hover:text-[var(--color-accent)] transition-colors">{{ list.name }}</p>
                        <p class="text-xs text-[var(--color-text-muted)]">by {{ list.owner.name }}</p>
                        <div class="flex items-center gap-3 text-xs text-[var(--color-text-muted)]">
                            <span>{{ $t('collections.titlesCount', { n: list.titles_count }) }}</span>
                            <span class="text-emerald-400">★ {{ list.nomination_count }}</span>
                        </div>
                    </div>
                </Link>
            </div>
        </section>

        <!-- Latest -->
        <section>
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-display font-bold text-xl text-[var(--color-text-primary)] flex items-center gap-2">
                    <span class="text-[var(--color-accent)]">🔥</span> {{ $t('home.latest') }}
                </h2>
                <Link :href="route('titles.index', { sort: 'latest' })" class="text-sm text-[var(--color-text-muted)] hover:text-[var(--color-accent)] transition-colors">
                    {{ $t('home.viewAll') }}
                </Link>
            </div>
            <TitleGrid :titles="latest" />
        </section>

        <!-- Top rated -->
        <section>
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-display font-bold text-xl text-[var(--color-text-primary)] flex items-center gap-2">
                    <svg class="w-5 h-5 star-filled" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" /></svg>
                    {{ $t('home.topRated') }}
                </h2>
                <Link :href="route('titles.index', { sort: 'rating' })" class="text-sm text-[var(--color-text-muted)] hover:text-[var(--color-accent)] transition-colors">
                    {{ $t('home.viewAll') }}
                </Link>
            </div>
            <TitleGrid :titles="topRated" />
        </section>

        <!-- Genre quick filter -->
        <section v-if="genres.length">
            <h2 class="font-display font-bold text-xl text-[var(--color-text-primary)] mb-5">
                {{ $t('home.exploreGenre') }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="genre in genres"
                    :key="genre.id"
                    :href="route('titles.index', { genre_id: genre.id })"
                    class="badge genre text-sm px-4 py-2"
                >
                    {{ genre.genre_name }}
                </Link>
            </div>
        </section>
    </div>
</template>

