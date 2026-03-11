<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const { t } = useI18n();

const props = defineProps({
    owner:  { type: Object, required: true },
    owned:  { type: Array,  default: () => [] },
    shared: { type: Array,  default: () => [] },
});

const { auth } = usePage().props;
const isOwner = auth.user?.id === props.owner.id;
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="font-display font-black text-2xl text-[var(--color-text-primary)]">
                    {{ t('collections.collectionsBy') }}
                    <span class="text-[var(--color-accent)]">{{ owner.name }}</span>
                </h1>
                <p class="text-[var(--color-text-muted)] text-sm mt-1">{{ t('collections.count', { n: owned.length }) }}</p>
            </div>
            <a
                v-if="isOwner"
                :href="route('collections.create')"
                class="flex items-center gap-2 px-4 py-2 rounded-lg bg-[var(--color-accent)] text-white text-sm font-semibold hover:opacity-90 transition-opacity"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ t('collections.createNew') }}
            </a>
        </div>

        <!-- Empty state (no owned collections) -->
        <div v-if="owned.length === 0" class="text-center py-16 text-[var(--color-text-muted)]">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
            </svg>
            <p class="text-sm">{{ t('collections.empty') }}</p>
        </div>

        <!-- Owned collections grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <a
                v-for="col in owned"
                :key="col.collection_id"
                :href="route('collections.show', col.slug)"
                class="group block rounded-xl overflow-hidden border border-[var(--color-border)] bg-[var(--color-bg-elevated)] hover:border-[var(--color-accent)]/50 transition-colors"
            >
                <div class="aspect-video bg-[var(--color-bg-surface)] overflow-hidden">
                    <div v-if="!col.cover_image_url && col.auto_cover_urls && col.auto_cover_urls.length > 1" class="grid grid-cols-2 w-full h-full">
                        <img v-for="(url, i) in col.auto_cover_urls.slice(0, 4)" :key="i" :src="url" class="w-full h-full object-cover" loading="lazy" />
                    </div>
                    <img v-else-if="!col.cover_image_url && col.auto_cover_urls && col.auto_cover_urls.length === 1" :src="col.auto_cover_urls[0]" :alt="col.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                    <img v-else :src="col.cover_url" :alt="col.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-[var(--color-text-primary)] leading-tight truncate">{{ col.name }}</h3>
                        <span v-if="col.visibility === 'PRIVATE'" class="shrink-0 text-xs text-[var(--color-text-muted)]">🔒</span>
                    </div>
                    <p class="text-xs text-[var(--color-text-muted)] mt-1">{{ t('collections.titlesCount', { n: col.titles_count }) }}</p>
                </div>
            </a>
        </div>

        <!-- Shared (collaborated) collections -->
        <template v-if="shared.length > 0">
            <div class="mt-12 mb-5 flex items-center gap-3">
                <h2 class="font-display font-bold text-lg text-[var(--color-text-primary)]">{{ t('collections.sharedLists') }}</h2>
                <span class="text-xs px-2 py-0.5 rounded-full bg-[var(--color-bg-elevated)] text-[var(--color-text-muted)] border border-[var(--color-border)]">{{ shared.length }}</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <a
                    v-for="col in shared"
                    :key="col.collection_id"
                    :href="route('collections.show', col.slug)"
                    class="group block rounded-xl overflow-hidden border border-[var(--color-border)] bg-[var(--color-bg-elevated)] hover:border-[var(--color-accent)]/50 transition-colors"
                >
                    <div class="aspect-video bg-[var(--color-bg-surface)] overflow-hidden">
                        <div v-if="!col.cover_image_url && col.auto_cover_urls && col.auto_cover_urls.length > 1" class="grid grid-cols-2 w-full h-full">
                            <img v-for="(url, i) in col.auto_cover_urls.slice(0, 4)" :key="i" :src="url" class="w-full h-full object-cover" loading="lazy" />
                        </div>
                        <img v-else-if="!col.cover_image_url && col.auto_cover_urls && col.auto_cover_urls.length === 1" :src="col.auto_cover_urls[0]" :alt="col.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                        <img v-else :src="col.cover_url" :alt="col.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                    </div>
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-semibold text-[var(--color-text-primary)] leading-tight truncate">{{ col.name }}</h3>
                            <span class="shrink-0 text-xs px-1.5 py-0.5 rounded bg-[var(--color-accent)]/15 text-[var(--color-accent)] font-medium">{{ t('collections.roleCollaborator') }}</span>
                        </div>
                        <p class="text-xs text-[var(--color-text-muted)] mt-1">{{ t('collections.titlesCount', { n: col.titles_count }) }}</p>
                    </div>
                </a>
            </div>
        </template>
    </div>
</template>
