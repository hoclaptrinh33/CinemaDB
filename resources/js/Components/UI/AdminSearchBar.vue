<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';

const query       = ref('');
const loading     = ref(false);
const showDropdown = ref(false);
const results     = ref([]);

const TYPE_LABEL = { MOVIE: 'Phim', SERIES: 'Series', EPISODE: 'Tập' };
const TYPE_COLOR = {
    MOVIE:   'text-blue-400',
    SERIES:  'text-purple-400',
    EPISODE: 'text-teal-400',
};

const search = useDebounceFn(async () => {
    const q = query.value.trim();

    if (q.length < 2) {
        results.value   = [];
        showDropdown.value = false;
        return;
    }

    loading.value = true;

    try {
        const { data } = await axios.get('/api/search', {
            params: { q, limit: 8 },
        });

        results.value   = data.results ?? [];
        showDropdown.value = true;
    } catch {
        results.value   = [];
        showDropdown.value = false;
    } finally {
        loading.value = false;
    }
}, 200);

watch(query, search);

function closeDropdownLater() {
    setTimeout(() => {
        showDropdown.value = false;
    }, 150);
}

function clearSearch() {
    query.value    = '';
    results.value  = [];
    showDropdown.value = false;
}

function goToEdit(item) {
    clearSearch();
    router.visit(route('admin.titles.edit', item.slug));
}

function goToIndex(event) {
    if (!query.value.trim()) return;
    showDropdown.value = false;
    router.visit(route('admin.titles.index', { search: query.value.trim() }));
    clearSearch();
    event.preventDefault();
}

onBeforeUnmount(() => {
    search.cancel();
});
</script>

<template>
    <div class="relative w-full max-w-xs" @focusin="showDropdown = results.length > 0" @focusout="closeDropdownLater">
        <!-- Input -->
        <form @submit="goToIndex">
            <div class="relative">
                <svg
                    class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[var(--color-text-muted)] pointer-events-none"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>

                <input
                    v-model="query"
                    type="search"
                    placeholder="Tìm nhanh title..."
                    class="input-base w-full !pl-8 !pr-8 !py-1.5 !text-sm"
                    @focus="showDropdown = results.length > 0"
                />

                <!-- Loading spinner / clear -->
                <div class="absolute right-2 top-1/2 -translate-y-1/2">
                    <svg v-if="loading" class="w-3.5 h-3.5 animate-spin text-[var(--color-text-muted)]" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <button
                        v-else-if="query"
                        type="button"
                        class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors"
                        @click="clearSearch"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </form>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition-all duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1 scale-98"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-1 scale-98"
        >
            <div
                v-if="showDropdown"
                class="absolute top-full left-0 right-0 mt-1.5 bg-[var(--color-bg-surface)] border border-[var(--color-border)] rounded-xl shadow-2xl z-[60] overflow-hidden"
                style="min-width: 320px;"
            >
                <!-- Results list -->
                <div v-if="results.length > 0" class="py-1.5">
                    <button
                        v-for="item in results"
                        :key="item.id"
                        type="button"
                        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-[var(--color-bg-elevated)] transition-colors text-left group"
                        @click="goToEdit(item)"
                    >
                        <!-- Poster thumbnail -->
                        <div class="w-8 h-11 shrink-0 rounded overflow-hidden bg-[var(--color-bg-elevated)]">
                            <img
                                v-if="item.poster_url"
                                :src="item.poster_url"
                                :alt="item.name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-[var(--color-text-primary)] truncate group-hover:text-[var(--color-accent)] transition-colors">
                                {{ item.name }}
                            </p>
                            <p v-if="item.name_vi" class="text-xs text-[var(--color-text-muted)] truncate">
                                {{ item.name_vi }}
                            </p>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-[10px] font-semibold" :class="TYPE_COLOR[item.type] ?? 'text-text-muted'">
                                    {{ TYPE_LABEL[item.type] ?? item.type }}
                                </span>
                                <span class="text-[var(--color-border)]">·</span>
                                <span class="text-[10px] text-[var(--color-text-muted)]">{{ item.year ?? '?' }}</span>
                                <span v-if="item.rating" class="text-[var(--color-border)]">·</span>
                                <span v-if="item.rating" class="text-[10px] text-[var(--color-gold)]">
                                    ★ {{ Number(item.rating).toFixed(1) }}
                                </span>
                            </div>
                        </div>

                        <!-- Edit arrow -->
                        <svg class="w-4 h-4 shrink-0 text-[var(--color-text-muted)] opacity-0 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                        </svg>
                    </button>
                </div>

                <!-- Empty state -->
                <div
                    v-else-if="query.trim().length >= 2 && !loading"
                    class="px-4 py-5 text-center text-sm text-[var(--color-text-muted)]"
                >
                    Không tìm thấy kết quả nào
                </div>

                <!-- Footer: link to full search -->
                <div v-if="results.length > 0" class="border-t border-[var(--color-border)] px-3 py-1.5">
                    <button
                        type="button"
                        class="text-xs text-[var(--color-accent)] hover:underline transition-colors"
                        @click="goToIndex($event)"
                    >
                        Xem tất cả kết quả cho "{{ query }}" →
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
