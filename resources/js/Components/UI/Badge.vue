<script setup>
defineProps({
    variant: {
        type: String,
        default: 'default',
        // default | accent | success | warning | danger | info | genre
        validator: (v) => ['default','accent','success','warning','danger','info','genre','movie','series','episode'].includes(v),
    },
    dot: { type: Boolean, default: false },
});

const colors = {
    default:  'bg-[var(--color-bg-elevated)] text-[var(--color-text-secondary)] border border-[var(--color-border)]',
    accent:   'bg-[var(--color-accent-muted)] text-[var(--color-accent)]',
    success:  'bg-green-950/60 text-green-400',
    warning:  'bg-amber-950/60 text-amber-400',
    danger:   'bg-red-950/60 text-red-400',
    info:     'bg-blue-950/60 text-blue-400',
    genre:    'bg-[var(--color-bg-elevated)] text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border-light)] border border-[var(--color-border)] cursor-pointer transition-colors',
    movie:    'bg-blue-950/60 text-blue-300',
    series:   'bg-purple-950/60 text-purple-300',
    episode:  'bg-teal-950/60 text-teal-300',
};
</script>

<template>
    <span :class="['badge', colors[variant]]">
        <span v-if="dot" class="w-1.5 h-1.5 rounded-full bg-current inline-block" />
        <slot />
    </span>
</template>
