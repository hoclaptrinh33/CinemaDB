<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import CommentCard from './CommentCard.vue';
import CommentForm from './CommentForm.vue';
import Spinner from '@/Components/UI/Spinner.vue';

const props = defineProps({
    comments: { type: Object, required: true }, // Paginated object
    titleId:  { type: [Number, String], required: true },
    canComment: { type: Boolean, default: false },
    storeUrl:         { type: String, default: null },
    likeRouteName:    { type: String, default: 'comments.like' },
    destroyRouteName: { type: String, default: 'comments.destroy' },
});

const loadingMore = ref(false);

function loadMore() {
    if (!props.comments.next_page_url || loadingMore.value) return;
    loadingMore.value = true;
    router.get(
        props.comments.next_page_url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => { loadingMore.value = false; },
        }
    );
}
</script>

<template>
    <div class="space-y-4">
        <!-- Write comment -->
        <div v-if="canComment" class="card p-4">
            <CommentForm :title-id="titleId" :store-url="storeUrl" />
        </div>

        <!-- Comment list -->
        <div v-if="comments.data?.length" class="space-y-3">
            <CommentCard
                v-for="comment in comments.data"
                :key="comment.comment_id"
                :comment="comment"
                :title-id="titleId"
                :store-url="storeUrl"
                :like-route-name="likeRouteName"
                :destroy-route-name="destroyRouteName"
            />
        </div>

        <p v-else-if="!canComment" class="text-[var(--color-text-muted)] text-sm text-center py-6">
            Chưa có bình luận nào. Đăng nhập để bắt đầu thảo luận.
        </p>

        <!-- Load more -->
        <div v-if="comments.next_page_url" class="text-center pt-2">
            <button
                type="button"
                class="px-6 py-2 rounded-lg border border-[var(--color-border)] text-sm text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-border-light)] transition-colors"
                :disabled="loadingMore"
                @click="loadMore"
            >
                <span v-if="loadingMore" class="flex items-center gap-2">
                    <Spinner size="sm" /> Đang tải...
                </span>
                <span v-else>Xem thêm ({{ comments.total - comments.to }} bình luận)</span>
            </button>
        </div>
    </div>
</template>
