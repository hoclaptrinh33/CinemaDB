<script setup>
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import StarRating from '@/Components/Form/StarRating.vue';
import Textarea from '@/Components/Form/Textarea.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    titleId:    { type: Number, required: true },
    titleSlug:  { type: String, required: true },
    userReview: { type: Object, default: null },
});

const { t } = useI18n();

const form = useForm({
    rating:       props.userReview?.rating      ?? null,
    review_text:  props.userReview?.review_text ?? '',
    has_spoilers: props.userReview?.has_spoilers ?? false,
});

function submit() {
    if (props.userReview) {
        form.patch(route('reviews.update', props.userReview.review_id ?? props.userReview.id), { preserveScroll: true });
    } else {
        form.post(route('reviews.store', props.titleSlug), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }
}
</script>

<template>
    <div class="card p-5 space-y-4">
        <h3 class="font-display font-bold text-base text-[var(--color-text-primary)]">
            {{ userReview ? $t('show.editReview') : $t('show.writeReview') }}
        </h3>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Star rating -->
            <StarRating
                v-model="form.rating"
                :label="$t('show.ratingLabel')"
                :error="form.errors.rating"
            />

            <!-- Review text -->
            <Textarea
                v-model="form.review_text"
                :label="$t('show.commentLabel')"
                :placeholder="$t('show.commentPlaceholder')"
                :rows="4"
                :error="form.errors.review_text"
            />

            <!-- Spoiler checkbox -->
            <label class="flex items-center gap-3 cursor-pointer group">
                <div class="relative">
                    <input
                        v-model="form.has_spoilers"
                        type="checkbox"
                        class="sr-only peer"
                    />
                    <div class="w-9 h-5 rounded-full border border-[var(--color-border)] bg-[var(--color-bg-elevated)] transition-colors peer-checked:bg-[var(--color-accent)] peer-checked:border-[var(--color-accent)]" />
                    <div class="absolute left-0.5 top-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4" />
                </div>
                <span class="text-sm text-[var(--color-text-secondary)] group-hover:text-[var(--color-text-primary)] transition-colors">
                    {{ $t('show.containsSpoiler') }}
                </span>
            </label>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3">
                <p v-if="form.errors.review_text || form.errors.rating" class="text-xs text-red-400 mr-auto">
                    {{ form.errors.review_text || form.errors.rating }}
                </p>
                <Button
                    variant="primary"
                    type="submit"
                    :loading="form.processing"
                    :disabled="!form.rating && !form.review_text"
                >
                    {{ userReview ? $t('show.saveChanges') : $t('show.submit') }}
                </Button>
            </div>
        </form>
    </div>
</template>
