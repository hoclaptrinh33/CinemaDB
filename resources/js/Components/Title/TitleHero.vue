<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import TrailerModal from '@/Components/UI/TrailerModal.vue';
import { useLocaleContent } from '@/composables/useLocaleContent.js';

const props = defineProps({
    titles: { type: Array, required: true },
});

const { t } = useI18n();

const currentIndex = ref(0);
const direction = ref(1); // 1 = forward, -1 = backward
const showTrailer = ref(false);
let autoTimer = null;
let pauseTimer = null; // delay before resuming after manual navigation
const PAUSE_AFTER_MANUAL = 30_000; // 30s pause after user clicks arrow

const current = computed(() => props.titles[currentIndex.value] ?? props.titles[0]);
const { name: localeName, description: localeDesc } = useLocaleContent(current);

function goTo(index, dir = 1) {
    direction.value = dir;
    currentIndex.value = ((index % props.titles.length) + props.titles.length) % props.titles.length;
}

function next() {
    showTrailer.value = false;
    goTo(currentIndex.value + 1, 1);
    pauseThenResume();
}

function prev() {
    showTrailer.value = false;
    goTo(currentIndex.value - 1, -1);
    pauseThenResume();
}

/** Stop auto-scroll, wait PAUSE_AFTER_MANUAL ms, then resume */
function pauseThenResume() {
    clearInterval(autoTimer);
    clearTimeout(pauseTimer);
    autoTimer = null;
    pauseTimer = setTimeout(() => {
        startTimer();
    }, PAUSE_AFTER_MANUAL);
}

function startTimer() {
    if (props.titles.length <= 1) return;
    autoTimer = setInterval(() => {
        // Don't change slide while trailer is open
        if (showTrailer.value) return;
        goTo(currentIndex.value + 1, 1);
    }, 5000);
}

function resetTimer() {
    clearTimeout(pauseTimer);
    clearInterval(autoTimer);
    startTimer();
}

function openTrailer() {
    showTrailer.value = true;
}

function closeTrailer() {
    showTrailer.value = false;
    resetTimer();
}

onMounted(startTimer);
onUnmounted(() => { clearInterval(autoTimer); clearTimeout(pauseTimer); });
</script>

<template>
    <div class="relative min-h-[70vh] flex items-end overflow-hidden">
        <!-- Backdrop images (crossfade all) -->
        <div class="absolute inset-0">
            <template v-for="(title, i) in titles" :key="title.id">
                <Transition name="hero-bg">
                    <img
                        v-if="i === currentIndex"
                        :src="title.backdrop_url"
                        :alt="title.title_name"
                        class="absolute inset-0 w-full h-full object-cover object-top scale-105"
                    />
                </Transition>
            </template>
            <div v-if="!current.backdrop_url" class="absolute inset-0 bg-[var(--color-bg-surface)]" />

            <!-- Overlays -->
            <div class="absolute inset-0 hero-gradient" />
            <div class="absolute inset-0 hero-gradient-bottom" />
            <div class="absolute inset-0 bg-gradient-to-t from-[var(--color-bg-base)] via-transparent to-black/40" />
        </div>

        <!-- Content -->
        <div class="relative z-10 w-full max-w-7xl mx-auto px-4 sm:px-6 pb-10 sm:pb-16 pt-20 sm:pt-32">
            <Transition :name="direction === 1 ? 'hero-slide-left' : 'hero-slide-right'" mode="out-in">
                <div :key="currentIndex" class="flex flex-col md:flex-row gap-8 items-start md:items-center">
                    <!-- Poster -->
                    <div class="hidden md:block shrink-0">
                        <img
                            :src="current.poster_url"
                            :alt="localeName"
                            class="w-40 aspect-poster object-cover rounded-xl shadow-2xl border border-[var(--color-border)]"
                        />
                    </div>

                    <!-- Info -->
                    <div class="flex-1 space-y-4">
                        <!-- Badges row -->
                        <div class="flex items-center flex-wrap gap-2">
                            <Badge variant="accent">{{ $t('show.featured') }}</Badge>
                            <Badge :variant="current.title_type === 'MOVIE' ? 'movie' : current.title_type === 'SERIES' ? 'series' : 'episode'">
                                {{ current.title_type === 'MOVIE' ? $t('titles.movie') : current.title_type === 'SERIES' ? $t('titles.series') : $t('titles.episode') }}
                            </Badge>
                            <span v-if="current.release_date" class="text-[var(--color-text-muted)] text-sm font-mono">
                                {{ current.release_date.slice(0, 4) }}
                            </span>
                            <span v-if="current.runtime_mins" class="text-[var(--color-text-muted)] text-sm">
                                · {{ current.runtime_mins }} {{ $t('show.minutes') }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h1 class="font-display font-black text-3xl sm:text-4xl md:text-6xl text-white leading-tight tracking-tight drop-shadow-lg" style="text-wrap: balance">
                            {{ localeName }}
                        </h1>

                        <!-- Rating -->
                        <div v-if="current.avg_rating" class="flex items-center gap-2">
                            <svg class="w-5 h-5 star-filled" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" />
                            </svg>
                            <span class="font-display font-bold text-2xl text-[var(--color-gold)]">
                                {{ Number(current.avg_rating).toFixed(1) }}
                            </span>
                            <span class="text-[var(--color-text-muted)] text-sm">
                                / 10 · {{ current.review_count?.toLocaleString() }} {{ $t('show.ratings') }}
                            </span>
                        </div>

                        <!-- Description -->
                        <p v-if="localeDesc" class="text-[var(--color-text-secondary)] text-base max-w-2xl line-clamp-3 leading-relaxed">
                            {{ localeDesc }}
                        </p>

                        <!-- CTAs -->
                        <div class="flex items-center gap-3 pt-2 flex-wrap">
                            <Link :href="route('titles.show', current.slug)">
                                <Button variant="primary" size="lg">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                    </svg>
                                    {{ $t('show.viewDetails') }}
                                </Button>
                            </Link>
                            <Button v-if="current.trailer_url" variant="ghost" size="lg" @click="openTrailer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                Trailer
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Left arrow -->
        <button
            v-if="titles.length > 1"
            @click="prev"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-20 flex items-center justify-center w-10 h-10 rounded-full bg-black/40 hover:bg-black/70 text-white border border-white/20 transition-all hover:scale-110"
            aria-label="Previous"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>

        <!-- Right arrow -->
        <button
            v-if="titles.length > 1"
            @click="next"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-20 flex items-center justify-center w-10 h-10 rounded-full bg-black/40 hover:bg-black/70 text-white border border-white/20 transition-all hover:scale-110"
            aria-label="Next"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
        </button>

        <!-- Dot indicators -->
        <div v-if="titles.length > 1" class="absolute bottom-[max(1.25rem,env(safe-area-inset-bottom))] left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
            <button
                v-for="(_, i) in titles"
                :key="i"
                @click="showTrailer = false; goTo(i, i > currentIndex ? 1 : -1); resetTimer()"
                :class="[
                    'transition-all rounded-full',
                    i === currentIndex
                        ? 'w-6 h-2 bg-[var(--color-accent)]'
                        : 'w-2 h-2 bg-white/40 hover:bg-white/70'
                ]"
                :aria-label="`Slide ${i + 1}`"
            />
        </div>

        <!-- Progress bar -->
        <div v-if="titles.length > 1" class="absolute bottom-0 left-0 right-0 z-20 h-[2px] bg-white/10">
            <div
                :key="currentIndex"
                class="h-full bg-[var(--color-accent)] hero-progress"
            />
        </div>

        <!-- Trailer modal -->
        <TrailerModal
            :show="showTrailer"
            :trailer-url="current.trailer_url"
            :title-name="localeName"
            @close="closeTrailer"
        />
    </div>
</template>

<style scoped>
/* Backdrop crossfade */
.hero-bg-enter-active { transition: opacity 0.8s ease; }
.hero-bg-leave-active { transition: opacity 0.8s ease; position: absolute; inset: 0; }
.hero-bg-enter-from  { opacity: 0; }
.hero-bg-leave-to    { opacity: 0; }

/* Content slide left (next) */
.hero-slide-left-enter-active  { transition: opacity 0.4s ease, transform 0.4s ease; }
.hero-slide-left-leave-active  { transition: opacity 0.3s ease, transform 0.3s ease; }
.hero-slide-left-enter-from    { opacity: 0; transform: translateX(40px); }
.hero-slide-left-leave-to      { opacity: 0; transform: translateX(-40px); }

/* Content slide right (prev) */
.hero-slide-right-enter-active { transition: opacity 0.4s ease, transform 0.4s ease; }
.hero-slide-right-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.hero-slide-right-enter-from   { opacity: 0; transform: translateX(-40px); }
.hero-slide-right-leave-to     { opacity: 0; transform: translateX(40px); }

/* Progress bar animation */
.hero-progress {
    animation: hero-progress 5s linear forwards;
}
@keyframes hero-progress {
    from { width: 0%; }
    to   { width: 100%; }
}
</style>

