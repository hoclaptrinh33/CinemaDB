<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    titleId:     { type: [Number, String], required: true },
    nominations: { type: Object, required: true },
    // { count: Number, userNominated: Boolean, dailyLeft: Number }
});

const { auth } = usePage().props;
const nominateForm = useForm({});

const isLoading = computed(() => nominateForm.processing);

function toggleNominate() {
    if (!auth.user) {
        window.location.href = route('login');
        return;
    }

    if (props.nominations.userNominated) {
        nominateForm.delete(route('nominations.unnominate', props.titleId), {
            preserveScroll: true,
        });
    } else {
        if (props.nominations.dailyLeft <= 0) return;
        nominateForm.post(route('nominations.nominate', props.titleId), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="flex items-center gap-2">
        <button
            type="button"
            :disabled="isLoading || (!nominations.userNominated && nominations.dailyLeft <= 0 && !!auth.user)"
            :class="[
                'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold transition-all',
                nominations.userNominated
                    ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40 hover:bg-amber-500/30'
                    : 'bg-[var(--color-bg-elevated)] text-[var(--color-text-muted)] border border-[var(--color-border)] hover:border-amber-500/40 hover:text-amber-400',
                ((!nominations.userNominated && nominations.dailyLeft <= 0 && !!auth.user) || isLoading) && 'opacity-50 cursor-not-allowed',
            ]"
            @click="toggleNominate"
        >
            <!-- Trophy icon -->
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
            <span>{{ nominations.count.toLocaleString('vi-VN') }}</span>
        </button>

        <!-- Daily remaining badge -->
        <span
            v-if="auth.user && !nominations.userNominated"
            :class="[
                'text-xs px-2 py-0.5 rounded-full font-mono',
                nominations.dailyLeft > 0
                    ? 'bg-green-950/60 text-green-400'
                    : 'bg-[var(--color-bg-elevated)] text-[var(--color-text-muted)]',
            ]"
        >
            {{ nominations.dailyLeft }}/3 hôm nay
        </span>
        <span
            v-else-if="auth.user && nominations.userNominated"
            class="text-xs text-amber-400"
        >
            Đã đề cử ✓
        </span>
    </div>
</template>
