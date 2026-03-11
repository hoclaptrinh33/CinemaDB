<script setup>
import ReviewCard from './ReviewCard.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    reviews:          { type: Object, required: true }, // Paginated<ReviewItem>
    fallbackReviews:  { type: Array, default: () => [] },
    canAdmin:         { type: Boolean, default: false },
});
</script>

<template>
    <div class="space-y-4">
        <!-- Empty state -->
        <div v-if="!reviews.data.length && !fallbackReviews.length" class="card p-10 flex flex-col items-center gap-3 text-center">
            <svg class="w-10 h-10 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
            </svg>
            <p class="text-[var(--color-text-muted)] text-sm">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        </div>

        <div v-if="!reviews.data.length && fallbackReviews.length" class="space-y-3">
            <div class="card p-4 text-sm text-[var(--color-text-muted)]">
                Chưa có đánh giá cho phim này. Gợi ý từ cộng đồng: Top 3 đánh giá hữu ích nhất.
            </div>
            <ReviewCard
                v-for="review in fallbackReviews"
                :key="review.review_id ?? review.id"
                :review="review"
                :can-delete="canAdmin"
            />
        </div>

        <!-- Review cards -->
        <ReviewCard
            v-for="review in reviews.data"
            :key="review.review_id ?? review.id"
            :review="review"
            :can-delete="canAdmin"
        />

        <!-- Pagination -->
        <Pagination v-if="reviews.last_page > 1" :meta="reviews" class="mt-4" />
    </div>
</template>
