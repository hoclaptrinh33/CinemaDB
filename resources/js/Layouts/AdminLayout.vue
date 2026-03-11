<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Alert from '@/Components/UI/Alert.vue';
import AdminSearchBar from '@/Components/UI/AdminSearchBar.vue';

const page  = usePage();
const flash = computed(() => page.props.flash);
const auth  = computed(() => page.props.auth);

const sidebarOpen = ref(false);

const navGroups = [
    {
        label: 'Tổng quan',
        links: [
            { label: 'Dashboard', route: 'admin.dashboard', icon: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25' },
        ],
    },
    {
        label: 'Nội dung',
        links: [
            { label: 'Titles', route: 'admin.titles.index', icon: 'M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75.125v-1.5c0-.621.504-1.125 1.125-1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125' },
            { label: 'Persons', route: 'admin.persons.index', icon: 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z' },
            { label: 'Studios', route: 'admin.studios.index', icon: 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21' },
        ],
    },
    {
        label: 'Danh mục',
        links: [
            { label: 'Quốc gia', route: 'admin.countries.index', icon: 'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253M3 12a8.959 8.959 0 0 0 .284 2.253' },
            { label: 'Ngôn ngữ', route: 'admin.languages.index', icon: 'M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802' },
            { label: 'Thể loại', route: 'admin.genres.index', icon: 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Zm-1.396 8.25a1.125 1.125 0 1 1-2.25 0 1.125 1.125 0 0 1 2.25 0Z' },
            { label: 'Vai trò phim', route: 'admin.roles.index', icon: 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z' },
        ],
    },
    {
        label: 'Cộng đồng',
        links: [
            { label: 'Users', route: 'admin.users.index', icon: 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z' },
            { label: 'Reviews', route: 'admin.reviews.index', icon: 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.628 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z' },
            { label: 'Huy hiệu', route: 'admin.badges.index', icon: 'M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0' },
        ],
    },
    {
        label: 'Hệ thống',
        links: [
            { label: 'TMDB Import', route: 'admin.tmdb-import.index', icon: 'M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3' },
            { label: 'Audit Log', route: 'admin.audit-logs.index', icon: 'M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z' },
        ],
    },
];

function isActive(routeName) {
    return route().current(routeName) || route().current(routeName + '.*');
}
</script>

<template>
    <div class="min-h-screen flex">
        <!-- ── SIDEBAR ── -->
        <!-- Overlay (mobile) -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/60 md:hidden"
            @click="sidebarOpen = false"
        />

        <aside
            :class="[
                'fixed top-0 left-0 h-full z-50 w-60 bg-[var(--color-bg-surface)] border-r border-[var(--color-border)] flex flex-col transition-transform duration-300',
                'md:translate-x-0 md:static md:z-auto',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <!-- Logo -->
            <div class="h-16 flex items-center px-5 border-b border-[var(--color-border)] shrink-0">
                <Link :href="route('admin.dashboard')" class="font-display font-black text-lg tracking-tight">
                    Cinema<span class="text-[var(--color-accent)]">DB</span>
                    <span class="ml-1.5 badge !bg-[var(--color-accent-muted)] !text-[var(--color-accent)] text-[10px] align-middle">Admin</span>
                </Link>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
                <div v-for="group in navGroups" :key="group.label">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--color-text-muted)] px-2 mb-1.5">
                        {{ group.label }}
                    </p>
                    <ul class="space-y-0.5">
                        <li v-for="link in group.links" :key="link.route">
                            <Link
                                :href="route(link.route)"
                                :class="[
                                    'sidebar-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150',
                                    isActive(link.route)
                                        ? 'active'
                                        : 'text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)] hover:text-[var(--color-text-primary)]',
                                ]"
                                @click="sidebarOpen = false"
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="link.icon" />
                                </svg>
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- User zone -->
            <div class="border-t border-[var(--color-border)] p-3 shrink-0">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-8 h-8 rounded-full bg-[var(--color-accent)] flex items-center justify-center text-white text-sm font-bold font-display shrink-0">
                        {{ auth.user?.username[0].toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-[var(--color-text-primary)] truncate">{{ auth.user?.username }}</p>
                        <p class="text-xs text-[var(--color-text-muted)] truncate">{{ auth.user?.role }}</p>
                    </div>
                </div>
                <div class="mt-1 space-y-0.5">
                    <Link :href="route('home')" class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-elevated)] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Về trang chủ
                    </Link>
                    <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs text-red-400 hover:bg-red-950/30 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>
                        Đăng xuất
                    </Link>
                </div>
            </div>
        </aside>

        <!-- ── MAIN AREA ── -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
        <header class="h-14 border-b border-[var(--color-border)] bg-[var(--color-bg-surface)] flex items-center px-4 sm:px-6 gap-4 shrink-0 sticky top-0 z-30">
            <!-- Hamburger (mobile) -->
            <button class="md:hidden btn btn-ghost !px-2 !py-2" @click="sidebarOpen = true">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <!-- Global admin search -->
            <AdminSearchBar class="hidden sm:block" />

            <div class="flex-1" />

            <!-- Current user pill -->
            <div class="flex items-center gap-2 shrink-0">
                <div class="w-6 h-6 rounded-full bg-[var(--color-accent)] flex items-center justify-center text-white text-[10px] font-bold font-display shrink-0">
                    {{ auth.user?.username?.[0]?.toUpperCase() }}
                </div>
                <p class="hidden sm:block text-xs text-[var(--color-text-muted)] font-mono">{{ auth.user?.username }}</p>
            </div>
        </header>

            <!-- Flash -->
            <div v-if="flash.success || flash.error" class="px-4 sm:px-6 pt-4">
                <Alert v-if="flash.success" variant="success" dismissible>{{ flash.success }}</Alert>
                <Alert v-if="flash.error"   variant="danger"  dismissible>{{ flash.error }}</Alert>
            </div>

            <!-- Content -->
            <main class="flex-1 p-4 sm:p-6 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
