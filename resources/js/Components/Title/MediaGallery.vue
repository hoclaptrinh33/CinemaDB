<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    media: { type: Array, default: () => [] },
});

const active = ref(null);
const activeIndex = ref(0);

const backdrops = computed(() => props.media.filter(m => m.media_type === 'backdrop'));
const trailers  = computed(() => props.media.filter(m => m.media_type === 'trailer'));
const all       = computed(() => [...backdrops.value, ...trailers.value]);

function open(item, index) {
    active.value = item;
    activeIndex.value = index;
}
function close() { active.value = null; }

function prev() {
    if (activeIndex.value > 0) {
        activeIndex.value--;
        active.value = all.value[activeIndex.value];
    }
}
function next() {
    if (activeIndex.value < all.value.length - 1) {
        activeIndex.value++;
        active.value = all.value[activeIndex.value];
    }
}

function youtubeId(url) {
    const m = url.match(/[?&]v=([^&]+)/);
    return m ? m[1] : null;
}

function handleKey(e) {
    if (!active.value) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === 'ArrowRight') next();
}
</script>

<template>
    <div v-if="all.length" @keydown="handleKey" tabindex="-1">
        <!-- Thumbnail grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
            <div
                v-for="(item, idx) in all"
                :key="idx"
                class="relative group aspect-video rounded-lg overflow-hidden cursor-pointer bg-[var(--color-bg-elevated)] border border-[var(--color-border)] hover:border-[var(--color-accent)]/50 transition-colors"
                @click="open(item, idx)"
            >
                <img
                    :src="item.thumbnail_url || item.url"
                    :alt="item.title || 'Media'"
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                />
                <!-- Play icon for trailers -->
                <div
                    v-if="item.media_type === 'trailer'"
                    class="absolute inset-0 flex items-center justify-center bg-black/40 group-hover:bg-black/50 transition-colors"
                >
                    <div class="w-10 h-10 rounded-full bg-[var(--color-accent)] flex items-center justify-center shadow-lg">
                        <svg class="w-4 h-4 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653Z" />
                        </svg>
                    </div>
                </div>
                <!-- Trailer label -->
                <span
                    v-if="item.media_type === 'trailer' && item.title"
                    class="absolute bottom-0 left-0 right-0 px-2 py-1 text-xs text-white bg-gradient-to-t from-black/80 to-transparent truncate"
                >
                    {{ item.title }}
                </span>
            </div>
        </div>

        <!-- Fullscreen modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="active"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
                    @click.self="close"
                >
                    <!-- Content -->
                    <div class="relative w-full max-w-5xl mx-4">
                        <!-- Close -->
                        <button
                            type="button"
                            class="absolute -top-10 right-0 text-white/70 hover:text-white text-sm flex items-center gap-1"
                            @click="close"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Đóng
                        </button>

                        <!-- YouTube iframe for trailers -->
                        <div v-if="active.media_type === 'trailer'" class="aspect-video w-full rounded-xl overflow-hidden">
                            <iframe
                                :src="`https://www.youtube.com/embed/${youtubeId(active.url)}?autoplay=1`"
                                class="w-full h-full"
                                frameborder="0"
                                allow="autoplay; encrypted-media"
                                allowfullscreen
                            />
                        </div>

                        <!-- Full image for backdrops -->
                        <div v-else class="flex items-center justify-center">
                            <img
                                :src="active.url"
                                :alt="active.title || 'Backdrop'"
                                class="max-h-[80vh] w-full object-contain rounded-xl"
                            />
                        </div>

                        <!-- Nav arrows -->
                        <button
                            v-if="activeIndex > 0"
                            type="button"
                            class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-colors"
                            @click="prev"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <button
                            v-if="activeIndex < all.length - 1"
                            type="button"
                            class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-colors"
                            @click="next"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>

                        <!-- Counter -->
                        <p class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-white/50 text-sm font-mono">
                            {{ activeIndex + 1 }} / {{ all.length }}
                        </p>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
