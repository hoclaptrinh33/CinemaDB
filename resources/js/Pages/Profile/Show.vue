<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProfileHero from '@/Components/User/ProfileHero.vue';
import ProfileStats from '@/Components/User/ProfileStats.vue';
import ProfileLevel from '@/Components/User/ProfileLevel.vue';
import ProfileCollections from '@/Components/User/ProfileCollections.vue';
import ActivityCalendar from '@/Components/User/ActivityCalendar.vue';
import UserBadges from '@/Components/User/UserBadges.vue';
import ReviewCard from '@/Components/Review/ReviewCard.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';

defineOptions({ layout: AppLayout });

const { t } = useI18n();
const page = usePage();
const auth = computed(() => page.props.auth);

const props = defineProps({
    profileUser:      { type: Object, required: true },
    isFollowing:      { type: Boolean, default: false },
    isOwnProfile:     { type: Boolean, default: false },
    earnedBadges:     { type: Array, default: () => [] },
    collections:      { type: Array, default: () => [] },
    stats:            { type: Object, default: () => ({}) },
    activityCalendar: { type: Array, default: () => [] },
    topReviews:       { type: Array, default: () => [] },
});

const following = ref(props.isFollowing);
const followerCount = ref(props.profileUser.follower_count ?? 0);
const followLoading = ref(false);

function toggleFollow() {
    if (!auth.value?.user || followLoading.value) return;
    followLoading.value = true;
    const method = following.value ? 'delete' : 'post';
    router[method](route(following.value ? 'users.unfollow' : 'users.follow', props.profileUser.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            if (following.value) {
                followerCount.value--;
            } else {
                followerCount.value++;
            }
            following.value = !following.value;
        },
        onFinish: () => { followLoading.value = false; },
    });
}
</script>

<template>
    <Head :title="t('profile.pageTitle', { name: profileUser.name })" />

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Hero: cover + avatar + name (read-only — no upload buttons) -->
        <div class="card overflow-hidden">
            <ProfileHero :user="profileUser" :readonly="true" />
            <!-- Follow bar -->
            <div class="px-5 pb-4 flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-4 text-sm text-text-secondary">
                    <span>{{ t('profile.followersCount', { n: followerCount }) }}</span>
                    <span>{{ t('profile.followingCount', { n: profileUser.following_count ?? 0 }) }}</span>
                </div>
                <div class="flex-1" />
                <button
                    v-if="auth?.user && !isOwnProfile"
                    :disabled="followLoading"
                    :class="[
                        'btn text-sm px-4 py-1.5 rounded-lg font-medium transition-all',
                        following
                            ? 'bg-bg-elevated border border-border text-text-secondary hover:border-red-500 hover:text-red-400'
                            : 'btn-primary',
                        followLoading ? 'opacity-60 cursor-not-allowed' : '',
                    ]"
                    @click="toggleFollow"
                >
                    <span v-if="followLoading">...</span>
                    <span v-else-if="following">{{ t('profile.followingAction') }}</span>
                    <span v-else>{{ t('profile.followAction') }}</span>
                </button>
            </div>
        </div>

        <!-- Stats row (3 cards, no daily-limit card) -->
        <ProfileStats :stats="stats" :show-daily-limit="false" />

        <!-- Level + progress -->
        <ProfileLevel :reputation="profileUser.reputation" />

        <!-- Activity heatmap -->
        <ActivityCalendar :calendar="activityCalendar" />

        <!-- Earned badges only -->
        <div v-if="earnedBadges.length > 0" class="card p-5 space-y-3">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-text-muted">
                {{ t('profile.badges') }}
            </h3>
            <UserBadges :all-badges="earnedBadges" :earned-only="true" />
        </div>

        <!-- Public collections -->
        <ProfileCollections :collections="collections" :is-owner="isOwnProfile" />

        <!-- Top reviews -->
        <div v-if="topReviews.length > 0" class="card p-5 space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-text-muted">
                {{ t('profile.topReviews') }}
            </h3>
            <div class="space-y-3">
                <div v-for="review in topReviews" :key="review.review_id" class="space-y-2">
                    <!-- Title link -->
                    <a
                        v-if="review.title"
                        :href="route('titles.show', review.title.slug)"
                        class="flex items-center gap-2 text-xs text-text-muted hover:text-accent transition-colors"
                    >
                        <img v-if="review.title.poster_url" :src="review.title.poster_url" :alt="review.title.title_name" class="w-5 h-7 object-cover rounded" />
                        <span class="font-medium">{{ review.title.title_name }}</span>
                    </a>
                    <ReviewCard :review="review" />
                </div>
            </div>
        </div>

    </div>
</template>
