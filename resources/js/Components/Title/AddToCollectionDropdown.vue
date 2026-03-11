<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    titleId:         { type: [Number, String], required: true },
    userCollections: { type: Array, default: () => [] },
});

const { auth } = usePage().props;
const open        = ref(false);
const submitting  = ref(null); // collection_id being toggled

// How many collections contain this title
const addedCount = computed(() => props.userCollections.filter(c => c.has_title).length);

// Close dropdown when clicking outside
const containerRef = ref(null);
function handleOutsideClick(e) {
    if (open.value && containerRef.value && !containerRef.value.contains(e.target)) {
        open.value = false;
    }
}
onMounted(() => document.addEventListener('click', handleOutsideClick));
onUnmounted(() => document.removeEventListener('click', handleOutsideClick));

function toggle(collection) {
    if (!auth.user) {
        window.location.href = route('login');
        return;
    }
    submitting.value = collection.collection_id;

    if (collection.has_title) {
        router.delete(route('collections.titles.remove', [collection.slug, props.titleId]), {
            preserveScroll: true,
            onFinish: () => { submitting.value = null; },
        });
    } else {
        router.post(route('collections.titles.add', collection.slug), {
            title_id: props.titleId,
        }, {
            preserveScroll: true,
            onFinish: () => { submitting.value = null; },
        });
    }
}

function openCreate() {
    open.value = false;
    router.get(route('collections.create'));
}
</script>

<template>
    <div ref="containerRef" class="relative">
        <!-- Trigger button -->
        <button
            type="button"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-colors"
            :class="addedCount > 0
                ? 'bg-[var(--color-accent-muted)] text-[var(--color-accent)] border-[var(--color-accent)]/40 hover:bg-[var(--color-accent-muted)]/80'
                : 'bg-[var(--color-bg-elevated)] text-[var(--color-text-primary)] border-[var(--color-border)] hover:border-[var(--color-accent)]/60'"
            @click="open = !open"
        >
            <!-- Bookmark icon -->
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path v-if="addedCount === 0" stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
            </svg>
            <span>
                {{ addedCount > 0 ? `Trong ${addedCount} bộ sưu tập` : 'Thêm vào bộ sưu tập' }}
            </span>
            <svg class="w-3.5 h-3.5 ml-0.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute left-0 top-full mt-1 w-64 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-surface)] shadow-xl z-20 overflow-hidden origin-top-left"
            >
                <!-- Not logged in -->
                <div v-if="!auth.user" class="px-4 py-3 text-sm text-[var(--color-text-muted)]">
                    <a :href="route('login')" class="text-[var(--color-accent)] hover:underline font-medium">Đăng nhập</a>
                    để thêm vào bộ sưu tập.
                </div>

                <!-- Collections list -->
                <template v-else>
                    <div v-if="userCollections.length === 0" class="px-4 py-3 text-sm text-[var(--color-text-muted)] italic">
                        Bạn chưa có bộ sưu tập nào.
                    </div>
                    <div v-else class="max-h-60 overflow-y-auto py-1">
                        <button
                            v-for="col in userCollections"
                            :key="col.collection_id"
                            type="button"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-[var(--color-bg-elevated)] transition-colors text-left"
                            :disabled="submitting === col.collection_id"
                            @click="toggle(col)"
                        >
                            <!-- Checkbox indicator -->
                            <span
                                class="shrink-0 w-4 h-4 rounded border flex items-center justify-center transition-colors"
                                :class="col.has_title
                                    ? 'bg-[var(--color-accent)] border-[var(--color-accent)]'
                                    : 'border-[var(--color-border)]'"
                            >
                                <svg v-if="col.has_title" class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>

                            <span class="flex-1 truncate" :class="col.has_title ? 'text-[var(--color-text-primary)] font-medium' : 'text-[var(--color-text-secondary)]'">
                                {{ col.name }}
                            </span>
                            <span class="shrink-0 text-xs text-[var(--color-text-muted)]">{{ col.titles_count }}</span>

                            <!-- Visibility badge -->
                            <span v-if="col.visibility === 'PRIVATE'" class="shrink-0 text-xs text-[var(--color-text-muted)]">🔒</span>
                        </button>
                    </div>

                    <div class="border-t border-[var(--color-border)]">
                        <a
                            :href="route('collections.create')"
                            class="flex items-center gap-2 px-4 py-2.5 text-sm text-[var(--color-accent)] hover:bg-[var(--color-bg-elevated)] transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Tạo bộ sưu tập mới
                        </a>
                    </div>
                </template>
            </div>
        </Transition>
    </div>
</template>
