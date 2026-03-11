<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { getLevel, getRank } from '@/composables/useRank.js';

/**
 * RankBadge — thẻ rank hiển thị bên cạnh tên người dùng.
 *
 * Dùng:
 *   <RankBadge :reputation="user.reputation" />
 *   <RankBadge :reputation="user.reputation" size="lg" show-label />
 */
const props = defineProps({
    reputation: { type: Number, default: 0 },
    /** 'sm' | 'md' | 'lg' */
    size:      { type: String,  default: 'sm' },
    /** Hiển thị tên rank bên cạnh icon */
    showLabel: { type: Boolean, default: false },
    /** Chỉ hiện với rank >= min threshold (0 = luôn hiện) */
    minRank:   { type: Number,  default: 0 },
});

const { t } = useI18n();

const rank = computed(() => getRank(props.reputation));
const level = computed(() => getLevel(props.reputation));

const shouldShow = computed(() => (props.reputation ?? 0) >= props.minRank);

const sizeClasses = {
    sm: 'text-[11px] px-1.5 py-0.5 gap-0.5',
    md: 'text-xs    px-2   py-0.5 gap-1',
    lg: 'text-sm    px-2.5 py-1   gap-1',
};

const iconSize = {
    sm: 'text-[10px]',
    md: 'text-xs',
    lg: 'text-sm',
};
</script>

<template>
    <span
        v-if="shouldShow"
        :title="`${t(rank.key)} · ${t('userLevel.levelNumber', { n: level.level })} — ${t('rank.reputationPts', { n: reputation })}`"
        class="inline-flex items-center rounded-full border font-semibold leading-none select-none"
        :class="[rank.bgColor, rank.borderColor, rank.color, sizeClasses[size]]"
    >
        <span :class="iconSize[size]">{{ rank.icon }}</span>
        <span v-if="showLabel">{{ t(rank.key) }} · Lv {{ level.level }}</span>
    </span>
</template>
