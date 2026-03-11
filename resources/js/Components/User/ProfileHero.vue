<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    user: { type: Object, required: true },
    readonly: { type: Boolean, default: false },
});

const { t, locale } = useI18n();

const avatarInput = ref(null);
const coverInput  = ref(null);
const uploadingAvatar = ref(false);
const uploadingCover  = ref(false);

// Lightbox
const lightboxSrc  = ref(null);
const lightboxOpen = ref(false);

function openLightbox(src) {
    if (!src) return;
    lightboxSrc.value = src;
    lightboxOpen.value = true;
}
function closeLightbox() {
    lightboxOpen.value = false;
}
function onLightboxKey(e) {
    if (e.key === 'Escape') closeLightbox();
}

function formatJoinDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(locale.value === 'vi' ? 'vi-VN' : 'en-US', { month: 'long', year: 'numeric' });
}

const roleMeta = computed(() => ({
    ADMIN:     { label: t('profile.roleAdmin'),     color: 'bg-red-500/20 text-red-400 border-red-400/40' },
    MODERATOR: { label: t('profile.roleModerator'), color: 'bg-orange-500/20 text-orange-400 border-orange-400/40' },
    USER:      { label: t('profile.roleMember'),    color: 'bg-zinc-500/20 text-zinc-400 border-zinc-400/30' },
}));

function handleAvatarChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    uploadingAvatar.value = true;
    const form = new FormData();
    form.append('avatar', file);
    router.post(route('profile.avatar'), form, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { uploadingAvatar.value = false; e.target.value = ''; },
    });
}

function handleCoverChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    uploadingCover.value = true;
    const form = new FormData();
    form.append('cover', file);
    router.post(route('profile.cover'), form, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { uploadingCover.value = false; e.target.value = ''; },
    });
}
</script>

<template>
    <div class="relative">

        <!-- Cover image -->
        <div class="relative h-52 md:h-64 bg-gradient-to-br from-zinc-800 to-zinc-900 overflow-hidden rounded-xl group">

            <!-- Cover (click to open lightbox) -->
            <div
                class="absolute inset-0"
                :class="user.cover_url ? 'cursor-zoom-in' : 'pointer-events-none'"
                @click="openLightbox(user.cover_url)"
            >
                <img
                    v-if="user.cover_url"
                    :src="user.cover_url"
                    :alt="t('profile.changeCover')"
                    class="w-full h-full object-cover"
                />
                <div
                    v-else
                    class="w-full h-full"
                    style="background-image: radial-gradient(circle at 30% 60%, rgba(99,102,241,0.15) 0%, transparent 60%), radial-gradient(circle at 80% 20%, rgba(16,185,129,0.10) 0%, transparent 50%);"
                ></div>
            </div>

            <!-- Change cover button (bottom-right, shown on hover) -->
            <button
                v-if="!readonly"
                type="button"
                class="absolute bottom-3 right-3 z-10 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-black/60 hover:bg-black/80 border border-white/20 backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100 focus:opacity-100"
                :disabled="uploadingCover"
                @click.stop="coverInput.click()"
            >
                <template v-if="uploadingCover">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/>
                    </svg>
                    {{ t('profile.uploadingImage') }}
                </template>
                <template v-else>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ t('profile.changeCover') }}
                </template>
            </button>
        </div>

        <!-- Avatar + info bar -->
        <div class="px-4 md:px-6 pb-4 relative">
            <div class="flex items-end justify-between -mt-12 md:-mt-16 mb-4">

                <div class="relative">
                    <!-- Avatar (click to open lightbox) -->
                    <button
                        type="button"
                        class="block w-24 h-24 md:w-32 md:h-32 rounded-full border-4 border-[var(--color-bg-base)] overflow-hidden ring-2 ring-[var(--color-border)] bg-zinc-800 focus:outline-none"
                        :class="user.avatar_url ? 'cursor-zoom-in' : 'cursor-default pointer-events-none'"
                        @click="openLightbox(user.avatar_url)"
                    >
                        <img
                            v-if="user.avatar_url"
                            :src="user.avatar_url"
                            :alt="user.name"
                            class="w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center text-3xl md:text-4xl font-bold text-white/80"
                            style="background: linear-gradient(135deg, #6366f1, #8b5cf6);"
                        >
                            {{ (user.name || '?').charAt(0).toUpperCase() }}
                        </div>
                    </button>

                    <!-- Camera badge (always visible, bottom-right of avatar) -->
                    <button
                        v-if="!readonly"
                        type="button"
                        class="absolute bottom-0 right-0 w-8 h-8 rounded-full flex items-center justify-center bg-indigo-500 hover:bg-indigo-400 border-2 border-[var(--color-bg-base)] text-white shadow-lg transition-colors focus:outline-none"
                        :disabled="uploadingAvatar"
                        :title="t('profile.changeAvatar')"
                        @click="avatarInput.click()"
                    >
                        <svg v-if="uploadingAvatar" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/>
                        </svg>
                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Name + meta -->
            <div class="space-y-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-xl md:text-2xl font-bold text-[var(--color-text-primary)]">{{ user.name }}</h1>
                    <span
                        v-if="user.role !== 'USER'"
                        class="text-xs font-semibold px-2 py-0.5 rounded-full border"
                        :class="roleMeta[user.role]?.color"
                    >
                        {{ roleMeta[user.role]?.label }}
                    </span>
                </div>
                <p class="text-sm text-[var(--color-text-muted)]">@{{ user.username }}</p>
                <p class="text-xs text-[var(--color-text-muted)] flex items-center gap-1 mt-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ t('profile.memberSince') }} {{ formatJoinDate(user.created_at) }}
                </p>
            </div>
        </div>

        <!-- Hidden file inputs (upload-only, hidden in readonly mode) -->
        <template v-if="!readonly">
            <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleAvatarChange" />
            <input ref="coverInput"  type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleCoverChange" />
        </template>

        <!-- Lightbox -->
        <Teleport to="body">
            <Transition name="lightbox">
                <div
                    v-if="lightboxOpen"
                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/85 backdrop-blur-sm"
                    @click.self="closeLightbox"
                    @keydown="onLightboxKey"
                    tabindex="-1"
                >
                    <button
                        type="button"
                        class="absolute top-4 right-4 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
                        @click="closeLightbox"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <img
                        :src="lightboxSrc"
                        class="max-w-[90vw] max-h-[90vh] rounded-xl shadow-2xl object-contain select-none"
                        alt="Xem ảnh"
                        @click.stop
                    />
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.lightbox-enter-active,
.lightbox-leave-active {
    transition: opacity 0.2s ease;
}
.lightbox-enter-from,
.lightbox-leave-to {
    opacity: 0;
}
</style>
