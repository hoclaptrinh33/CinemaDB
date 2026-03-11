<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import RankBadge from '@/Components/User/RankBadge.vue';
import { getLevel, getRank } from '@/composables/useRank.js';

const props = defineProps({
    review:    { type: Object, required: true },
    canDelete: { type: Boolean, default: false },
    canHide:   { type: Boolean, default: false },
});

const spoilerRevealed = ref(false);
const showFull        = ref(false);
const { auth }        = usePage().props;

const reviewId = computed(() => props.review.review_id ?? props.review.id);
const hasSpoilers = computed(() => Boolean(props.review.has_spoilers ?? props.review.contains_spoiler));
const helpfulCount = computed(() => props.review.helpful_votes ?? props.review.helpful_count ?? 0);
const userRank = computed(() => getRank(props.review.user?.reputation ?? 0));
const userLevel = computed(() => getLevel(props.review.user?.reputation ?? 0));

const MAX_LEN = 300;
const isLong  = (props.review.review_text?.length ?? 0) > MAX_LEN;

const likeForm = useForm({});
function toggleHelpful() {
    likeForm.post(route('reviews.helpful', reviewId.value), { preserveScroll: true });
}

function deleteReview() {
    if (!confirm('Xoá đánh giá này?')) return;
    useForm({}).delete(route('reviews.destroy', reviewId.value), { preserveScroll: true });
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
</script>

<template>
    <div class="card p-4 space-y-3">
        <!-- Header -->
        <div class="flex items-start justify-between gap-3">
            <!-- User info -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('users.show', review.user.username)"
                    class="w-9 h-9 rounded-full overflow-hidden bg-bg-elevated border-2 shrink-0 block hover:opacity-80 transition-opacity"
                    :class="userRank.borderColor"
                >
                    <img
                        v-if="review.user.avatar"
                        :src="review.user.avatar"
                        :alt="review.user.username"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center text-sm font-bold font-display"
                         :class="userRank.color">
                        {{ review.user.username[0].toUpperCase() }}
                    </div>
                </Link>
                <div>
                    <div class="flex items-center gap-1.5">
                        <Link
                            :href="route('users.show', review.user.username)"
                            class="font-display font-bold text-sm hover:underline"
                            :class="[userRank.color, userRank.textGlow]"
                        >
                            {{ review.user.username }}
                        </Link>
                        <RankBadge :reputation="review.user.reputation ?? 0" size="sm" />
                        <span class="text-xs font-mono uppercase tracking-wider text-text-muted">Lv {{ userLevel.level }}</span>
                    </div>
                    <p class="text-xs text-text-muted font-mono">
                        {{ formatDate(review.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Rating + badges -->
            <div class="flex items-center gap-2 shrink-0">
                <Badge v-if="hasSpoilers && !spoilerRevealed" variant="warning">Spoiler</Badge>
                <Badge v-if="review.moderation_status === 'HIDDEN'" variant="danger">Ẩn</Badge>
                <div v-if="review.rating" class="flex items-center gap-1 bg-gold-muted rounded-md px-2 py-1">
                    <svg class="w-3.5 h-3.5 star-filled" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2l2.9 6.1L22 9.3l-5 5 1.2 7L12 18l-6.2 3.3 1.2-7-5-5z" />
                    </svg>
                    <span class="font-display font-bold text-gold text-sm">{{ review.rating }}</span>
                </div>
            </div>
        </div>

        <!-- Spoiler overlay -->
        <div v-if="hasSpoilers && !spoilerRevealed" class="py-3">
            <button
                class="flex items-center gap-2 text-sm text-text-muted hover:text-accent transition-colors group"
                @click="spoilerRevealed = true"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
                Nội dung có spoiler — nhấp để hiện
            </button>
        </div>

        <!-- Review text -->
        <div v-else-if="review.review_text" class="text-text-secondary text-sm leading-relaxed">
            <p>
                {{ showFull || !isLong ? review.review_text : review.review_text.slice(0, MAX_LEN) + '…' }}
            </p>
            <button
                v-if="isLong"
                class="text-xs text-accent hover:underline mt-1"
                @click="showFull = !showFull"
            >
                {{ showFull ? 'Thu gọn' : 'Xem thêm' }}
            </button>
        </div>

        <!-- Footer actions -->
        <div class="flex items-center justify-between pt-1">
            <!-- Helpful vote -->
            <button
                v-if="auth.user && auth.user.id !== review.user.id"
                :class="[
                    'flex items-center gap-1.5 text-xs transition-colors',
                    review.voted_helpful
                        ? 'text-accent'
                        : 'text-text-muted hover:text-text-primary',
                ]"
                :disabled="likeForm.processing"
                @click="toggleHelpful"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                </svg>
                Hữu ích ({{ helpfulCount }})
            </button>
            <span v-else class="text-xs text-text-muted">{{ helpfulCount }} người thấy hữu ích</span>

            <!-- Delete (owner or admin) -->
            <button
                v-if="canDelete || (auth.user && auth.user.id === review.user.id)"
                class="text-xs text-text-muted hover:text-red-400 transition-colors"
                @click="deleteReview"
            >
                Xoá
            </button>
        </div>
    </div>
</template>
