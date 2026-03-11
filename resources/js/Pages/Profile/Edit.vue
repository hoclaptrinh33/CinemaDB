<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProfileHero from '@/Components/User/ProfileHero.vue';
import ProfileStats from '@/Components/User/ProfileStats.vue';
import ProfileLevel from '@/Components/User/ProfileLevel.vue';
import ProfileCollections from '@/Components/User/ProfileCollections.vue';
import ActivityCalendar from '@/Components/User/ActivityCalendar.vue';
import UserBadges from '@/Components/User/UserBadges.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
    user: { type: Object, required: true },
    allBadges: { type: Array, default: () => [] },
    collections: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    activityCalendar: { type: Array, default: () => [] },
});

const settingsOpen = ref(false);
</script>

<template>
    <Head title="Hồ sơ của tôi" />

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Hero: cover + avatar + name -->
            <div class="card overflow-hidden">
                <ProfileHero :user="user" />
            </div>

            <!-- Stats row -->
            <ProfileStats :stats="stats" />

            <!-- Level + progress -->
            <ProfileLevel :reputation="user.reputation" />

            <!-- Activity calendar -->
            <ActivityCalendar :calendar="activityCalendar" />

            <!-- Badges (earned + unearned) -->
            <div class="card p-5 space-y-3">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-[var(--color-text-muted)]">
                    Huy hiệu
                </h3>
                <UserBadges :all-badges="allBadges" />
            </div>

            <!-- Saved collections -->
            <ProfileCollections :collections="collections" :is-owner="true" />

            <!-- Account settings (collapsible) -->
            <div class="card overflow-hidden">
                <button
                    class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-[var(--color-bg-subtle)] transition-colors"
                    @click="settingsOpen = !settingsOpen"
                >
                    <span class="text-sm font-semibold uppercase tracking-wider text-[var(--color-text-muted)]">
                        ⚙️ Cài đặt tài khoản
                    </span>
                    <svg class="w-4 h-4 text-[var(--color-text-muted)] transition-transform" :class="settingsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div v-show="settingsOpen" class="border-t border-[var(--color-border)] divide-y divide-[var(--color-border)]">
                    <div class="p-5">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </div>
                    <div v-if="user.has_password" class="p-5">
                        <UpdatePasswordForm />
                    </div>
                    <div v-else class="p-5">
                        <p class="text-sm text-[var(--color-text-muted)]">
                            Tài khoản của bạn được liên kết qua Google. Bạn không có mật khẩu riêng cho tài khoản này.
                        </p>
                    </div>
                    <div class="p-5">
                        <DeleteUserForm />
                    </div>
                </div>
            </div>

    </div>
</template>

