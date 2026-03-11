<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const props = defineProps({
    allBadges: {
        type: Array,
        default: () => [],
    },
    earnedOnly: {
        type: Boolean,
        default: false,
    },
});

const INITIAL_UNEARNED_BADGES = 18;
const showAllUnearned = ref(false);

const tierBorder = {
    WOOD:     'border-orange-800',
    IRON:     'border-stone-500',
    BRONZE:   'border-amber-600',
    SILVER:   'border-slate-400',
    GOLD:     'border-yellow-400',
    PLATINUM: 'border-cyan-300',
    DIAMOND:  'border-sky-300',
};

const tierBg = {
    WOOD:     'bg-orange-900/20 text-orange-300',
    IRON:     'bg-stone-500/20 text-stone-300',
    BRONZE:   'bg-amber-600/10 text-amber-600',
    SILVER:   'bg-slate-400/10 text-slate-400',
    GOLD:     'bg-yellow-400/10 text-yellow-400',
    PLATINUM: 'bg-cyan-300/10 text-cyan-300',
    DIAMOND:  'bg-sky-300/10 text-sky-300',
};

const frameClassMap = {
    plain: 'ring-1 ring-white/10 shadow-[0_0_12px_rgba(255,255,255,0.06)]',
    'ring-iron': 'ring-2 ring-stone-300/35 shadow-[0_0_14px_rgba(214,211,209,0.12)]',
    'ring-bronze': 'ring-2 ring-amber-500/40 shadow-[0_0_18px_rgba(217,119,6,0.18)]',
    'ring-silver': 'ring-2 ring-slate-300/45 shadow-[0_0_18px_rgba(203,213,225,0.18)]',
    'ring-gold': 'ring-2 ring-yellow-300/55 shadow-[0_0_22px_rgba(250,204,21,0.24)]',
    'halo-platinum': 'ring-2 ring-cyan-200/65 shadow-[0_0_26px_rgba(103,232,249,0.34),0_0_42px_rgba(34,211,238,0.16)]',
    'halo-diamond': 'ring-2 ring-sky-200/70 shadow-[0_0_30px_rgba(125,211,252,0.36),0_0_50px_rgba(59,130,246,0.18)]',
    ember: 'ring-2 ring-orange-400/30 shadow-[0_0_20px_rgba(251,146,60,0.18)]',
    steel: 'ring-2 ring-slate-300/30 shadow-[0_0_20px_rgba(148,163,184,0.18)]',
    aurora: 'ring-2 ring-cyan-300/30 shadow-[0_0_20px_rgba(34,211,238,0.18)]',
    royal: 'ring-2 ring-amber-300/30 shadow-[0_0_20px_rgba(250,204,21,0.18)]',
};

const tierOrder = { DIAMOND: 0, PLATINUM: 1, GOLD: 2, SILVER: 3, BRONZE: 4, IRON: 5, WOOD: 6 };

const earned = computed(() => props.allBadges.filter(b => b.is_earned).sort((a, b) => tierOrder[a.tier] - tierOrder[b.tier]));
const unearned = computed(() => props.allBadges.filter(b => !b.is_earned).sort((a, b) => tierOrder[a.tier] - tierOrder[b.tier]));
const displayedUnearned = computed(() => showAllUnearned.value ? unearned.value : unearned.value.slice(0, INITIAL_UNEARNED_BADGES));
const hiddenUnearnedCount = computed(() => Math.max(0, unearned.value.length - displayedUnearned.value.length));

function conditionLabel(badge) {
    const n = badge.condition_value;
    const map = {
        review_count:       t('userBadges.condReviewCount', { n }),
        helpful_votes:      t('userBadges.condHelpfulVotes', { n }),
        collections_count:  t('userBadges.condCollections', { n }),
        distinct_types:     t('userBadges.condDistinctTypes', { n }),
        early_bird:         t('userBadges.condEarlyBird', { n }),
        collection_nominations_received: t('userBadges.condCollectionNominations', { n }),
        follower_count:     t('userBadges.condFollowerCount', { n }),
        following_count:    t('userBadges.condFollowingCount', { n }),
        activity_streak:    t('userBadges.condActivityStreak', { n }),
        login_streak:       t('userBadges.condLoginStreak', { n }),
        manual:             t('userBadges.condManual'),
    };
    return map[badge.condition_type] ?? badge.condition_type;
}

function rarityLabel(badge) {
    if (badge.earned_users_percent == null) {
        return t('userBadges.rarityUnknown');
    }

    const percent = Number(badge.earned_users_percent);

    return t('userBadges.rarityPercent', {
        percent: percent < 1 ? percent.toFixed(2) : percent.toFixed(1),
    });
}

function frameClass(badge) {
    return frameClassMap[badge.frame_style] ?? '';
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-US', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <div class="space-y-5">
        <!-- Earned badges -->
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-text-muted mb-3">
                {{ t('userBadges.earned', { n: earned.length }) }}
            </p>
            <div v-if="earned.length > 0" class="flex flex-wrap gap-3">
                <div
                    v-for="badge in earned"
                    :key="badge.badge_id"
                    class="group relative flex flex-col items-center gap-1"
                >
                    <div
                        class="w-12 h-12 rounded-full border-2 flex items-center justify-center overflow-hidden shadow-md"
                        :class="[tierBorder[badge.tier], frameClass(badge)]"
                    >
                        <img v-if="badge.icon_path" :src="badge.icon_path" :alt="badge.name" class="w-full h-full object-cover" @error="(e) => e.target.style.display = 'none'" />
                        <span v-else class="text-xl">🏅</span>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full" :class="tierBg[badge.tier]">
                        {{ badge.tier }}
                    </span>
                    <!-- Tooltip -->
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10 px-3 py-2 bg-bg-elevated border border-border text-text-primary text-xs rounded-lg shadow-xl whitespace-nowrap max-w-50 text-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        <p class="font-semibold">{{ badge.name }}</p>
                        <p class="text-text-muted mt-0.5" v-if="badge.description">{{ badge.description }}</p>
                        <p class="text-sky-400 mt-1 text-[10px]">{{ rarityLabel(badge) }}</p>
                        <p class="text-emerald-400 mt-1 text-[10px]" v-if="badge.earned_at">{{ t('userBadges.earnedAt', { date: formatDate(badge.earned_at) }) }}</p>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-text-muted italic">{{ t('userBadges.noBadges') }}</p>
        </div>

        <!-- Unearned badges -->
        <div v-if="!earnedOnly && unearned.length > 0">
            <p class="text-xs font-semibold uppercase tracking-wider text-text-muted mb-3">
                {{ t('userBadges.unearned', { n: unearned.length }) }}
            </p>
            <div class="flex flex-wrap gap-3">
                <div
                    v-for="badge in displayedUnearned"
                    :key="badge.badge_id"
                    class="group relative flex flex-col items-center gap-1"
                >
                    <!-- Grayscale wrapper separate from tooltip parent -->
                    <div class="flex flex-col items-center gap-1 opacity-35 grayscale pointer-events-none">
                        <div class="w-12 h-12 rounded-full border-2 border-zinc-700 flex items-center justify-center overflow-hidden">
                            <img v-if="badge.icon_path" :src="badge.icon_path" :alt="badge.name" class="w-full h-full object-cover" @error="(e) => e.target.style.display = 'none'" />
                            <span v-else class="text-xl">🏅</span>
                        </div>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-zinc-700/30 text-zinc-500">
                            {{ badge.tier }}
                        </span>
                    </div>
                    <!-- Tooltip — outside grayscale wrapper -->
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10 px-3 py-2 bg-bg-elevated border border-border text-text-primary text-xs rounded-lg shadow-xl whitespace-nowrap max-w-50 text-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                        <p class="font-semibold">{{ badge.name }}</p>
                        <p class="text-text-muted mt-0.5" v-if="badge.description">{{ badge.description }}</p>
                        <p class="text-zinc-400 mt-1 text-[10px]">🎯 {{ conditionLabel(badge) }}</p>
                        <p class="text-sky-400 mt-1 text-[10px]">{{ rarityLabel(badge) }}</p>
                    </div>
                </div>
            </div>
            <div v-if="unearned.length > INITIAL_UNEARNED_BADGES" class="mt-4">
                <button
                    type="button"
                    class="text-sm font-semibold text-accent hover:text-accent/80 transition-colors"
                    @click="showAllUnearned = !showAllUnearned"
                >
                    {{ showAllUnearned
                        ? t('userBadges.showLess')
                        : t('userBadges.showMore', { n: hiddenUnearnedCount }) }}
                </button>
            </div>
        </div>
    </div>
</template>