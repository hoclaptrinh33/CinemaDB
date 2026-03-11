<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    label:       { type: String, default: '' },
    error:       { type: String, default: '' },
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
            type="date"
            class="input-base [color-scheme:dark]"
            :class="error && '!border-red-500'"
            :value="modelValue"
            v-bind="$attrs"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="text-xs text-red-400 mt-0.5">{{ error }}</p>
    </div>
</template>
