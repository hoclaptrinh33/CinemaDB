<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label:       { type: String, default: '' },
    error:       { type: String, default: '' },
    hint:        { type: String, default: '' },
    required:    { type: Boolean, default: false },
    options:     { type: Array, default: () => [] }, // [{ value, label }] or plain strings
    placeholder: { type: String, default: 'Chọn...' },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" class="text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] font-display">
            {{ label }} <span v-if="required" class="text-[var(--color-accent)]">*</span>
        </label>
        <div class="relative">
            <select
                class="input-base pr-9 cursor-pointer"
                :class="error && '!border-red-500'"
                :value="modelValue"
                v-bind="$attrs"
                @change="$emit('update:modelValue', $event.target.value)"
            >
                <option value="" disabled>{{ placeholder }}</option>
                <option
                    v-for="opt in options"
                    :key="typeof opt === 'object' ? opt.value : opt"
                    :value="typeof opt === 'object' ? opt.value : opt"
                >
                    {{ typeof opt === 'object' ? opt.label : opt }}
                </option>
                <slot />
            </select>
            <!-- Arrow icon -->
            <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                <svg class="w-4 h-4 text-[var(--color-text-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>
        <p v-if="error" class="text-xs text-red-400 mt-0.5">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-[var(--color-text-muted)]">{{ hint }}</p>
    </div>
</template>
