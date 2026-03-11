<script setup>
import TitleCard from './TitleCard.vue';

defineProps({
    titles:  { type: Array, required: true },
    cols:    { type: Number, default: 6 }, // 4 | 5 | 6
    loading: { type: Boolean, default: false },
});

const skeletonCount = 6;
</script>

<template>
    <div
        :class="[
            'grid gap-4',
            cols === 4 && 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4',
            cols === 5 && 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
            cols === 6 && 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6',
        ]"
    >
        <!-- Loading skeletons -->
        <template v-if="loading">
            <div
                v-for="i in skeletonCount"
                :key="`sk-${i}`"
                class="aspect-poster skeleton rounded-xl"
            />
        </template>

        <!-- Actual cards -->
        <template v-else>
            <TitleCard v-for="title in titles" :key="title.id" :title="title" />
        </template>
    </div>
</template>
