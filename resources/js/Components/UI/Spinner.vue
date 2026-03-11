<script setup>
defineProps({
    size: { type: String, default: 'md' }, // sm | md | lg
    color: { type: String, default: 'accent' }, // accent | white | muted
});

const sizes = { sm: 'w-4 h-4', md: 'w-6 h-6', lg: 'w-10 h-10' };
const colors = {
    accent: 'text-[var(--color-accent)]',
    white:  'text-white',
    muted:  'text-[var(--color-text-muted)]',
};
</script>

<template>
    <svg
        :class="['animate-spin shrink-0', sizes[size], colors[color]]"
        fill="none"
        viewBox="0 0 24 24"
    >
        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
</template>
