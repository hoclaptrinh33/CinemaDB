<script setup>
defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'ghost', 'danger'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    as: { type: String, default: 'button' },
});
</script>

<template>
    <component
        :is="as"
        :class="[
            'btn',
            `btn-${variant}`,
            size === 'sm' && 'text-xs px-3 py-2',
            size === 'lg' && 'text-base px-6 py-3',
        ]"
        :disabled="disabled || loading"
        v-bind="$attrs"
    >
        <svg v-if="loading" class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <slot />
    </component>
</template>
