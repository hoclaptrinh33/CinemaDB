<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    meta: { type: Object, required: true },
    // meta: { current_page, last_page, from, to, total, links }
});

const pages = computed(() => {
    const current = props.meta.current_page;
    const last    = props.meta.last_page;
    const range   = [];

    // Always show first, last, current ±2
    const add = (n) => { if (n >= 1 && n <= last && !range.includes(n)) range.push(n); };
    add(1);
    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) add(i);
    add(last);

    // Sort and add ellipsis markers
    range.sort((a, b) => a - b);
    const result = [];
    for (let i = 0; i < range.length; i++) {
        if (i > 0 && range[i] - range[i - 1] > 1) result.push('...');
        result.push(range[i]);
    }
    return result;
});

const prevUrl = computed(() => props.meta.links?.[0]?.url ?? null);
const nextUrl = computed(() => props.meta.links?.[props.meta.links.length - 1]?.url ?? null);

function pageUrl(page) {
    return props.meta.links?.find(l => l.label == page)?.url ?? null;
}
</script>

<template>
    <div v-if="meta.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 py-4">
        <!-- Count info -->
        <p class="text-sm text-[var(--color-text-muted)]">
            <span class="text-[var(--color-text-secondary)]">{{ meta.from }}–{{ meta.to }}</span>
            trong {{ meta.total }} kết quả
        </p>

        <!-- Pages -->
        <nav class="flex items-center gap-1">
            <!-- Prev -->
            <Link
                :href="prevUrl ?? '#'"
                :class="['btn btn-ghost !px-2 !py-2', !prevUrl && 'opacity-30 pointer-events-none']"
                preserve-scroll
            >
                <ChevronLeftIcon class="w-4 h-4" />
            </Link>

            <template v-for="(page, index) in pages" :key="index">
                <span v-if="page === '...'" class="px-2 text-[var(--color-text-muted)] select-none">…</span>
                <Link
                    v-else
                    :href="pageUrl(page) ?? '#'"
                    :class="[
                        'min-w-[36px] h-9 flex items-center justify-center rounded-lg text-sm font-medium transition-colors',
                        page === meta.current_page
                            ? 'bg-[var(--color-accent)] text-white font-display'
                            : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)] hover:text-[var(--color-text-primary)]'
                    ]"
                    preserve-scroll
                >{{ page }}</Link>
            </template>

            <!-- Next -->
            <Link
                :href="nextUrl ?? '#'"
                :class="['btn btn-ghost !px-2 !py-2', !nextUrl && 'opacity-30 pointer-events-none']"
                preserve-scroll
            >
                <ChevronRightIcon class="w-4 h-4" />
            </Link>
        </nav>
    </div>
</template>
