<script setup>
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
    stats: {
        type: Object,
        required: true,
    },
    showDailyLimit: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <!-- Reviews count -->
        <div class="card p-4 flex flex-col gap-1 hover:border-indigo-500/40 transition-colors">
            <span class="text-2xl">🎬</span>
            <p class="text-2xl font-bold text-text-primary">{{ stats.reviews_count.toLocaleString() }}</p>
            <p class="text-xs text-text-muted">{{ t('userStats.reviewsCount') }}</p>
        </div>

        <!-- Nominations total -->
        <div class="card p-4 flex flex-col gap-1 hover:border-emerald-500/40 transition-colors">
            <span class="text-2xl">🏅</span>
            <p class="text-2xl font-bold text-text-primary">{{ (stats.total_nominations ?? stats.nominations_total ?? 0).toLocaleString() }}</p>
            <p class="text-xs text-text-muted">{{ t('userStats.nominationsTotal') }}</p>
            <span v-if="showDailyLimit" class="mt-auto inline-flex self-start items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full"
                  :class="stats.nominations_today > 0 ? 'bg-emerald-500/15 text-emerald-400' : 'bg-zinc-500/10 text-zinc-500'">
                {{ t('userStats.todayCount', { n: stats.nominations_today ?? 0, limit: stats.nominations_limit ?? 0 }) }}
            </span>
        </div>

        <!-- Streak -->
        <div class="card p-4 flex flex-col gap-1 hover:border-blue-500/40 transition-colors">
            <span class="text-2xl">🔥</span>
            <p class="text-2xl font-bold text-text-primary">{{ (stats.current_streak_days ?? 0).toLocaleString() }}</p>
            <p class="text-xs text-text-muted">{{ t('userStats.currentStreak') }}</p>
            <span class="mt-auto inline-flex self-start items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400">
                {{ t('userStats.bestStreak', { n: stats.longest_streak_days ?? 0 }) }}
            </span>
        </div>

        <!-- Social reach -->
        <div class="card p-4 flex flex-col gap-1 hover:border-yellow-500/40 transition-colors">
            <span class="text-2xl">👥</span>
            <p class="text-2xl font-bold text-text-primary">{{ (stats.followers_count ?? 0).toLocaleString() }}</p>
            <p class="text-xs text-text-muted">{{ t('userStats.followers') }}</p>
            <span class="mt-auto inline-flex self-start items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-yellow-500/10 text-yellow-400">
                {{ t('userStats.followingCount', { n: stats.following_count ?? 0 }) }}
            </span>
        </div>
    </div>
</template>
