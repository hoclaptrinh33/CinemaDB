<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { LEVELS, getLevel, getNextLevel } from '@/composables/useRank.js';

const { t, locale } = useI18n();

const props = defineProps({
    reputation: {
        type: Number,
        default: 0,
    },
});

const currentLevel = computed(() => getLevel(props.reputation));
const nextLevel = computed(() => getNextLevel(props.reputation));
const achievedLevels = computed(() => LEVELS.slice().reverse().filter((level) => props.reputation >= level.min));

const progress = computed(() => {
    if (!nextLevel.value) return 100;

    const range = nextLevel.value.min - currentLevel.value.min;
    const earned = props.reputation - currentLevel.value.min;

    return Math.min(100, Math.max(0, Math.round((earned / range) * 100)));
});

const pointsToNext = computed(() => {
    if (!nextLevel.value) return 0;

    return nextLevel.value.min - props.reputation;
});

function levelHint(level) {
    const localeCode = locale.value === 'vi' ? 'vi-VN' : 'en-US';
    const required = level.min.toLocaleString(localeCode);
    const current = props.reputation.toLocaleString(localeCode);

    return t('userLevel.hint', {
        required,
        current,
    });
}
</script>

<template>
    <div class="card p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-text-muted">{{ t('userLevel.label') }}</h3>
            <span class="text-xs text-text-muted">{{ t('userLevel.reputationPoints', { n: reputation.toLocaleString() }) }}</span>
        </div>

        <!-- Current rank -->
        <div class="flex items-center gap-3">
            <span class="text-4xl leading-none" :class="currentLevel.textGlow">{{ currentLevel.icon }}</span>
            <div class="flex-1 min-w-0">
                <div class="flex items-baseline justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-bold text-lg leading-tight" :class="currentLevel.color">{{ t(currentLevel.key) }}</p>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-text-muted">
                            {{ t('userLevel.levelNumber', { n: currentLevel.level }) }}
                        </p>
                    </div>
                    <!-- XP counter -->
                    <span class="text-sm font-mono font-semibold shrink-0">
                        <span :class="currentLevel.color">{{ reputation.toLocaleString() }}</span>
                        <span class="text-text-muted">/</span>
                        <span v-if="nextLevel" class="text-text-muted">{{ nextLevel.min.toLocaleString() }}</span>
                        <span v-else class="text-yellow-400">∞</span>
                    </span>
                </div>

                <!-- Progress bar -->
                <div class="mt-2">
                    <div class="h-2 rounded-full bg-zinc-800 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all duration-700 ease-out bg-linear-to-r"
                            :class="currentLevel.gradient"
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                    <div class="mt-1 flex justify-between text-[10px] text-text-muted">
                        <span>{{ currentLevel.min.toLocaleString() }}</span>
                        <span v-if="nextLevel">
                            {{ t('userLevel.pointsToNext', { n: pointsToNext.toLocaleString(), rank: `${t(nextLevel.key)} ${nextLevel.icon}` }) }}
                        </span>
                        <span v-else class="text-yellow-400 font-semibold">{{ t('userLevel.maxLevel') }}</span>
                        <span v-if="nextLevel">{{ nextLevel.min.toLocaleString() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rank ladder (mini) -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <button
                v-for="level in achievedLevels"
                :key="level.level"
                type="button"
                class="group relative flex items-center gap-1 text-xs px-2 py-1 rounded-full border transition-colors"
                :class="[level.bgColor, level.borderColor, level.color]"
            >
                <span class="text-sm leading-none">{{ level.icon }}</span>
                <span class="hidden sm:inline font-medium">Lv {{ level.level }}</span>
                <div
                    class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 w-56 -translate-x-1/2 rounded-xl border border-border bg-bg-elevated px-3 py-2 text-left text-[11px] font-medium leading-relaxed text-text-primary opacity-0 shadow-xl transition-opacity duration-150 group-hover:opacity-100"
                >
                    {{ levelHint(level) }}
                </div>
            </button>
        </div>
    </div>
</template>
