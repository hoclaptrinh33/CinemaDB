<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import TitleGrid from '@/Components/Title/TitleGrid.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Badge from '@/Components/UI/Badge.vue';

defineOptions({ layout: AppLayout });

defineProps({
    studio: { type: Object, required: true },
    titles: { type: Object, required: true },
});
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 space-y-10">
        <!-- Studio header -->
        <div class="flex items-start gap-6">
            <!-- Logo -->
            <div class="w-20 h-20 rounded-2xl overflow-hidden bg-[var(--color-bg-elevated)] border border-[var(--color-border)] flex items-center justify-center shrink-0 p-2">
                <img v-if="studio.logo_url" :src="studio.logo_url" :alt="studio.studio_name" class="w-full h-full object-contain" />
                <span v-else class="font-display font-black text-2xl text-[var(--color-text-muted)]">{{ studio.studio_name[0] }}</span>
            </div>

            <!-- Info -->
            <div class="space-y-2">
                <h1 class="font-display font-black text-4xl text-[var(--color-text-primary)]">{{ studio.studio_name }}</h1>
                <div class="flex flex-wrap items-center gap-3 text-sm text-[var(--color-text-muted)]">
                    <span v-if="studio.country">{{ studio.country }}</span>
                    <a v-if="studio.website" :href="studio.website" target="_blank" rel="noopener" class="text-[var(--color-accent)] hover:underline flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                        Website
                    </a>
                    <Badge variant="default">{{ titles.total }} tác phẩm</Badge>
                </div>
                <p v-if="studio.description" class="text-[var(--color-text-secondary)] text-sm leading-relaxed max-w-2xl">
                    {{ studio.description }}
                </p>
            </div>
        </div>

        <!-- Titles -->
        <section>
            <h2 class="font-display font-bold text-sm uppercase tracking-widest text-[var(--color-text-muted)] mb-4 border-b border-[var(--color-border)] pb-3">
                {{ $t('studio.works') }}
            </h2>
            <div v-if="!titles.data.length" class="card p-12 text-center text-[var(--color-text-muted)] text-sm">
                {{ $t('studio.noWorks') }}
            </div>
            <TitleGrid v-else :titles="titles.data" />
            <Pagination v-if="titles.last_page > 1" :meta="titles" class="mt-6" />
        </section>
    </div>
</template>
