<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import axios from 'axios';

const query = ref('');
const loading = ref(false);
const showDropdown = ref(false);
const results = ref([]);

const search = useDebounceFn(async () => {
    const q = query.value.trim();

    if (q.length < 2) {
        results.value = [];
        showDropdown.value = false;
        return;
    }

    loading.value = true;

    try {
        const { data } = await axios.get('/api/search', {
            params: {
                q,
                limit: 10,
            },
        });

        results.value = data.results ?? [];
        showDropdown.value = true;
    } catch {
        results.value = [];
        showDropdown.value = false;
    } finally {
        loading.value = false;
    }
}, 200);

watch(query, search);

function closeDropdownLater() {
    setTimeout(() => {
        showDropdown.value = false;
    }, 120);
}

function clearSearch() {
    query.value = '';
    results.value = [];
    showDropdown.value = false;
}

onBeforeUnmount(() => {
    search.cancel();
});
</script>

<template>
    <div class="relative w-full" @focusin="showDropdown = true" @focusout="closeDropdownLater">
        <input
            v-model="query"
            type="search"
            :placeholder="$t('titles.searchPlaceholder')"
            class="input-base w-full pr-20"
        />

        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
            <button
                v-if="query"
                type="button"
                class="btn btn-ghost px-2! py-1! text-xs"
                @click="clearSearch"
            >
                Clear
            </button>
            <span v-if="loading" class="text-xs text-text-muted">...</span>
        </div>

        <div
            v-if="showDropdown"
            class="absolute top-full left-0 right-0 mt-1 card p-2 z-50 max-h-96 overflow-y-auto"
        >
            <div v-if="loading" class="px-3 py-6 text-center text-sm text-text-muted">
                {{ $t('titles.search') }}...
            </div>

            <div
                v-else-if="query.trim().length >= 2 && results.length === 0"
                class="px-3 py-6 text-center text-sm text-text-muted"
            >
                {{ $t('titles.noResults') }}
            </div>

            <div v-else class="space-y-1">
                <Link
                    v-for="item in results"
                    :key="item.id"
                    :href="route('titles.show', item.slug)"
                    class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-bg-overlay transition-colors"
                    @click="clearSearch"
                >
                    <img
                        :src="item.poster_url"
                        :alt="item.name"
                        class="w-10 h-14 rounded object-cover bg-bg-overlay"
                    />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-text-primary truncate">
                            {{ item.name }}
                        </p>
                        <p
                            v-if="item.name_vi"
                            class="text-xs text-text-muted truncate"
                        >
                            {{ item.name_vi }}
                        </p>
                        <p class="text-xs text-text-muted mt-0.5">
                            {{ item.type }} • {{ item.year ?? 'N/A' }} • ★ {{ Number(item.rating ?? 0).toFixed(1) }}
                        </p>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
