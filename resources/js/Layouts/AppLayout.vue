<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Alert from '@/Components/UI/Alert.vue';
import LanguageSwitcher from '@/Components/UI/LanguageSwitcher.vue';
import SmartSearchBar from '@/Components/UI/SmartSearchBar.vue';
import { notifIcon, notifColor, notifTitle } from '@/utils/notifHelpers.js';

const { t } = useI18n();
const page  = usePage();
const auth  = computed(() => page.props.auth);
const flash = computed(() => page.props.flash);

const searchOpen  = ref(false);
const drawerOpen  = ref(false);  // hamburger side-drawer
const notifOpen   = ref(false);  // notification panel
const headerRef   = ref(null);
const headerHeight = ref(64);
let headerResizeObserver = null;

// ── Notifications ────────────────────────────────────────────────────
const notifications = ref([]);
const notifLoading  = ref(false);
const badgeToasts   = ref([]);

const unreadCount = computed(() => notifications.value.length);

const tierColor = {
    WOOD:     'border-orange-800 bg-orange-900/20',
    IRON:     'border-stone-500 bg-stone-500/10',
    BRONZE:   'border-amber-600 bg-amber-600/10',
    SILVER:   'border-slate-400 bg-slate-400/10',
    GOLD:     'border-yellow-400 bg-yellow-400/10',
    PLATINUM: 'border-cyan-300 bg-cyan-300/10',
    DIAMOND:  'border-sky-300 bg-sky-300/10',
};


function dismissToast(id) {
    badgeToasts.value = badgeToasts.value.filter(t => t.id !== id);
}

async function loadNotifications() {
    if (!auth.value?.user || notifLoading.value) return;
    notifLoading.value = true;
    try {
        const { data } = await axios.get('/api/notifications/unread');
        // Split: badge toasts vs panel notifications
        const badgeOnes = data.filter(n => n.type === 'badge_earned');
        notifications.value = data.filter(n => n.type !== 'badge_earned');

        // Mark badge notifications read immediately and show toasts
        for (const [i, n] of badgeOnes.entries()) {
            setTimeout(async () => {
                const toast = { id: n.id, ...n.data };
                badgeToasts.value.push(toast);
                setTimeout(() => dismissToast(toast.id), 6000);
                await axios.post(`/api/notifications/${n.id}/read`);
            }, i * 400);
        }
    } catch {
        // non-critical
    } finally {
        notifLoading.value = false;
    }
}

async function markAllRead() {
    try {
        await axios.post('/api/notifications/read-all');
        notifications.value = [];
    } catch { /* silent */ }
}

async function markRead(id) {
    try {
        await axios.post(`/api/notifications/${id}/read`);
        notifications.value = notifications.value.filter(n => n.id !== id);
    } catch { /* silent */ }
}

function toggleNotif() {
    notifOpen.value = !notifOpen.value;
    if (notifOpen.value) {
        drawerOpen.value = false;
        loadNotifications();
    }
}

function toggleDrawer() {
    drawerOpen.value = !drawerOpen.value;
    if (drawerOpen.value) notifOpen.value = false;
}

// Close panels when clicking outside
function handleOutsideClick(e) {
    if (!e.target.closest('[data-notif-panel]') && !e.target.closest('[data-notif-btn]')) {
        notifOpen.value = false;
    }
    if (!e.target.closest('[data-drawer]') && !e.target.closest('[data-drawer-btn]')) {
        drawerOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
    if (auth.value?.user) loadNotifications();

    if (typeof ResizeObserver !== 'undefined' && headerRef.value) {
        headerResizeObserver = new ResizeObserver(([entry]) => {
            headerHeight.value = entry.contentRect.height;
        });

        headerResizeObserver.observe(headerRef.value);
        headerHeight.value = headerRef.value.getBoundingClientRect().height;
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleOutsideClick);
    headerResizeObserver?.disconnect();
});
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <!-- ── HEADER ── -->
        <header ref="headerRef" class="glass fixed inset-x-0 top-0 z-50 border-b border-[var(--color-border)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-3">
                <!-- Logo -->
                <Link :href="route('home')" class="shrink-0 flex items-center gap-2 group">
                    <div class="w-8 h-8 rounded-lg bg-[var(--color-accent)] flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375Z" />
                            <path fill-rule="evenodd" d="m3.087 9 .54 9.176A3 3 0 0 0 6.62 21h10.757a3 3 0 0 0 2.995-2.824L20.913 9H3.087ZM12 10.5a.75.75 0 0 1 .75.75v4.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 1 1 1.06-1.06l1.72 1.72v-4.94a.75.75 0 0 1 .75-.75Z" />
                        </svg>
                    </div>
                    <span class="font-display font-black text-lg text-[var(--color-text-primary)] group-hover:text-[var(--color-accent)] transition-colors tracking-tight">
                        Cinema<span class="text-[var(--color-accent)]">DB</span>
                    </span>
                </Link>

                <!-- Nav links (desktop) -->
                <nav class="hidden md:flex items-center gap-1 ml-2">
                    <Link
                        v-for="link in [{ label: t('nav.home'), route: 'home' }, { label: t('nav.movies'), route: 'titles.index' }, { label: t('nav.leaderboard'), route: 'leaderboards.index' }]"
                        :key="link.route"
                        :href="route(link.route)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            route().current(link.route)
                                ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]'
                                : 'text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-elevated)]',
                        ]"
                    >{{ link.label }}</Link>
                </nav>

                <div class="flex-1" />

                <!-- Search -->
                <button class="btn btn-ghost !px-2 !py-2" aria-label="Search" @click="searchOpen = !searchOpen">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </button>

                <LanguageSwitcher class="hidden md:flex" />

                <template v-if="auth.user">
                    <!-- Admin link -->
                    <Link v-if="auth.can?.accessAdmin" :href="route('admin.dashboard')" class="hidden md:inline-flex btn btn-ghost text-xs !py-1.5 !px-3">
                        Admin
                    </Link>

                    <!-- 🔔 Bell icon -->
                    <div class="relative" data-notif-btn>
                        <button
                            class="btn btn-ghost !px-2 !py-2 relative"
                            :aria-label="t('nav.notifications')"
                            @click.stop="toggleNotif"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            <!-- Unread badge -->
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
                            >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                        </button>

                        <!-- Notification panel -->
                        <Transition name="pop">
                            <div
                                v-if="notifOpen"
                                data-notif-panel
                                class="absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw-1rem)] bg-[var(--color-bg-elevated)] border border-[var(--color-border)] rounded-2xl shadow-2xl z-50 overflow-hidden"
                                @click.stop
                            >
                                <!-- Header -->
                                <div class="flex items-center justify-between px-4 py-3 border-b border-[var(--color-border)]">
                                    <h3 class="font-semibold text-sm text-[var(--color-text-primary)]">{{ t('nav.notifications') }}</h3>
                                    <button
                                        v-if="notifications.length > 0"
                                        class="text-xs text-[var(--color-accent)] hover:underline"
                                        @click="markAllRead"
                                    >{{ t('nav.markAllRead') }}</button>
                                </div>
                                <!-- List -->
                                <div class="max-h-96 overflow-y-auto">
                                    <div v-if="notifLoading" class="flex items-center justify-center py-8 text-[var(--color-text-muted)] text-sm">
                                        {{ t('common.loading') }}
                                    </div>
                                    <div v-else-if="notifications.length === 0" class="flex flex-col items-center justify-center py-10 gap-2 text-[var(--color-text-muted)]">
                                        <svg class="w-8 h-8 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                        </svg>
                                        <p class="text-sm">{{ t('nav.noNotifications') }}</p>
                                    </div>
                                    <template v-else>
                                        <component
                                            :is="n.data?.url ? 'a' : 'div'"
                                            v-for="n in notifications"
                                            :key="n.id"
                                            :href="n.data?.url ?? undefined"
                                            class="flex items-start gap-3 px-4 py-3 hover:bg-[var(--color-bg-overlay)] transition-colors group"
                                            :class="n.data?.url ? 'cursor-pointer' : 'cursor-default'"
                                            @click="n.data?.url && markRead(n.id)"
                                        >
                                            <span class="text-xl shrink-0 mt-0.5">{{ notifIcon[n.type] ?? '🔔' }}</span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-[var(--color-text-primary)] leading-snug">{{ notifTitle(n) }}</p>
                                                <p v-if="n.data?.preview" class="text-xs text-[var(--color-text-muted)] mt-0.5 truncate">{{ n.data.preview }}</p>
                                                <p class="text-[10px] text-[var(--color-text-muted)] mt-1">{{ n.created_at }}</p>
                                            </div>
                                            <button
                                                class="shrink-0 opacity-0 group-hover:opacity-100 text-[var(--color-text-muted)] hover:text-red-400 transition-all"
                                                :title="t('nav.markRead')"
                                                @click.prevent.stop="markRead(n.id)"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </component>
                                    </template>
                                </div>
                                <!-- Footer: link to notification center -->
                                <div class="border-t border-[var(--color-border)] px-4 py-2.5 text-center">
                                    <Link
                                        :href="route('notifications.index')"
                                        class="text-xs text-[var(--color-accent)] hover:underline font-medium"
                                        @click="notifOpen = false"
                                    >{{ t('nav.viewAll') }}</Link>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <!-- Avatar + username → link to profile -->
                    <Link :href="route('profile.edit')" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <div class="w-8 h-8 rounded-full bg-[var(--color-accent)] flex items-center justify-center text-white text-sm font-bold font-display overflow-hidden">
                            <img v-if="auth.user.avatar_url" :src="auth.user.avatar_url" :alt="auth.user.username" class="w-full h-full object-cover" />
                            <span v-else>{{ auth.user.username[0].toUpperCase() }}</span>
                        </div>
                        <span class="text-sm text-[var(--color-text-secondary)] hidden lg:block">{{ auth.user.username }}</span>
                    </Link>
                </template>
                <template v-else>
                    <div class="hidden md:flex items-center gap-2">
                        <Link :href="route('login')" class="btn btn-ghost text-sm">{{ $t('nav.login') }}</Link>
                        <Link :href="route('register')" class="btn btn-primary text-sm">{{ $t('nav.register') }}</Link>
                    </div>
                </template>

                <!-- ≡ Hamburger — always visible when logged in, mobile only otherwise -->
                <button
                    data-drawer-btn
                    :class="['btn btn-ghost !px-2 !py-2', auth.user ? 'flex' : 'md:hidden flex']"
                    aria-label="Menu"
                    @click.stop="toggleDrawer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>

            <!-- Search bar (expandable) -->
            <div v-if="searchOpen" class="border-t border-[var(--color-border)] px-4 sm:px-6 py-3">
                <div class="max-w-2xl mx-auto">
                    <SmartSearchBar />
                </div>
            </div>
        </header>

        <div aria-hidden="true" :style="{ height: `${headerHeight}px` }"></div>

        <!-- ── DRAWER OVERLAY ── -->
        <Transition name="fade-overlay">
            <div
                v-if="drawerOpen"
                class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
                @click="drawerOpen = false"
            />
        </Transition>

        <!-- ── DRAWER PANEL ── -->
        <Transition name="slide-drawer">
            <aside
                v-if="drawerOpen"
                data-drawer
                class="fixed right-0 top-0 h-full w-72 z-50 bg-[var(--color-bg-elevated)] border-l border-[var(--color-border)] shadow-2xl flex flex-col"
                @click.stop
            >
                <!-- Drawer header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-[var(--color-border)]">
                    <template v-if="auth.user">
                        <Link :href="route('profile.edit')" class="flex items-center gap-3 group" @click="drawerOpen = false">
                            <div class="w-10 h-10 rounded-full bg-[var(--color-accent)] flex items-center justify-center text-white font-bold overflow-hidden">
                                <img v-if="auth.user.avatar_url" :src="auth.user.avatar_url" :alt="auth.user.username" class="w-full h-full object-cover" />
                                <span v-else>{{ auth.user.username[0].toUpperCase() }}</span>
                            </div>
                            <div>
                                <p class="font-semibold text-sm text-[var(--color-text-primary)] group-hover:text-[var(--color-accent)] transition-colors">{{ auth.user.username }}</p>
                                <p class="text-xs text-[var(--color-text-muted)]">{{ auth.user.name }}</p>
                            </div>
                        </Link>
                    </template>
                    <template v-else>
                        <span class="font-display font-black text-lg">Cinema<span class="text-[var(--color-accent)]">DB</span></span>
                    </template>
                    <button class="btn btn-ghost !px-2 !py-2 ml-auto" @click="drawerOpen = false">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Drawer nav -->
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                    <!-- Home -->
                    <Link
                        :href="route('home')"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                        :class="route().current('home') ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)]'"
                        @click="drawerOpen = false"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        {{ t('nav.home') }}
                    </Link>

                    <!-- Movies -->
                    <Link
                        :href="route('titles.index')"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                        :class="route().current('titles.index') ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)]'"
                        @click="drawerOpen = false"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125A1.125 1.125 0 0 1 1.125 17.25V4.5C1.5 4.5 2 4 2.625 4h14.25C17.5 4 18 4.5 18 5.125v.75M3.375 19.5H18m0 0h1.5m-1.5 0c-.621 0-1.125-.504-1.125-1.125v-5.25M18 19.5V18m0-5.25v-.125c0-.621.504-1.125 1.125-1.125h1.5" />
                        </svg>
                        {{ t('nav.movies') }}
                    </Link>

                    <!-- Bảng xếp hạng -->
                    <Link
                        :href="route('leaderboards.index')"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                        :class="route().current('leaderboards.index') ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)]'"
                        @click="drawerOpen = false"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                        </svg>
                        {{ t('nav.leaderboard') }}
                    </Link>

                    <template v-if="auth.user">
                        <hr class="border-[var(--color-border)] my-2" />

                        <!-- Profile -->
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                            :class="route().current('profile.edit') ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)]'"
                            @click="drawerOpen = false"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            {{ t('nav.myProfile') }}
                        </Link>

                        <!-- My Collections -->
                        <Link
                            :href="route('users.collections', auth.user.id)"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                            :class="route().current('users.collections') ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)]'"
                            @click="drawerOpen = false"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0z" />
                            </svg>
                            {{ t('nav.myCollections') }}
                        </Link>

                        <!-- Feed -->
                        <Link
                            :href="route('feed')"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                            :class="route().current('feed') ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)]'"
                            @click="drawerOpen = false"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3h10.5a2.25 2.25 0 0 1 2.25 2.25v13.5a2.25 2.25 0 0 1-2.25 2.25H6.75a2.25 2.25 0 0 1-2.25-2.25V5.25A2.25 2.25 0 0 1 6.75 3Z" />
                            </svg>
                            {{ t('nav.feed') }}
                        </Link>

                        <!-- Notifications -->
                        <Link
                            :href="route('notifications.index')"
                            class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                            :class="route().current('notifications.index') ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)]'"
                            @click="drawerOpen = false"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            {{ t('nav.notifications') }}
                            <span
                                v-if="unreadCount > 0"
                                class="ml-auto min-w-[20px] h-5 px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
                            >{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                        </Link>

                        <!-- Activity Log with sub-items -->
                        <div>
                            <Link
                                :href="route('activity-log.index')"
                                class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                                :class="route().current('activity-log.index') ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)]' : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)]'"
                                @click="drawerOpen = false"
                            >
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                </svg>
                                {{ t('nav.activityLog') }}
                            </Link>
                            <!-- Sub-links -->
                            <div class="ml-8 mt-0.5 space-y-0.5">
                                <Link
                                    v-for="sub in [{ label: t('nav.activityTabReviews'), tab: 'reviews' }, { label: t('nav.activityTabComments'), tab: 'comments' }, { label: t('nav.activityTabNominations'), tab: 'nominations' }]"
                                    :key="sub.tab"
                                    :href="route('activity-log.index', { tab: sub.tab })"
                                    class="block px-3 py-1.5 rounded-lg text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-overlay)] transition-colors"
                                    @click="drawerOpen = false"
                                >{{ sub.label }}</Link>
                            </div>
                        </div>

                        <template v-if="auth.can?.accessAdmin">
                            <hr class="border-[var(--color-border)] my-2" />
                            <Link :href="route('admin.dashboard')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-[var(--color-accent)] hover:bg-[var(--color-accent-muted)] transition-colors" @click="drawerOpen = false">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
                                </svg>
                                {{ t('nav.admin') }}
                            </Link>
                        </template>
                    </template>
                    <template v-else>
                        <hr class="border-[var(--color-border)] my-2" />
                        <Link :href="route('login')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-overlay)] hover:text-[var(--color-text-primary)] transition-colors" @click="drawerOpen = false">{{ t('nav.login') }}</Link>
                        <Link :href="route('register')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-[var(--color-accent)] hover:bg-[var(--color-accent-muted)] transition-colors" @click="drawerOpen = false">{{ t('nav.register') }}</Link>
                    </template>
                </nav>

                <!-- Drawer footer -->
                <template v-if="auth.user">
                    <div class="px-3 pb-4 pt-2 border-t border-[var(--color-border)] space-y-1">
                        <div class="px-4 py-2">
                            <LanguageSwitcher />
                        </div>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-red-400 hover:bg-red-950/30 transition-colors"
                            @click="drawerOpen = false"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l3 3m0 0-3 3m3-3H2.25" />
                            </svg>
                            {{ t('nav.logout') }}
                        </Link>
                    </div>
                </template>
            </aside>
        </Transition>

        <!-- ── FLASH MESSAGES ── -->
        <div v-if="flash.success || flash.error" class="max-w-7xl mx-auto w-full px-4 sm:px-6 pt-4">
            <Alert v-if="flash.success" variant="success" dismissible>{{ flash.success }}</Alert>
            <Alert v-if="flash.error"   variant="danger"  dismissible>{{ flash.error }}</Alert>
        </div>

        <!-- ── MAIN CONTENT ── -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- ── FOOTER ── -->
        <footer class="border-t border-[var(--color-border)] mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <Link :href="route('home')" class="font-display font-black text-xl tracking-tight">
                    Cinema<span class="text-[var(--color-accent)]">DB</span>
                </Link>
                <p class="text-[var(--color-text-muted)] text-sm">
                    © 2026 CinemaDB — {{ t('nav.footerTagline') }}
                </p>
                <div class="flex items-center gap-4 text-sm text-[var(--color-text-muted)]">
                    <Link :href="route('titles.index')" class="hover:text-[var(--color-text-primary)] transition-colors">{{ t('titles.movie') }}</Link>
                    <span>·</span>
                    <Link :href="route('titles.index', { type: 'SERIES' })" class="hover:text-[var(--color-text-primary)] transition-colors">{{ t('titles.series') }}</Link>
                </div>
            </div>
            <!-- ── CONTACT ── -->
            <div class="border-t border-[var(--color-border)]">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-6 text-xs text-[var(--color-text-muted)]">
                    <span class="font-medium text-[var(--color-text-secondary)]">Lê Hải Đăng</span>
                    <span class="hidden sm:inline">·</span>
                    <a href="mailto:20233288@eaut.edu.vn" class="hover:text-[var(--color-accent)] transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        20233288@eaut.edu.vn
                    </a>
                    <span class="hidden sm:inline">·</span>
                    <a href="https://github.com/hoclaptrinh33/CinemaDB" target="_blank" rel="noopener noreferrer" class="hover:text-[var(--color-accent)] transition-colors flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2z"/></svg>
                        hoclaptrinh33/CinemaDB
                    </a>
                </div>
            </div>
        </footer>

        <!-- ── BADGE TOAST NOTIFICATIONS ── -->
        <div class="fixed bottom-[calc(1.5rem+env(safe-area-inset-bottom))] right-6 z-[200] flex flex-col gap-3 pointer-events-none">
            <TransitionGroup name="slide-toast" tag="div" class="flex flex-col gap-3">
                <div
                    v-for="toast in badgeToasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-start gap-3 p-4 rounded-2xl border shadow-2xl backdrop-blur-md w-72"
                    :class="tierColor[toast.badge_tier] ?? 'border-[var(--color-border)] bg-[var(--color-bg-elevated)]'"
                >
                    <div class="shrink-0 w-10 h-10 rounded-full border-2 flex items-center justify-center overflow-hidden"
                         :class="toast.badge_tier === 'WOOD' ? 'border-orange-800'
                             : toast.badge_tier === 'IRON' ? 'border-stone-500'
                             : toast.badge_tier === 'BRONZE' ? 'border-amber-600'
                             : toast.badge_tier === 'SILVER' ? 'border-slate-400'
                             : toast.badge_tier === 'GOLD'   ? 'border-yellow-400'
                             : toast.badge_tier === 'DIAMOND' ? 'border-sky-300'
                             :                                  'border-cyan-300'">
                        <img v-if="toast.icon_path" :src="toast.icon_path" :alt="toast.badge_name"
                             class="w-full h-full object-cover" @error="(e) => e.target.style.display='none'" />
                        <span v-else class="text-xl">🏅</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wider text-[var(--color-accent)]">Huy hiệu mới!</p>
                        <p class="font-semibold text-sm text-[var(--color-text-primary)] truncate">{{ toast.badge_name }}</p>
                        <p class="text-xs text-[var(--color-text-muted)] leading-snug mt-0.5">{{ toast.description }}</p>
                    </div>
                    <button class="shrink-0 text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors" @click="dismissToast(toast.id)">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>

<style scoped>
.slide-toast-enter-active,
.slide-toast-leave-active {
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-toast-enter-from,
.slide-toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}

/* Notification panel pop */
.pop-enter-active,
.pop-leave-active {
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: top right;
}
.pop-enter-from,
.pop-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(-4px);
}

/* Drawer slide-in from right */
.slide-drawer-enter-active,
.slide-drawer-leave-active {
    transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-drawer-enter-from,
.slide-drawer-leave-to {
    transform: translateX(100%);
}

/* Overlay fade */
.fade-overlay-enter-active,
.fade-overlay-leave-active {
    transition: opacity 0.25s ease;
}
.fade-overlay-enter-from,
.fade-overlay-leave-to {
    opacity: 0;
}
</style>



