<script setup>
import { computed } from 'vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    show:       { type: Boolean, default: false },
    trailerUrl: { type: String,  default: '' },
    titleName:  { type: String,  default: '' },
});

const emit = defineEmits(['close']);

/** Convert any YouTube URL to embed format, pass other URLs as-is */
const embedUrl = computed(() => {
    if (!props.trailerUrl) return '';
    try {
        const url = new URL(props.trailerUrl);
        // youtube.com/watch?v=ID
        if (url.hostname.includes('youtube.com') && url.searchParams.get('v')) {
            return `https://www.youtube.com/embed/${url.searchParams.get('v')}?autoplay=1&rel=0`;
        }
        // youtu.be/ID
        if (url.hostname === 'youtu.be') {
            const id = url.pathname.replace('/', '');
            return `https://www.youtube.com/embed/${id}?autoplay=1&rel=0`;
        }
    } catch {
        // not a valid URL, return as-is
    }
    return props.trailerUrl;
});

function close() {
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.esc="close">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/85 backdrop-blur-sm" @click="close" />

                <!-- Dialog -->
                <Transition
                    enter-active-class="transition-all duration-250"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="show" class="relative w-full max-w-4xl">
                        <!-- Close button -->
                        <button
                            @click="close"
                            class="absolute -top-10 right-0 flex items-center gap-1.5 text-white/70 hover:text-white transition-colors text-sm"
                            aria-label="Close trailer"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            Đóng
                        </button>

                        <!-- Video wrapper 16:9 -->
                        <div class="relative w-full" style="padding-bottom: 56.25%;">
                            <iframe
                                v-if="embedUrl"
                                :src="embedUrl"
                                :title="titleName ? `Trailer: ${titleName}` : 'Trailer'"
                                class="absolute inset-0 w-full h-full rounded-xl"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            />
                        </div>

                        <!-- Title -->
                        <p v-if="titleName" class="mt-3 text-center text-white/60 text-sm font-display tracking-wide">
                            {{ titleName }} — Trailer
                        </p>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
