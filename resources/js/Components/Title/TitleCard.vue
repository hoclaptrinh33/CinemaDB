<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Badge from '@/Components/UI/Badge.vue';
import { useI18n } from 'vue-i18n';
import { useLocaleContent } from '@/composables/useLocaleContent.js';

const props = defineProps({
    title: { type: Object, required: true },
});

const { t } = useI18n();
const titleRef = computed(() => props.title);
const { name: localeName } = useLocaleContent(titleRef);

const typeVariant = {
    MOVIE:   'movie',
    SERIES:  'series',
    EPISODE: 'episode',
};
const typeLabel = computed(() => ({
    MOVIE:   t('titles.movie'),
    SERIES:  t('titles.series'),
    EPISODE: t('titles.episode'),
}));

const titleHref = computed(() => {
    try { return route('titles.show', props.title.slug ?? props.title.title_id); }
    catch { return '#'; }
});
</script>

<template>
    <Link
        :href="titleHref"
        class="group block relative aspect-poster rounded-xl overflow-hidden bg-[var(--color-bg-surface)] border border-[var(--color-border)] transition-all duration-300 hover:border-[var(--color-accent)] hover:-translate-y-1 hover:shadow-[0_16px_40px_rgba(0,0,0,0.6)]"
    >
        <!-- Poster image -->
        <img
            :src="title.poster_url"
            :alt="localeName"
            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 bg-gray-900"
            loading="lazy"
        />

        <!-- Gradient overlay (always) -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent" />

        <!-- Type badge -->
        <div class="absolute top-2 left-2">
            <Badge :variant="typeVariant[title.title_type] ?? 'default'">
                {{ typeLabel[title.title_type] ?? title.title_type }}
            </Badge>
        </div>

        <!-- Rating chip -->
        <div
            v-if="title.avg_rating"
            class="absolute top-2 right-2 flex items-center gap-1 bg-black/70 backdrop-blur-sm rounded-md px-2 py-0.5"
        >
            <svg class="w-3 h-3 star-filled shrink-0" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" />
            </svg>
            <span class="text-[var(--color-gold)] font-mono text-xs font-bold leading-none">
                {{ Number(title.avg_rating).toFixed(1) }}
            </span>
        </div>

        <!-- Bottom info -->
        <div class="absolute bottom-0 left-0 right-0 p-3 translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
            <p class="font-display font-bold text-sm text-white line-clamp-2 leading-tight">
                {{ localeName }}
            </p>
            <p v-if="title.release_date" class="text-[var(--color-text-muted)] text-xs mt-0.5 font-mono">
                {{ title.release_date.slice(0, 4) }}
            </p>
        </div>
    </Link>
</template>
