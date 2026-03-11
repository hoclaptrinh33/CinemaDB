<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    collections: {
        type: Array,
        default: () => [],
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="card p-5 space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-[var(--color-text-muted)]">
                {{ t('collections.savedLists') }}
            </h3>
            <a v-if="isOwner" :href="route('collections.create')" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                + {{ t('collections.createNew') }}
            </a>
        </div>

        <div v-if="collections.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            <a
                v-for="col in collections"
                :key="col.collection_id"
                :href="route('collections.show', col.slug)"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-[var(--color-bg-subtle)] border border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-bg-elevated)] transition-all group"
            >
                <!-- Icon -->
                <div class="w-8 h-8 rounded-md flex-shrink-0 flex items-center justify-center text-base"
                     :class="col.visibility === 'PRIVATE' ? 'bg-zinc-700/50' : 'bg-indigo-500/15'">
                    {{ col.visibility === 'PRIVATE' ? '🔒' : '🎞️' }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <p class="text-sm font-medium text-[var(--color-text-primary)] truncate group-hover:text-white transition-colors">
                            {{ col.name }}
                        </p>
                        <span v-if="col.role === 'collaborator'" class="shrink-0 text-[10px] px-1.5 py-0.5 rounded bg-teal-500/15 text-teal-400 font-medium">
                            {{ t('collections.roleCollaborator') }}
                        </span>
                    </div>
                    <p class="text-xs text-[var(--color-text-muted)]">
                        <span v-if="col.role === 'collaborator' && col.owner">{{ col.owner.name }} · </span>
                        {{ t('collections.titlesCount', { n: col.titles_count || 0 }) }}
                    </p>
                </div>
                <svg class="w-4 h-4 text-zinc-600 group-hover:text-zinc-400 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div v-else class="py-8 text-center space-y-2">
            <span class="text-3xl">📂</span>
            <p class="text-sm text-[var(--color-text-muted)]">{{ t('collections.empty') }}</p>
            <a v-if="isOwner" :href="route('collections.create')" class="inline-block text-xs text-indigo-400 underline underline-offset-2 hover:text-indigo-300 transition-colors">
                {{ t('collections.createFirst') }}
            </a>
        </div>
    </div>
</template>
