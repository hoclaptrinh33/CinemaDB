<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label:       { type: String, default: '' },
    error:       { type: String, default: '' },
    hint:        { type: String, default: '' },
    required:    { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" class="text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] font-display">
            {{ label }} <span v-if="required" class="text-[var(--color-accent)]">*</span>
        </label>
        <input
            class="input-base"
            :class="error && '!border-red-500 !shadow-[0_0_0_3px_rgba(239,68,68,0.15)]'"
            :value="modelValue"
            v-bind="$attrs"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="text-xs text-red-400 mt-0.5">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-[var(--color-text-muted)]">{{ hint }}</p>
    </div>
</template>
