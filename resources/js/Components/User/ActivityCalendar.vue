<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, tm, locale } = useI18n();

const props = defineProps({
    calendar: {
        type: Array,
        default: () => [],
    },
});

// Tooltip state
const tooltip = ref(null);
const tooltipPos = ref({ x: 0, y: 0 });

function showTooltip(e, day) {
    const d = new Date(day.date + 'T00:00:00');
    const formatted = d.toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-US', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' });
    tooltip.value = {
        text: day.active ? t('activityCalendar.active', { date: formatted }) : formatted,
        active: day.active,
    };
    tooltipPos.value = { x: e.clientX, y: e.clientY };
}

function hideTooltip() {
    tooltip.value = null;
}

// Build 52-week grid (7 rows × 52 cols + partial)
// Each entry: {date, active}
const weeks = computed(() => {
    const result = [];
    // Pad the beginning so week starts on Monday
    const firstDay = props.calendar[0]?.date;
    if (!firstDay) return result;

    const startDate = new Date(firstDay + 'T00:00:00');
    // 0=Sun,1=Mon...6=Sat → shift so Monday is 0
    const dayOfWeek = (startDate.getDay() + 6) % 7; // 0=Mon, 6=Sun

    const padded = [
        ...Array(dayOfWeek).fill(null),
        ...props.calendar,
    ];

    for (let i = 0; i < padded.length; i += 7) {
        result.push(padded.slice(i, i + 7));
    }
    return result;
});

const monthLabels = computed(() => {
    const labels = [];
    let lastMonth = -1;
    weeks.value.forEach((week, wi) => {
        const firstReal = week.find(d => d !== null);
        if (!firstReal) return;
        const m = new Date(firstReal.date + 'T00:00:00').getMonth();
        if (m !== lastMonth) {
            labels.push({ col: wi, label: new Date(firstReal.date + 'T00:00:00').toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-US', { month: 'short' }) });
            lastMonth = m;
        }
    });
    return labels;
});

const DAYS = computed(() => tm('activityCalendar.days'));
</script>

<template>
    <div class="card p-5 space-y-3 overflow-x-auto">
        <div class="flex items-center justify-between min-w-0">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-[var(--color-text-muted)]">
                {{ t('activityCalendar.title') }}
            </h3>
            <span class="text-xs text-[var(--color-text-muted)]">{{ t('activityCalendar.subtitle') }}</span>
        </div>

        <div class="relative min-w-[640px]">
            <!-- Month labels -->
            <div class="flex mb-1 pl-7">
                <template v-for="(label, i) in monthLabels" :key="i">
                    <div
                        class="text-[10px] text-[var(--color-text-muted)] absolute"
                        :style="{ left: `calc(${label.col} * 13px + 28px)` }"
                    >
                        {{ label.label }}
                    </div>
                </template>
            </div>

            <div class="flex gap-0.5 mt-4">
                <!-- Day labels -->
                <div class="flex flex-col gap-0.5 mr-1">
                    <div v-for="(day, i) in DAYS" :key="i"
                         class="text-[9px] text-[var(--color-text-muted)] h-[11px] flex items-center"
                         :class="i % 2 === 0 ? 'opacity-100' : 'opacity-0'">
                        {{ day }}
                    </div>
                </div>

                <!-- Weeks grid -->
                <div class="flex gap-0.5">
                    <div v-for="(week, wi) in weeks" :key="wi" class="flex flex-col gap-0.5">
                        <div
                            v-for="(day, di) in week"
                            :key="di"
                            class="w-[11px] h-[11px] rounded-sm transition-colors cursor-pointer"
                            :class="day === null
                                ? 'bg-transparent'
                                : day.active
                                    ? 'bg-emerald-500 hover:bg-emerald-400'
                                    : 'bg-zinc-800 hover:bg-zinc-700'"
                            @mouseenter="day && showTooltip($event, day)"
                            @mouseleave="hideTooltip"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex items-center gap-2 mt-3 justify-end">
                <span class="text-[10px] text-[var(--color-text-muted)]">{{ t('activityCalendar.less') }}</span>
                <div class="w-2.5 h-2.5 rounded-sm bg-zinc-800"></div>
                <div class="w-2.5 h-2.5 rounded-sm bg-emerald-700"></div>
                <div class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></div>
                <div class="w-2.5 h-2.5 rounded-sm bg-emerald-400"></div>
                <span class="text-[10px] text-[var(--color-text-muted)]">{{ t('activityCalendar.more') }}</span>
            </div>
        </div>

        <!-- Floating tooltip -->
        <Teleport to="body">
            <div
                v-if="tooltip"
                class="fixed z-50 pointer-events-none px-3 py-1.5 rounded-md text-xs font-medium shadow-lg border"
                :class="tooltip.active
                    ? 'bg-emerald-900/90 border-emerald-600/50 text-emerald-100'
                    : 'bg-zinc-900 border-zinc-700 text-zinc-300'"
                :style="{ top: tooltipPos.y - 40 + 'px', left: tooltipPos.x + 12 + 'px' }"
            >
                {{ tooltip.text }}
            </div>
        </Teleport>
    </div>
</template>
