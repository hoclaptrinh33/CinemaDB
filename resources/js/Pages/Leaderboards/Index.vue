<script setup>
import { ref, computed } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import RankBadge from '@/Components/User/RankBadge.vue';
import { getLevel, getRank } from '@/composables/useRank.js';

defineOptions({ layout: AppLayout });

const { t, locale } = useI18n();

const props = defineProps({
    nominatedTitles: { type: Array,  default: () => [] },
    nomPeriod:       { type: String, default: 'week' },
    topRatedTitles:  { type: Array,  default: () => [] },
    ratingPeriod:    { type: String, default: 'month' },
    ratingYear:      { type: Number, default: () => new Date().getFullYear() },
    ratingMonth:     { type: Number, default: () => new Date().getMonth() + 1 },
    topUsers:        { type: Array,  default: () => [] },
    topActiveUsers:  { type: Array,  default: () => [] },
    activeYear:      { type: Number, default: () => new Date().getFullYear() },
    currentYear:     { type: Number, default: () => new Date().getFullYear() },
    currentMonth:    { type: Number, default: () => new Date().getMonth() + 1 },
    topLists:        { type: Array,  default: () => [] },
    listsPeriod:     { type: String, default: 'week' },
});

// ── Tabs ──────────────────────────────────────────────────────────────
const tabs = computed(() => [
    { key: 'nominations', icon: '🏅', label: t('leaderboard.tabNominations') },
    { key: 'ratings',     icon: '⭐', label: t('leaderboard.tabRatings') },
    { key: 'reputation',  icon: '👑', label: t('leaderboard.tabReputation') },
    { key: 'activity',    icon: '📅', label: t('leaderboard.tabActivity') },
    { key: 'lists',       icon: '📋', label: t('leaderboard.tabTopLists') },
]);
const activeTab = ref('nominations');

// ── Period options ────────────────────────────────────────────────────
const nomPeriods = computed(() => [
    { key: 'day',   label: t('leaderboard.periodDay') },
    { key: 'week',  label: t('leaderboard.periodWeek') },
    { key: 'month', label: t('leaderboard.periodMonth') },
    { key: 'year',  label: t('leaderboard.periodYear') },
]);
const ratingPeriods = computed(() => [
    { key: 'month', label: t('leaderboard.periodMonth') },
    { key: 'year',  label: t('leaderboard.periodYear') },
]);
const listsPeriods = computed(() => [
    { key: 'week',  label: t('leaderboard.periodWeek') },
    { key: 'month', label: t('leaderboard.periodMonth') },
    { key: 'year',  label: t('leaderboard.periodYear') },
]);

const yearOptions = computed(() => {
    const years = [];
    for (let y = props.currentYear; y >= props.currentYear - 4; y--) years.push(y);
    return years;
});

// ── Server navigation ─────────────────────────────────────────────────
function navigate(overrides) {
    router.get(
        route('leaderboards.index'),
        {
            nom_period:    props.nomPeriod,
            rating_period: props.ratingPeriod,
            rating_year:   props.ratingYear,
            rating_month:  props.ratingMonth,
            active_year:   props.activeYear,
            lists_period:  props.listsPeriod,
            ...overrides,
        },
        { preserveState: true, replace: true }
    );
}

function setNomPeriod(period)    { navigate({ nom_period: period }); }
function setRatingPeriod(period) { navigate({ rating_period: period }); }
function setRatingYear(year)     { navigate({ rating_year: year }); }
function setActiveYear(year)     { navigate({ active_year: year }); }
function setListsPeriod(period)  { navigate({ lists_period: period }); }

// ── Helpers ───────────────────────────────────────────────────────────
const MEDALS = ['🥇', '🥈', '🥉'];
function medal(i) { return MEDALS[i] ?? null; }

function fmtDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-US', { month: 'short', year: 'numeric' });
}
</script>

<template>
    <Head :title="t('leaderboard.title')" />

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

        <!-- Page header -->
        <div>
            <h1 class="text-2xl font-bold font-display text-text-primary">
                🏆 {{ t('leaderboard.title') }}
            </h1>
            <p class="text-sm text-text-muted mt-1">{{ t('leaderboard.subtitle') }}</p>
        </div>

        <!-- Tab bar -->
        <div class="flex gap-1 bg-bg-elevated rounded-xl p-1 overflow-x-auto">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="flex-1 min-w-max flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg text-sm font-medium transition-all whitespace-nowrap"
                :class="activeTab === tab.key
                    ? 'bg-accent text-white shadow-sm'
                    : 'text-text-muted hover:text-text-primary hover:bg-bg-base'"
                @click="activeTab = tab.key"
            >
                <span>{{ tab.icon }}</span>
                <span>{{ tab.label }}</span>
            </button>
        </div>

        <!-- ── Tab 1: Top nominated titles ── -->
        <div v-show="activeTab === 'nominations'" class="card p-5 space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h2 class="font-display font-bold text-lg text-text-primary">
                    🏅 {{ t('leaderboard.sectionNominations') }}
                </h2>
                <div class="flex gap-1 bg-bg-elevated rounded-lg p-1">
                    <button
                        v-for="p in nomPeriods"
                        :key="p.key"
                        type="button"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors"
                        :class="nomPeriod === p.key
                            ? 'bg-accent text-white'
                            : 'text-text-muted hover:text-text-primary'"
                        @click="setNomPeriod(p.key)"
                    >{{ p.label }}</button>
                </div>
            </div>

            <div v-if="nominatedTitles.length > 0" class="space-y-1">
                <Link
                    v-for="(item, i) in nominatedTitles"
                    :key="item.title_id"
                    :href="route('titles.show', item.slug)"
                    class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-bg-elevated transition-colors group"
                >
                    <div class="w-8 text-center shrink-0">
                        <span v-if="medal(i)" class="text-xl leading-none">{{ medal(i) }}</span>
                        <span v-else class="text-sm font-mono font-bold text-text-muted">{{ i + 1 }}</span>
                    </div>
                    <img :src="item.poster_url" :alt="item.title_name" class="w-10 h-14 object-cover rounded-md shrink-0 bg-zinc-800" />
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-text-primary truncate group-hover:text-accent transition-colors">
                            {{ item.title_name_vi || item.title_name }}
                        </p>
                        <p v-if="item.title_name_vi" class="text-xs text-text-muted truncate">{{ item.title_name }}</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-1.5 text-sm font-bold text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.11a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                        {{ item.nomination_count }}
                    </div>
                </Link>
            </div>
            <p v-else class="text-sm text-text-muted italic text-center py-8">
                {{ t('leaderboard.noData') }}
            </p>
        </div>

        <!-- ── Tab 2: Top rated titles ── -->
        <div v-show="activeTab === 'ratings'" class="card p-5 space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <h2 class="font-display font-bold text-lg text-text-primary">
                    ⭐ {{ t('leaderboard.sectionRatings') }}
                </h2>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="flex gap-1 bg-bg-elevated rounded-lg p-1">
                        <button
                            v-for="p in ratingPeriods"
                            :key="p.key"
                            type="button"
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors"
                            :class="ratingPeriod === p.key
                                ? 'bg-accent text-white'
                                : 'text-text-muted hover:text-text-primary'"
                            @click="setRatingPeriod(p.key)"
                        >{{ p.label }}</button>
                    </div>
                    <select
                        :value="ratingYear"
                        class="input-base py-1.5! px-2! text-xs w-24"
                        @change="setRatingYear(Number($event.target.value))"
                    >
                        <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>
            </div>

            <div v-if="topRatedTitles.length > 0" class="space-y-1">
                <Link
                    v-for="(item, i) in topRatedTitles"
                    :key="item.title_id"
                    :href="route('titles.show', item.slug)"
                    class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-bg-elevated transition-colors group"
                >
                    <div class="w-8 text-center shrink-0">
                        <span v-if="medal(i)" class="text-xl leading-none">{{ medal(i) }}</span>
                        <span v-else class="text-sm font-mono font-bold text-text-muted">{{ i + 1 }}</span>
                    </div>
                    <img :src="item.poster_url" :alt="item.title_name" class="w-10 h-14 object-cover rounded-md shrink-0 bg-zinc-800" />
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-text-primary truncate group-hover:text-accent transition-colors">
                            {{ item.title_name_vi || item.title_name }}
                        </p>
                        <p class="text-xs text-text-muted">{{ fmtDate(item.release_date) }} · {{ t('leaderboard.ratingCount', { n: item.rating_count }) }}</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-1.5 text-sm font-bold text-yellow-400">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" />
                        </svg>
                        {{ item.avg_rating }}
                    </div>
                </Link>
            </div>
            <p v-else class="text-sm text-text-muted italic text-center py-8">
                {{ t('leaderboard.noRatings') }}
            </p>
        </div>

        <!-- ── Tab 3: Top users by reputation ── -->
        <div v-show="activeTab === 'reputation'" class="card p-5 space-y-4">
            <h2 class="font-display font-bold text-lg text-text-primary">
                👑 {{ t('leaderboard.sectionReputation') }}
            </h2>

            <div v-if="topUsers.length > 0" class="space-y-1">
                <Link
                    v-for="(user, i) in topUsers"
                    :key="user.id"
                    :href="route('users.show', user.username)"
                    class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-bg-elevated transition-colors group"
                >
                    <div class="w-8 text-center shrink-0">
                        <span v-if="medal(i)" class="text-xl leading-none">{{ medal(i) }}</span>
                        <span v-else class="text-sm font-mono font-bold text-text-muted">{{ i + 1 }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-full overflow-hidden shrink-0 border-2"
                         :class="getRank(user.reputation).borderColor">
                        <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.username" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center text-sm font-bold"
                             :class="getRank(user.reputation).color"
                             style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
                            {{ user.username[0].toUpperCase() }}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm truncate group-hover:text-accent transition-colors"
                           :class="getRank(user.reputation).color">
                            {{ user.username }}
                        </p>
                        <div class="flex items-center gap-2">
                            <RankBadge :reputation="user.reputation" size="sm" show-label />
                            <span class="text-[10px] font-mono uppercase tracking-wider text-text-muted">Lv {{ getLevel(user.reputation).level }}</span>
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-mono font-bold text-text-muted tabular-nums">
                        {{ user.reputation.toLocaleString() }}
                    </span>
                </Link>
            </div>
            <p v-else class="text-sm text-text-muted italic text-center py-8">{{ t('leaderboard.noDataShort') }}</p>
        </div>

        <!-- ── Tab 4: Most active users ── -->
        <div v-show="activeTab === 'activity'" class="card p-5 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display font-bold text-lg text-text-primary">
                    📅 {{ t('leaderboard.sectionActivity') }}
                </h2>
                <select
                    :value="activeYear"
                    class="input-base py-1.5! px-2! text-xs w-24"
                    @change="setActiveYear(Number($event.target.value))"
                >
                    <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <div v-if="topActiveUsers.length > 0" class="space-y-1">
                <Link
                    v-for="(user, i) in topActiveUsers"
                    :key="user.id"
                    :href="route('users.show', user.username)"
                    class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-bg-elevated transition-colors group"
                >
                    <div class="w-8 text-center shrink-0">
                        <span v-if="medal(i)" class="text-xl leading-none">{{ medal(i) }}</span>
                        <span v-else class="text-sm font-mono font-bold text-text-muted">{{ i + 1 }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-full overflow-hidden shrink-0 border-2"
                         :class="getRank(user.reputation).borderColor">
                        <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.username" class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center text-sm font-bold"
                             :class="getRank(user.reputation).color"
                             style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
                            {{ user.username[0].toUpperCase() }}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-text-primary truncate group-hover:text-accent transition-colors">
                            {{ user.username }}
                        </p>
                        <div class="flex items-center gap-2">
                            <RankBadge :reputation="user.reputation" size="sm" show-label />
                            <span class="text-[10px] font-mono uppercase tracking-wider text-text-muted">Lv {{ getLevel(user.reputation).level }}</span>
                        </div>
                    </div>
                    <div class="shrink-0 flex items-center gap-1.5 text-sm font-bold text-blue-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        {{ t('leaderboard.days', { n: user.activity_days }) }}
                    </div>
                </Link>
            </div>
            <p v-else class="text-sm text-text-muted italic text-center py-8">{{ t('leaderboard.noDataShort') }}</p>
        </div>

        <!-- ── Top Lists ───────────────────────────────────────────── -->
        <div v-show="activeTab === 'lists'" class="card p-5 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-display font-bold text-lg text-text-primary">
                    📋 {{ t('leaderboard.sectionTopLists') }}
                </h2>
                <div class="flex gap-1">
                    <button
                        v-for="p in listsPeriods"
                        :key="p.key"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                        :class="listsPeriod === p.key
                            ? 'bg-accent text-white'
                            : 'bg-bg-elevated text-text-muted hover:text-text-primary'"
                        @click="setListsPeriod(p.key)"
                    >{{ p.label }}</button>
                </div>
            </div>

            <div v-if="topLists.length > 0" class="space-y-1">
                <Link
                    v-for="(list, i) in topLists"
                    :key="list.id"
                    :href="route('collections.show', list.slug)"
                    class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-bg-elevated transition-colors group"
                >
                    <div class="w-8 text-center shrink-0">
                        <span v-if="medal(i)" class="text-xl leading-none">{{ medal(i) }}</span>
                        <span v-else class="text-sm font-mono font-bold text-text-muted">{{ i + 1 }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0 border border-border">
                        <div v-if="list.auto_cover_urls && list.auto_cover_urls.length > 1" class="grid grid-cols-2 w-full h-full">
                            <img v-for="(url, ci) in list.auto_cover_urls.slice(0, 4)" :key="ci" :src="url" class="w-full h-full object-cover" loading="lazy" />
                        </div>
                        <img v-else-if="list.auto_cover_urls && list.auto_cover_urls.length === 1" :src="list.auto_cover_urls[0]" :alt="list.name" class="w-full h-full object-cover" loading="lazy" />
                        <img v-else-if="list.cover_image_url" :src="list.cover_image_url" :alt="list.name" class="w-full h-full object-cover" loading="lazy" />
                        <img v-else-if="list.cover_poster_url" :src="list.cover_poster_url" :alt="list.name" class="w-full h-full object-cover" loading="lazy" />
                        <div v-else class="w-full h-full flex items-center justify-center text-lg bg-bg-elevated">📋</div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-text-primary truncate group-hover:text-accent transition-colors">
                            {{ list.name }}
                        </p>
                        <p class="text-xs text-text-muted truncate">{{ list.owner_name }} · {{ t('collections.titlesCount', { n: list.titles_count }) }}</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-1 text-sm font-bold text-yellow-400">
                        ★ {{ list.nomination_count }}
                    </div>
                </Link>
            </div>
            <p v-else class="text-sm text-text-muted italic text-center py-8">{{ t('leaderboard.noDataShort') }}</p>
        </div>

    </div>
</template>
