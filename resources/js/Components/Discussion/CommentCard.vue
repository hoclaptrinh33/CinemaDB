<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import CommentForm from './CommentForm.vue';
import RankBadge from '@/Components/User/RankBadge.vue';
import { getLevel, getRank } from '@/composables/useRank.js';

const props = defineProps({
    comment: { type: Object, required: true },
    titleId: { type: [Number, String], required: true },
    depth:   { type: Number, default: 0 },
    storeUrl:         { type: String, default: null },
    likeRouteName:    { type: String, default: 'comments.like' },
    destroyRouteName: { type: String, default: 'comments.destroy' },
});

const { auth } = usePage().props;
const showReplyForm = ref(false);
const showReplies   = ref(true);
const likeForm      = useForm({});

const isLiked = computed(() => !!props.comment.liked_by_user);
const replies = computed(() => props.comment.replies ?? []);

function toggleLike() {
    likeForm.post(route(props.likeRouteName, props.comment.comment_id), { preserveScroll: true });
}

function deleteComment() {
    if (!confirm('Xoá bình luận này?')) return;
    useForm({}).delete(route(props.destroyRouteName, props.comment.comment_id), { preserveScroll: true });
}

function formatDate(d) {
    const date = new Date(d);
    const now  = new Date();
    const diff = (now - date) / 1000;

    if (diff < 60)   return 'vừa xong';
    if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`;
    if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} ngày trước`;
    return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

const canDelete = computed(() =>
    auth.user && (auth.user.id === props.comment.user_id || ['ADMIN', 'MODERATOR'].includes(auth.user.role))
);
const canHide = computed(() =>
    auth.user && ['ADMIN', 'MODERATOR'].includes(auth.user.role)
);
const commentUserRank = computed(() => getRank(props.comment.user?.reputation ?? 0));
const commentUserLevel = computed(() => getLevel(props.comment.user?.reputation ?? 0));
</script>

<template>
    <div :class="['comment-card', depth > 0 && 'ml-8 border-l-2 border-border pl-4']">
        <div class="flex gap-3">
            <!-- Avatar -->
            <Link
                v-if="comment.user?.username"
                :href="route('users.show', comment.user.username)"
                class="shrink-0 w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold font-display overflow-hidden no-underline hover:opacity-80 transition-opacity"
                :class="[commentUserRank.bgColor, commentUserRank.borderColor, commentUserRank.color]"
            >
                {{ comment.user.username[0].toUpperCase() }}
            </Link>
            <div v-else
                 class="shrink-0 w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold font-display overflow-hidden"
                 :class="[commentUserRank.bgColor, commentUserRank.borderColor, commentUserRank.color]">
                ?
            </div>

            <div class="flex-1 min-w-0">
                <!-- Header -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <Link
                        v-if="comment.user?.username"
                        :href="route('users.show', comment.user.username)"
                        class="font-display font-bold text-sm hover:underline"
                        :class="[commentUserRank.color, commentUserRank.textGlow]"
                    >
                        {{ comment.user.username }}
                    </Link>
                    <span v-else class="font-display font-bold text-sm"
                          :class="[commentUserRank.color, commentUserRank.textGlow]">
                        Ẩn danh
                    </span>
                    <RankBadge :reputation="comment.user?.reputation ?? 0" size="sm" />
                    <span class="text-[10px] font-mono uppercase tracking-wider text-text-muted">Lv {{ commentUserLevel.level }}</span>
                    <span class="text-xs text-text-muted font-mono">
                        {{ formatDate(comment.created_at) }}
                    </span>
                    <span v-if="comment.is_hidden" class="text-xs text-amber-400 font-semibold">[Đã ẩn]</span>
                </div>

                <!-- Content -->
                <div class="mt-1">
                    <!-- GIF -->
                    <img
                        v-if="comment.content_type === 'gif' && comment.gif_url"
                        :src="comment.gif_url"
                        :alt="comment.content"
                        class="max-h-40 rounded-lg border border-border mt-1"
                        loading="lazy"
                    />
                    <!-- Text / Emoji -->
                    <p v-else class="text-sm text-text-secondary leading-relaxed whitespace-pre-wrap wrap-break-word">
                        {{ comment.content }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 mt-2">
                    <!-- Like -->
                    <button
                        v-if="auth.user"
                        type="button"
                        class="flex items-center gap-1 text-xs transition-colors"
                        :class="isLiked ? 'text-accent' : 'text-text-muted hover:text-accent'"
                        @click="toggleLike"
                    >
                        <svg class="w-3.5 h-3.5" :fill="isLiked ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                        </svg>
                        {{ comment.like_count || '' }}
                    </button>

                    <!-- Reply (only depth 0) -->
                    <button
                        v-if="depth === 0 && auth.user"
                        type="button"
                        class="text-xs text-text-muted hover:text-text-primary transition-colors"
                        @click="showReplyForm = !showReplyForm"
                    >
                        {{ showReplyForm ? 'Huỷ' : 'Phản hồi' }}
                    </button>

                    <!-- Replies count toggle -->
                    <button
                        v-if="depth === 0 && replies.length"
                        type="button"
                        class="text-xs text-text-muted hover:text-text-primary transition-colors"
                        @click="showReplies = !showReplies"
                    >
                        {{ showReplies ? '▾' : '▸' }} {{ replies.length }} phản hồi
                    </button>

                    <!-- Delete -->
                    <button
                        v-if="canDelete"
                        type="button"
                        class="text-xs text-text-muted hover:text-red-400 transition-colors ml-auto"
                        @click="deleteComment"
                    >Xoá</button>
                </div>

                <!-- Reply form -->
                <div v-if="showReplyForm && depth === 0" class="mt-3">
                    <CommentForm
                        :title-id="titleId"
                        :parent-id="comment.comment_id"
                        :on-cancel="() => showReplyForm = false"
                        :store-url="storeUrl"
                    />
                </div>

                <!-- Replies -->
                <div v-if="depth === 0 && replies.length && showReplies" class="mt-3 space-y-3">
                    <CommentCard
                        v-for="reply in replies"
                        :key="reply.comment_id"
                        :comment="reply"
                        :title-id="titleId"
                        :depth="1"
                        :store-url="storeUrl"
                        :like-route-name="likeRouteName"
                        :destroy-route-name="destroyRouteName"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
