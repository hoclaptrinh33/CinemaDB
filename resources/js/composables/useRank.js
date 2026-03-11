/**
 * Hệ thống cấp bậc lớn (rank bands) + cấp độ chi tiết theo điểm Reputation.
 * Phải giữ đồng bộ với app/Services/LevelUpService.php.
 */

export const RANKS = [
    {
        min: 5200,
        key: 'rank.master',
        icon: '👑',
        color: 'text-yellow-400',
        bgColor: 'bg-yellow-400/15',
        borderColor: 'border-yellow-400/50',
        textGlow: 'drop-shadow-[0_0_8px_rgba(250,204,21,0.7)]',
        gradient: 'from-yellow-400 to-amber-500',
    },
    {
        min: 3333,
        key: 'rank.legend',
        icon: '🏆',
        color: 'text-purple-400',
        bgColor: 'bg-purple-400/15',
        borderColor: 'border-purple-400/50',
        textGlow: 'drop-shadow-[0_0_8px_rgba(192,132,252,0.7)]',
        gradient: 'from-purple-400 to-violet-500',
    },
    {
        min: 1300,
        key: 'rank.critic',
        icon: '✍️',
        color: 'text-blue-400',
        bgColor: 'bg-blue-400/15',
        borderColor: 'border-blue-400/50',
        textGlow: '',
        gradient: 'from-blue-400 to-cyan-500',
    },
    {
        min: 380,
        key: 'rank.connoisseur',
        icon: '🎬',
        color: 'text-emerald-400',
        bgColor: 'bg-emerald-400/15',
        borderColor: 'border-emerald-400/50',
        textGlow: '',
        gradient: 'from-emerald-400 to-green-500',
    },
    {
        min: 60,
        key: 'rank.grinder',
        icon: '🍿',
        color: 'text-green-400',
        bgColor: 'bg-green-400/15',
        borderColor: 'border-green-400/50',
        textGlow: '',
        gradient: 'from-green-400 to-lime-400',
    },
    {
        min: 0,
        key: 'rank.casual',
        icon: '🏟️',
        color: 'text-zinc-400',
        bgColor: 'bg-zinc-400/10',
        borderColor: 'border-zinc-400/30',
        textGlow: '',
        gradient: 'from-zinc-400 to-zinc-500',
    },
];

const levelThresholds = [18000, 15000, 12500, 10000, 8000, 6500, 5200, 4200, 3700, 3333, 2800, 2300, 1800, 1300, 850, 600, 380, 240, 150, 95, 60, 30, 10, 0];

export const LEVELS = levelThresholds.map((min, index) => {
    const level = levelThresholds.length - index;
    const rank = RANKS.find((entry) => min >= entry.min) ?? RANKS[RANKS.length - 1];

    return {
        level,
        ...rank,
        min,
    };
});

/**
 * Trả về rank info từ điểm reputation.
 * @param {number} reputation
 * @returns {Object} rank info
 */
export function getRank(reputation = 0) {
    return RANKS.find(r => (reputation ?? 0) >= r.min) ?? RANKS[RANKS.length - 1];
}

export function getLevel(reputation = 0) {
    return LEVELS.find((level) => (reputation ?? 0) >= level.min) ?? LEVELS[LEVELS.length - 1];
}

export function getNextLevel(reputation = 0) {
    const current = getLevel(reputation);
    const index = LEVELS.findIndex((level) => level.level === current.level);

    return index > 0 ? LEVELS[index - 1] : null;
}

/**
 * Composable để dùng trong setup()
 */
export function useRank() {
    return { getRank, getLevel, getNextLevel, RANKS, LEVELS };
}
