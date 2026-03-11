<script setup>
import PersonCard from './PersonCard.vue';
import { useI18n } from 'vue-i18n';

const { t, te } = useI18n();

defineProps({
    cast: { type: Array, required: true }, // CastMember[]
    crew: { type: Array, default: () => [] }, // CrewMember[]
});

function roleLabel(roleName) {
    if (!roleName) return '';

    const key = String(roleName)
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');

    const i18nKey = `crewRoles.${key}`;
    return te(i18nKey) ? t(i18nKey) : roleName;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Cast -->
        <div v-if="cast.length">
            <h3 class="font-display font-bold text-sm uppercase tracking-widest text-[var(--color-text-muted)] mb-3">
                {{ t('show.cast') }}
            </h3>
            <div class="flex gap-2 overflow-x-auto pb-3 scrollbar-thin" style="scrollbar-width: thin;">
                <PersonCard
                    v-for="member in cast"
                    :key="member.person_id"
                    :person="{ person_id: member.person_id, full_name: member.full_name, photo_url: member.photo_url }"
                    :character-name="member.character_name"
                    class="shrink-0"
                />
            </div>
        </div>

        <!-- Crew (grouped by role) -->
        <div v-if="crew.length">
            <h3 class="font-display font-bold text-sm uppercase tracking-widest text-[var(--color-text-muted)] mb-3">
                {{ t('show.crew') }}
            </h3>
            <div class="flex flex-wrap gap-4">
                <template v-for="member in crew" :key="member.person_id + member.role_name">
                    <PersonCard
                        :person="{ person_id: member.person_id, full_name: member.full_name, photo_url: member.photo_url }"
                        :role-name="roleLabel(member.role_name)"
                        size="sm"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
