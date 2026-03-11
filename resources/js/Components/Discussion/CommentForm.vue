<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    titleId:  { type: [Number, String], required: true },
    parentId: { type: [Number, String], default: null },
    onCancel: { type: Function, default: null },
    storeUrl: { type: String, default: null }, // override for collection comments
});

const { auth } = usePage().props;
const form = useForm({
    content:      '',
    content_type: 'text',
    gif_url:      '',
    parent_id:    props.parentId ?? null,
});

// ── Emoji picker (native browser emoji support) ────────────────────────────
const showEmojiPicker = ref(false);

const COMMON_EMOJIS = ['😂','❤️','🔥','👍','😍','🥺','✨','💯','😭','🎉','👏','🙌','😊','🤣','💀','🥰','😎','🤩','😤','😢','🫡','💪','🎬','🎭','🍿','⭐','👑','🏆','💫','🌟'];

function insertEmoji(emoji) {
    form.content += emoji;
    showEmojiPicker.value = false;
}

// ── GIF picker via Tenor ───────────────────────────────────────────────────
const showGifPicker  = ref(false);
const gifQuery       = ref('');
const gifResults     = ref([]);
const gifLoading     = ref(false);
const gifNextPos     = ref('');
const gifDebounce    = ref(null);

async function searchGifs() {
    if (!gifQuery.value.trim()) {
        await loadFeaturedGifs();
        return;
    }
    gifLoading.value = true;
    try {
        const res = await fetch(route('gifs.search') + '?q=' + encodeURIComponent(gifQuery.value));
        const data = await res.json();
        gifResults.value = data.results || [];
        gifNextPos.value = data.next || '';
    } catch {
        gifResults.value = [];
    } finally {
        gifLoading.value = false;
    }
}

async function loadFeaturedGifs() {
    gifLoading.value = true;
    try {
        const res = await fetch(route('gifs.search') + '?q=movie+cinema');
        const data = await res.json();
        gifResults.value = data.results || [];
        gifNextPos.value = data.next || '';
    } catch {
        gifResults.value = [];
    } finally {
        gifLoading.value = false;
    }
}

function onGifQueryInput() {
    clearTimeout(gifDebounce.value);
    gifDebounce.value = setTimeout(searchGifs, 400);
}

function openGifPicker() {
    showGifPicker.value = true;
    if (!gifResults.value.length) loadFeaturedGifs();
}

function selectGif(gif) {
    form.content      = gif.title || 'GIF';
    form.content_type = 'gif';
    form.gif_url      = gif.url;
    showGifPicker.value = false;
}

function clearGif() {
    form.content      = '';
    form.content_type = 'text';
    form.gif_url      = '';
}

// ── Submit ─────────────────────────────────────────────────────────────────
const charCount = computed(() => form.content.length);
const MAX_CHARS = 2000;

function submit() {
    if (!form.content.trim()) return;
    const url = props.storeUrl ?? route('comments.store', props.titleId);
    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.content_type = 'text';
            if (props.onCancel) props.onCancel();
        },
    });
}
</script>

<template>
    <div class="space-y-2">
        <!-- GIF preview -->
        <div v-if="form.content_type === 'gif' && form.gif_url" class="relative inline-block">
            <img :src="form.gif_url" alt="GIF" class="max-h-32 rounded-lg border border-[var(--color-border)]" />
            <button
                type="button"
                class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-muted)] hover:text-[var(--color-accent)] flex items-center justify-center text-xs"
                @click="clearGif"
            >✕</button>
        </div>

        <!-- Textarea -->
        <div class="relative">
            <textarea
                v-model="form.content"
                :placeholder="parentId ? 'Viết phản hồi...' : 'Viết bình luận... (có thể gửi emoji, GIF)'"
                rows="3"
                :maxlength="MAX_CHARS"
                class="w-full px-4 py-3 bg-[var(--color-bg-elevated)] border border-[var(--color-border)] rounded-xl text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] text-sm resize-none focus:outline-none focus:border-[var(--color-accent)]/60 focus:ring-1 focus:ring-[var(--color-accent)]/30 transition-colors"
                :class="form.errors.content && 'border-red-500/60'"
            />
            <span class="absolute bottom-2 right-3 text-xs text-[var(--color-text-muted)] font-mono">
                {{ charCount }}/{{ MAX_CHARS }}
            </span>
        </div>
        <p v-if="form.errors.content" class="text-xs text-red-400">{{ form.errors.content }}</p>

        <!-- Toolbar -->
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-1">
                <!-- Emoji picker toggle -->
                <div class="relative">
                    <button
                        type="button"
                        title="Emoji"
                        class="p-2 rounded-lg text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-elevated)] transition-colors text-base"
                        @click="showEmojiPicker = !showEmojiPicker; showGifPicker = false"
                    >😊</button>

                    <!-- Emoji grid popup -->
                    <Transition enter-active-class="transition duration-100 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-75 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                        <div
                            v-if="showEmojiPicker"
                            class="absolute bottom-full left-0 mb-1 bg-[var(--color-bg-surface)] border border-[var(--color-border)] rounded-xl shadow-xl p-2 w-64 z-10"
                        >
                            <div class="grid grid-cols-6 gap-1">
                                <button
                                    v-for="emoji in COMMON_EMOJIS"
                                    :key="emoji"
                                    type="button"
                                    class="w-8 h-8 text-lg hover:bg-[var(--color-bg-elevated)] rounded flex items-center justify-center transition-colors"
                                    @click="insertEmoji(emoji)"
                                >{{ emoji }}</button>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- GIF picker toggle -->
                <button
                    type="button"
                    title="GIF"
                    class="px-2 py-1 rounded-lg text-xs font-bold text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-elevated)] transition-colors border border-[var(--color-border)] tracking-wider"
                    @click="openGifPicker(); showEmojiPicker = false"
                >GIF</button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    v-if="onCancel"
                    type="button"
                    class="px-3 py-1.5 text-sm text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors"
                    @click="onCancel"
                >Huỷ</button>
                <Button
                    variant="primary"
                    size="sm"
                    :disabled="!form.content.trim() || form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Đang gửi...' : (parentId ? 'Phản hồi' : 'Đăng') }}
                </Button>
            </div>
        </div>

        <!-- GIF picker panel -->
        <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
            <div
                v-if="showGifPicker"
                class="border border-[var(--color-border)] rounded-xl bg-[var(--color-bg-surface)] p-3 space-y-2"
            >
                <div class="flex items-center gap-2">
                    <input
                        v-model="gifQuery"
                        type="text"
                        placeholder="Tìm GIF..."
                        class="flex-1 px-3 py-1.5 bg-[var(--color-bg-elevated)] border border-[var(--color-border)] rounded-lg text-sm text-[var(--color-text-primary)] placeholder-[var(--color-text-muted)] focus:outline-none focus:border-[var(--color-accent)]/60 transition-colors"
                        @input="onGifQueryInput"
                    />
                    <button
                        type="button"
                        class="text-xs text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]"
                        @click="showGifPicker = false"
                    >✕</button>
                </div>

                <!-- GIF grid -->
                <div class="grid grid-cols-3 gap-1.5 max-h-48 overflow-y-auto">
                    <div v-if="gifLoading" class="col-span-3 py-4 text-center text-[var(--color-text-muted)] text-sm">
                        Đang tải...
                    </div>
                    <button
                        v-for="gif in gifResults"
                        :key="gif.id"
                        type="button"
                        class="relative aspect-video rounded-lg overflow-hidden bg-[var(--color-bg-elevated)] hover:ring-2 hover:ring-[var(--color-accent)] transition-all"
                        @click="selectGif(gif)"
                    >
                        <img :src="gif.preview_url || gif.url" :alt="gif.title" class="w-full h-full object-cover" loading="lazy" />
                    </button>
                    <p v-if="!gifLoading && !gifResults.length" class="col-span-3 text-center text-[var(--color-text-muted)] text-sm py-4">
                        Không tìm thấy GIF
                    </p>
                </div>
                <p class="text-[10px] text-[var(--color-text-muted)] text-right">Powered by Tenor</p>
            </div>
        </Transition>
    </div>
</template>
