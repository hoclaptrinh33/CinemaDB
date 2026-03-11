<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: { type: Number, default: null }, // 1–10 or null
    label:      { type: String, default: 'Điểm đánh giá' },
    hint:       { type: String, default: '' },
    error:      { type: String, default: '' },
    readonly:   { type: Boolean, default: false },
    size:       { type: String, default: 'md' }, // sm | md | lg
});
const emit = defineEmits(['update:modelValue']);

const hovered = ref(null);

const active = computed(() => hovered.value ?? props.modelValue ?? 0);

const sizeClass = {
    sm: 'w-5 h-5',
    md: 'w-7 h-7',
    lg: 'w-9 h-9',
}[props.size];

const qualityLabel = computed(() => {
    const labels = {
        1: 'Tệ hại', 2: 'Rất tệ', 3: 'Tệ', 4: 'Trung bình',
        5: 'Ổn', 6: 'Khá', 7: 'Tốt', 8: 'Rất tốt',
        9: 'Xuất sắc', 10: 'Kiệt tác',
    };
    return active.value ? labels[active.value] : 'Chưa chọn';
});

function select(n) {
    if (props.readonly) return;
    emit('update:modelValue', props.modelValue === n ? null : n);
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <span v-if="props.label" class="text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] font-display">
                {{ props.label }}
            </span>
            <div class="flex items-center gap-1.5">
                <span
                    v-if="active"
                    class="text-[var(--color-gold)] font-display font-bold text-lg leading-none"
                >
                    {{ active }}
                </span>
                <span class="text-xs text-[var(--color-text-muted)]">/ 10 — {{ qualityLabel }}</span>
            </div>
        </div>

        <div class="flex items-center gap-0.5">
            <button
                v-for="n in 10"
                :key="n"
                type="button"
                :disabled="readonly"
                :aria-label="`Đánh giá ${n}/10`"
                :class="[
                    sizeClass,
                    'transition-transform duration-100',
                    !readonly && 'hover:scale-110 cursor-pointer',
                    readonly && 'cursor-default',
                ]"
                @mouseenter="!readonly && (hovered = n)"
                @mouseleave="hovered = null"
                @click="select(n)"
            >
                <svg viewBox="0 0 24 24" class="w-full h-full">
                    <path
                        d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5 7.1-1.2z"
                        :fill="n <= active ? 'var(--color-gold)' : 'var(--color-bg-overlay)'"
                        :stroke="n <= active ? 'var(--color-gold)' : 'var(--color-border-light)'"
                        stroke-width="1.2"
                        stroke-linejoin="round"
                    />
                </svg>
            </button>
        </div>

        <p v-if="error" class="text-xs text-red-400 mt-0.5">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-[var(--color-text-muted)]">{{ hint }}</p>
    </div>
</template>
