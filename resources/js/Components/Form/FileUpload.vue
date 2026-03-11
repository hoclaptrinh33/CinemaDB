<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: { type: [File, String, null], default: null },
    label:      { type: String, default: '' },
    hint:       { type: String, default: '' },
    error:      { type: String, default: '' },
    accept:     { type: String, default: 'image/*' },
    ratio:      { type: String, default: 'poster' }, // poster | backdrop | square
    required:   { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const isDragging = ref(false);
const inputRef   = ref(null);

const previewSrc = computed(() => {
    if (!props.modelValue) return null;
    if (props.modelValue instanceof File) return URL.createObjectURL(props.modelValue);
    return props.modelValue; // already a URL string
});

const ratioClass = {
    poster:   'aspect-poster   max-w-[160px]',
    backdrop: 'aspect-backdrop w-full',
    square:   'aspect-square   max-w-[160px]',
}[props.ratio] ?? 'aspect-poster max-w-[160px]';

function onFileChange(e) {
    const file = e.target.files?.[0] ?? null;
    if (file) emit('update:modelValue', file);
}
function onDrop(e) {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0] ?? null;
    if (file && file.type.startsWith('image/')) emit('update:modelValue', file);
}
function clear() {
    emit('update:modelValue', null);
    if (inputRef.value) inputRef.value.value = '';
}
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" class="text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] font-display">
            {{ label }} <span v-if="required" class="text-[var(--color-accent)]">*</span>
        </label>

        <div
            :class="[
                ratioClass,
                'relative rounded-xl overflow-hidden border-2 border-dashed transition-all duration-200 cursor-pointer group',
                isDragging
                    ? 'border-[var(--color-accent)] bg-[var(--color-accent-muted)]'
                    : 'border-[var(--color-border)] hover:border-[var(--color-border-light)] bg-[var(--color-bg-elevated)]',
                error && '!border-red-500',
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave="isDragging = false"
            @drop.prevent="onDrop"
            @click="inputRef?.click()"
        >
            <!-- Preview -->
            <img
                v-if="previewSrc"
                :src="previewSrc"
                class="absolute inset-0 w-full h-full object-cover"
                alt="preview"
            />

            <!-- Overlay + actions when has preview -->
            <div
                v-if="previewSrc"
                class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3"
                @click.stop
            >
                <button
                    type="button"
                    class="btn btn-secondary text-xs"
                    @click="inputRef?.click()"
                >
                    Đổi ảnh
                </button>
                <button
                    type="button"
                    class="btn btn-danger text-xs"
                    @click="clear"
                >
                    Xoá
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="!previewSrc" class="absolute inset-0 flex flex-col items-center justify-center gap-2 p-4 text-center">
                <svg class="w-8 h-8 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"
                    />
                </svg>
                <p class="text-xs text-[var(--color-text-muted)] leading-tight">
                    <span class="text-[var(--color-accent)]">Chọn ảnh</span><br/>
                    hoặc kéo thả vào đây
                </p>
            </div>
        </div>

        <input
            ref="inputRef"
            type="file"
            :accept="accept"
            class="sr-only"
            @change="onFileChange"
        />

        <p v-if="error" class="text-xs text-red-400 mt-0.5">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-[var(--color-text-muted)]">{{ hint }}</p>
    </div>
</template>
