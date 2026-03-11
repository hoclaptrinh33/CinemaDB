<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show:      { type: Boolean, default: false },
    title:     { type: String, default: '' },
    maxWidth:  { type: String, default: 'md' }, // sm | md | lg | xl
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const maxWidthClass = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};

function close() {
    if (props.closeable) emit('close');
}

function onKeydown(e) {
    if (e.key === 'Escape') close();
}

watch(() => props.show, (v) => {
    document.body.style.overflow = v ? 'hidden' : '';
});

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition leave-active-class="transition-opacity duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                    @click="close"
                />

                <!-- Dialog -->
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="show"
                        :class="['relative w-full', maxWidthClass[maxWidth], 'bg-[var(--color-bg-surface)] border border-[var(--color-border)] rounded-2xl shadow-2xl']"
                    >
                        <!-- Header -->
                        <div v-if="title || closeable" class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-[var(--color-border)]">
                            <h3 class="font-display font-700 text-lg text-[var(--color-text-primary)]">{{ title }}</h3>
                            <button
                                v-if="closeable"
                                @click="close"
                                class="p-1.5 rounded-lg text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-elevated)] transition-colors"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-5">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div v-if="$slots.footer" class="px-6 pb-5 flex justify-end gap-3">
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
