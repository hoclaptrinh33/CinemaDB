<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    person:        { type: Object, required: true },
    characterName: { type: String, default: null },
    roleName:      { type: String, default: null },
    size:          { type: String, default: 'md' }, // sm | md
});

const avatarFallback = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='240' viewBox='0 0 160 240'%3E%3Crect width='160' height='240' fill='%231a1a24'/%3E%3Ccircle cx='80' cy='85' r='36' fill='%2333334a'/%3E%3Cellipse cx='80' cy='200' rx='58' ry='46' fill='%2333334a'/%3E%3C/svg%3E`;
</script>

<template>
    <Link
        :href="route('persons.show', person.person_id)"
        :class="[
            'flex flex-col items-center text-center gap-2 group cursor-pointer p-2 rounded-xl hover:bg-[var(--color-bg-elevated)] transition-colors duration-200',
            size === 'sm' ? 'w-20' : 'w-28',
        ]"
    >
        <!-- Avatar -->
        <div
            :class="[
                'rounded-xl overflow-hidden border border-[var(--color-border)] group-hover:border-[var(--color-accent)] transition-colors duration-200 shrink-0',
                size === 'sm' ? 'w-16 h-20' : 'w-24 h-32',
            ]"
        >
            <img
                :src="person.photo_url || avatarFallback"
                :alt="person.full_name"
                class="w-full h-full object-cover object-top"
                loading="lazy"
            />
        </div>

        <!-- Name -->
        <div class="space-y-0.5 w-full">
            <p
                :class="[
                    'font-display font-semibold text-[var(--color-text-primary)] group-hover:text-[var(--color-accent)] transition-colors leading-tight line-clamp-2',
                    size === 'sm' ? 'text-xs' : 'text-sm',
                ]"
            >
                {{ person.full_name }}
            </p>
            <p v-if="characterName" class="text-xs text-[var(--color-text-muted)] line-clamp-1 italic">
                {{ characterName }}
            </p>
            <p v-else-if="roleName" class="text-xs text-[var(--color-text-muted)] line-clamp-1">
                {{ roleName }}
            </p>
        </div>
    </Link>
</template>
