<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useI18n } from 'vue-i18n';

defineOptions({ layout: AppLayout });

const props = defineProps({
    person:       { type: Object, required: true },
    filmography:  { type: Array,  default: () => [] },
    knownFor:     { type: Array,  default: () => [] },
    totalCredits: { type: Number, default: 0 },
});

const { t } = useI18n();

const bioExpanded = ref(false);
const showVioBio   = ref(!!props.person?.biography_vi);
const avatarFallback = 'https://placehold.co/400x600/1a1a24/666?text=?';

function fmtDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('vi-VN', {
        day: 'numeric', month: 'long', year: 'numeric',
    });
}

function calcAge(birthStr, deathStr) {
    if (!birthStr) return null;
    const end   = deathStr ? new Date(deathStr) : new Date();
    const start = new Date(birthStr);
    return Math.floor((end - start) / (365.25 * 24 * 3600 * 1000));
}

const age          = computed(() => calcAge(props.person.birth_date, props.person.death_date));
const primaryRole  = computed(() => props.filmography[0]?.role_name ?? null);
const genderLabel  = computed(() => {
    const map = {
        Male:   t('person.genderMale'),
        Female: t('person.genderFemale'),
        Other:  t('person.genderOther'),
    };
    return map[props.person.gender] ?? props.person.gender;
});
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
        <div class="flex flex-col lg:flex-row gap-8 items-start">

            <!-- ── LEFT SIDEBAR ── -->
            <aside class="lg:w-64 xl:w-72 shrink-0 space-y-5">

                <!-- Photo -->
                <img
                    :src="person.photo_url || avatarFallback"
                    :alt="person.full_name"
                    class="w-full aspect-[2/3] object-cover rounded-2xl border border-[var(--color-border)] shadow-2xl"
                />

                <!-- Personal info card -->
                <div class="card p-4 space-y-3">
                    <h3 class="font-display font-bold text-xs uppercase tracking-widest text-[var(--color-text-muted)]">
                        {{ $t('person.personalInfo') }}
                    </h3>
                    <dl class="space-y-2.5 text-sm">
                        <div v-if="primaryRole">
                            <dt class="text-[var(--color-text-muted)] text-xs font-semibold uppercase tracking-wider">{{ $t('person.knownFor') }}</dt>
                            <dd class="text-[var(--color-text-primary)] mt-0.5 font-medium">{{ primaryRole }}</dd>
                        </div>
                        <div v-if="totalCredits">
                            <dt class="text-[var(--color-text-muted)] text-xs font-semibold uppercase tracking-wider">{{ $t('person.totalWorks') }}</dt>
                            <dd class="text-[var(--color-text-primary)] mt-0.5">{{ totalCredits }}</dd>
                        </div>
                        <div v-if="person.gender">
                            <dt class="text-[var(--color-text-muted)] text-xs font-semibold uppercase tracking-wider">{{ $t('person.gender') }}</dt>
                            <dd class="text-[var(--color-text-primary)] mt-0.5">{{ genderLabel }}</dd>
                        </div>
                        <div v-if="person.birth_date">
                            <dt class="text-[var(--color-text-muted)] text-xs font-semibold uppercase tracking-wider">{{ $t('person.birthDate') }}</dt>
                            <dd class="text-[var(--color-text-primary)] mt-0.5 leading-snug">
                                {{ fmtDate(person.birth_date) }}
                                <span v-if="age && !person.death_date" class="text-[var(--color-text-muted)] text-xs">({{ age }} {{ $t('person.age') }})</span>
                            </dd>
                        </div>
                        <div v-if="person.death_date">
                            <dt class="text-[var(--color-text-muted)] text-xs font-semibold uppercase tracking-wider">{{ $t('person.deathDate') }}</dt>
                            <dd class="text-[var(--color-text-primary)] mt-0.5 leading-snug">
                                {{ fmtDate(person.death_date) }}
                                <span v-if="age" class="text-[var(--color-text-muted)] text-xs">({{ age }} {{ $t('person.age') }})</span>
                            </dd>
                        </div>
                        <div v-if="person.country">
                            <dt class="text-[var(--color-text-muted)] text-xs font-semibold uppercase tracking-wider">{{ $t('person.bornIn') }}</dt>
                            <dd class="text-[var(--color-text-primary)] mt-0.5">{{ person.country.country_name }}</dd>
                        </div>
                    </dl>
                </div>
            </aside>

            <!-- ── MAIN CONTENT ── -->
            <div class="flex-1 min-w-0 space-y-10">

                <!-- Name -->
                <div>
                    <h1 class="font-display font-black text-4xl md:text-5xl text-[var(--color-text-primary)] leading-tight">
                        {{ person.full_name }}
                    </h1>
                    <div v-if="person.birth_date" class="mt-1 flex items-center gap-2">
                        <Badge v-if="person.gender" variant="default">{{ genderLabel }}</Badge>
                        <span v-if="primaryRole" class="text-[var(--color-text-muted)] text-sm">{{ primaryRole }}</span>
                    </div>
                </div>

                <!-- Biography -->
                <section v-if="person.biography || person.biography_vi" class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display font-bold text-base uppercase tracking-widest text-[var(--color-text-primary)]">
                            {{ $t('person.biography') }}
                        </h2>
                        <button
                            v-if="person.biography_vi && person.biography"
                            @click="showVioBio = !showVioBio; bioExpanded = false"
                            class="text-xs text-[var(--color-accent)] border border-[var(--color-accent)] rounded px-2 py-0.5 hover:bg-[var(--color-accent)] hover:text-white transition-colors focus:outline-none"
                        >
                            {{ showVioBio ? $t('person.readInEnglish') : $t('person.readInVietnamese') }}
                        </button>
                    </div>
                    <div
                        class="text-[var(--color-text-secondary)] text-sm leading-relaxed whitespace-pre-line"
                        :class="bioExpanded ? '' : 'line-clamp-4'"
                    >{{ showVioBio ? person.biography_vi : person.biography }}</div>
                    <button
                        @click="bioExpanded = !bioExpanded"
                        class="text-[var(--color-accent)] text-sm font-medium hover:underline focus:outline-none"
                    >
                        {{ bioExpanded ? $t('person.showLess') : $t('person.readMore') }}
                    </button>
                </section>

                <!-- Known for – horizontal poster scroll -->
                <section v-if="knownFor.length" class="space-y-3">
                    <h2 class="font-display font-bold text-base uppercase tracking-widest text-[var(--color-text-primary)]">
                        {{ $t('person.knownFor') }}
                    </h2>
                    <div class="flex gap-3 overflow-x-auto pb-2" style="scrollbar-width: thin;">
                        <Link
                            v-for="credit in knownFor"
                            :key="credit.title_id"
                            :href="route('titles.show', credit.slug)"
                            class="shrink-0 w-28 group"
                        >
                            <div class="aspect-poster rounded-xl overflow-hidden border border-[var(--color-border)] bg-[var(--color-bg-surface)] group-hover:border-[var(--color-accent)] transition-colors duration-200 shadow-md">
                                <img
                                    :src="credit.poster_url"
                                    :alt="credit.title_name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                />
                            </div>
                            <p class="mt-1.5 text-xs text-[var(--color-text-secondary)] text-center leading-tight line-clamp-2 group-hover:text-[var(--color-accent)] transition-colors">
                                {{ credit.title_name_vi || credit.title_name }}
                            </p>
                        </Link>
                    </div>
                </section>

                <!-- Filmography sections grouped by role -->
                <section v-for="group in filmography" :key="group.role_name" class="space-y-1">
                    <div class="flex items-center justify-between mb-3 border-b border-[var(--color-border)] pb-2">
                        <h2 class="font-display font-bold text-base uppercase tracking-widest text-[var(--color-text-primary)]">
                            {{ group.role_name }}
                        </h2>
                        <Badge variant="default">{{ group.count }}</Badge>
                    </div>

                    <div
                        v-for="credit in group.credits"
                        :key="`${credit.title_id}-${group.role_name}`"
                        class="flex items-start gap-3 py-2 px-2 -mx-2 rounded-lg hover:bg-[var(--color-bg-surface)] transition-colors group"
                    >
                        <!-- Year -->
                        <span class="shrink-0 w-12 text-right text-[var(--color-text-muted)] text-sm font-mono mt-0.5 leading-tight">
                            {{ credit.release_date ? credit.release_date.slice(0, 4) : '—' }}
                        </span>

                        <!-- Dot -->
                        <div class="shrink-0 mt-1.5">
                            <div class="w-3 h-3 rounded-full border-2 border-[var(--color-accent)] bg-transparent group-hover:bg-[var(--color-accent)] transition-colors" />
                        </div>

                        <!-- Title + character -->
                        <div class="flex-1 min-w-0">
                            <Link
                                :href="route('titles.show', credit.slug)"
                                class="font-medium text-sm text-[var(--color-text-primary)] hover:text-[var(--color-accent)] transition-colors block leading-snug"
                            >
                                {{ credit.title_name_vi || credit.title_name }}
                            </Link>
                            <p v-if="credit.character_name" class="text-xs text-[var(--color-text-muted)] mt-0.5">
                                {{ credit.character_name }}
                            </p>
                        </div>

                        <!-- Type badge -->
                        <Badge
                            :variant="credit.title_type === 'MOVIE' ? 'movie' : 'series'"
                            class="shrink-0 self-start mt-0.5 text-xs"
                        >
                            {{ credit.title_type === 'MOVIE' ? $t('titles.movie') : credit.title_type === 'SERIES' ? $t('titles.series') : $t('titles.episode') }}
                        </Badge>
                    </div>
                </section>

            </div>
        </div>
    </div>
</template>
