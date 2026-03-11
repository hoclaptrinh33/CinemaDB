<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import CommentList from '@/Components/Discussion/CommentList.vue';

defineOptions({ layout: AppLayout });

const { t, locale } = useI18n();

const props = defineProps({
    collection:        { type: Object, required: true },
    titles:            { type: Array,  default: () => [] },
    collaborators:     { type: Array,  default: () => [] },
    myRole:            { type: String, default: null },
    can:               { type: Object, default: () => ({}) },
    userNotes:         { type: Object, default: () => ({}) },   // { [title_id]: { watched_at } } — note is now shared on the pivot
    userHasNominated:  { type: Boolean, default: false },
    cover_image_url:   { type: String,  default: null },
    comments:          { type: Object,  default: null },
    canComment:        { type: Boolean, default: false },
});

const { auth } = usePage().props;

const inviteUsername   = ref('');
const expandedNoteFor  = ref(null);  // title_id whose note panel is open
const localNotes       = ref({ ...props.userNotes });
// Shared notes (one per title-in-collection, same for all members)
const localSharedNotes = ref(Object.fromEntries(props.titles.map(t => [t.title_id, t.note ?? ''])));

// â”€â”€ Visibility toggle (owner) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function toggleVisibility() {
    const next = props.collection.visibility === 'PUBLIC' ? 'PRIVATE' : 'PUBLIC';
    router.patch(route('collections.update', props.collection.slug), { visibility: next }, {
        preserveScroll: true,
    });
}

// â”€â”€ Publish / Unpublish (owner) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const showPublishConfirm = ref(false);
const publishErrors = ref({});
const publishForm = ref({ headline: '', body: '' });

function confirmPublish() {
    publishErrors.value = {};
    publishForm.value.headline = props.collection.publish_headline ?? '';
    publishForm.value.body = props.collection.publish_body ?? '';
    showPublishConfirm.value = true;
}

function doPublish() {
    if (props.titles.length === 0) {
        publishErrors.value = { titles: t('collections.minTitlesRequired') };
        return;
    }
    publishErrors.value = {};
    showPublishConfirm.value = false;
    router.post(route('collections.publish', props.collection.slug), {
        publish_headline: publishForm.value.headline || null,
        publish_body: publishForm.value.body || null,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            publishErrors.value = errors;
            showPublishConfirm.value = true;
        },
    });
}

function unpublish() {
    router.delete(route('collections.unpublish', props.collection.slug), {
        preserveScroll: true,
    });
}

// â”€â”€ Copy (auth users on published lists) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function copyList() {
    router.post(route('collections.copy', props.collection.slug), {});
}

// â”€â”€ Nomination â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function nominate() {
    router.post(route('collections.nominate', props.collection.slug), {}, {
        preserveScroll: true,
    });
}

function unnominate() {
    router.delete(route('collections.unnominate', props.collection.slug), {
        preserveScroll: true,
    });
}

// â”€â”€ Title management â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function removeTitle(titleId) {
    if (!confirm(t('collections.confirmRemove'))) return;
    router.delete(route('collections.titles.remove', [props.collection.slug, titleId]), {
        preserveScroll: true,
    });
}

// â”€â”€ Notes & Watch â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function toggleNotePanel(titleId) {
    expandedNoteFor.value = expandedNoteFor.value === titleId ? null : titleId;
}

function getNoteFor(titleId) {
    return localSharedNotes.value[titleId] ?? '';
}

function getWatchedAt(titleId) {
    return localNotes.value[titleId]?.watched_at ?? null;
}

function saveNote(titleId) {
    router.put(
        route('collections.titles.note', [props.collection.slug, titleId]),
        { note: localSharedNotes.value[titleId] ?? '' },
        {
            preserveScroll: true,
            onSuccess: () => { expandedNoteFor.value = null; },
        }
    );
}

function toggleWatch(titleId) {
    const current = localNotes.value[titleId]?.watched_at ?? null;
    // Optimistic UI update
    if (!localNotes.value[titleId]) localNotes.value[titleId] = {};
    localNotes.value[titleId].watched_at = current ? null : new Date().toISOString();

    router.post(
        route('collections.titles.watch', [props.collection.slug, titleId]),
        {},
        {
            preserveScroll: true,
            onError: () => {
                // Revert on failure
                localNotes.value[titleId].watched_at = current;
            },
        }
    );
}

function fmtWatched(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
        hour: '2-digit', minute: '2-digit',
        day: '2-digit', month: '2-digit', year: 'numeric',
    });
}

// â”€â”€ Collaborators â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function invite() {
    if (!inviteUsername.value.trim()) return;
    router.post(route('collections.collaborators.invite', props.collection.slug), {
        username: inviteUsername.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => { inviteUsername.value = ''; },
    });
}

function acceptInvite() {
    router.post(route('collections.collaborators.accept', props.collection.slug), {}, {
        preserveScroll: true,
    });
}

function declineOrLeave() {
    const msg = props.myRole === 'pending'
        ? t('collections.confirmDecline')
        : t('collections.confirmLeave');
    if (!confirm(msg)) return;
    router.delete(route('collections.collaborators.destroy', [props.collection.slug, auth.user.id]), {
        preserveScroll: true,
    });
}

function removeCollaborator(userId) {
    if (!confirm(t('collections.confirmRemoveCollaborator'))) return;
    router.delete(route('collections.collaborators.destroy', [props.collection.slug, userId]), {
        preserveScroll: true,
    });
}

// ── Rename (owner) ───────────────────────────────────────────────────────────────────
const showRenameModal = ref(false);
const renameForm = ref({ name: '', description: '' });
const renameErrors = ref({});

function openRename() {
    renameForm.value.name = props.collection.name;
    renameForm.value.description = props.collection.description ?? '';
    renameErrors.value = {};
    showRenameModal.value = true;
}

function doRename() {
    renameErrors.value = {};
    router.patch(route('collections.update', props.collection.slug), {
        name: renameForm.value.name,
        description: renameForm.value.description,
    }, {
        preserveScroll: true,
        onSuccess: () => { showRenameModal.value = false; },
        onError: (errors) => { renameErrors.value = errors; },
    });
}

// ── Delete (owner) ───────────────────────────────────────────────────────────────────
const showDeleteConfirm = ref(false);
const settingsMenuOpen = ref(false);

function doDelete() {
    router.delete(route('collections.destroy', props.collection.slug));
}

// ── Cover image upload (owner) ────────────────────────────────────────────────────────
const coverFileInput = ref(null);
const autoCoverUrls = computed(() =>
    props.titles.slice(0, 4).map(t => t.poster_url).filter(Boolean)
);

function uploadCover(event) {
    const file = event.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('cover', file);
    router.post(route('collections.cover', props.collection.slug), formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { coverFileInput.value.value = ''; },
    });
}

// ── Derived ──────────────────────────────────────────────────────────────────────────
const isMember = computed(() => props.myRole === 'owner' || props.myRole === 'collaborator');
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

        <!-- Pending invite banner -->
        <div v-if="myRole === 'pending'" class="mb-6 rounded-xl bg-[var(--color-accent)]/10 border border-[var(--color-accent)]/30 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <p class="text-sm text-[var(--color-text-secondary)]">{{ t('collections.youHavePendingInvite') }}</p>
            <div class="flex gap-2 shrink-0">
                <button type="button" class="px-4 py-1.5 text-sm rounded-lg bg-[var(--color-accent)] text-white font-medium hover:opacity-90 transition-opacity" @click="acceptInvite">
                    {{ t('collections.acceptInvite') }}
                </button>
                <button type="button" class="px-4 py-1.5 text-sm rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)] transition-colors" @click="declineOrLeave">
                    {{ t('collections.declineInvite') }}
                </button>
            </div>
        </div>

        <!-- Publish confirmation dialog -->
        <div v-if="showPublishConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
            <div class="bg-[var(--color-bg-surface)] rounded-2xl p-6 max-w-2xl w-full mx-auto shadow-2xl overflow-y-auto max-h-[90vh]">
                <h3 class="font-display font-bold text-lg text-[var(--color-text-primary)] mb-2">{{ t('collections.publishConfirmTitle') }}</h3>
                <p class="text-sm text-[var(--color-text-muted)] mb-4">{{ t('collections.publishConfirm') }}</p>
                <p v-if="publishErrors.titles" class="text-sm text-red-400 mb-3">{{ publishErrors.titles }}</p>

                <!-- Publish notes (optional) -->
                <div class="space-y-3 mb-5">
                    <div>
                        <label class="block text-xs font-medium text-[var(--color-text-muted)] mb-1">{{ t('collections.publishHeadlineLabel') }}</label>
                        <input
                            v-model="publishForm.headline"
                            type="text"
                            maxlength="200"
                            :placeholder="t('collections.publishHeadlinePlaceholder')"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-base)] text-[var(--color-text-secondary)] placeholder:text-[var(--color-text-muted)] focus:outline-none focus:border-[var(--color-accent)] transition-colors"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[var(--color-text-muted)] mb-1">{{ t('collections.publishBodyLabel') }}</label>
                        <RichTextEditor
                            v-model="publishForm.body"
                            :placeholder="t('collections.publishBodyPlaceholder')"
                        />
                        <p class="text-[10px] text-[var(--color-text-muted)] mt-1">{{ t('collections.publishNotesHint') }}</p>
                    </div>
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)]" @click="showPublishConfirm = false">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="button" class="px-4 py-2 text-sm rounded-lg bg-emerald-600 text-white font-medium hover:opacity-90" @click="doPublish">
                        {{ t('collections.publish') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Rename modal -->
        <div v-if="showRenameModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div class="bg-[var(--color-bg-surface)] rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl">
                <h3 class="font-display font-bold text-lg text-[var(--color-text-primary)] mb-4">{{ t('collections.renameTitle') }}</h3>
                <div class="space-y-3 mb-5">
                    <div>
                        <label class="block text-xs text-[var(--color-text-muted)] mb-1">{{ t('collections.nameLabel') }}</label>
                        <input
                            v-model="renameForm.name"
                            type="text"
                            maxlength="200"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-base)] text-[var(--color-text-secondary)] focus:outline-none focus:border-[var(--color-accent)]"
                            :class="renameErrors.name ? 'border-red-500' : ''"
                        />
                        <p v-if="renameErrors.name" class="mt-1 text-xs text-red-400">{{ renameErrors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-[var(--color-text-muted)] mb-1">{{ t('collections.descLabel') }}</label>
                        <textarea
                            v-model="renameForm.description"
                            rows="3"
                            maxlength="1000"
                            class="w-full px-3 py-2 text-sm rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-base)] text-[var(--color-text-secondary)] focus:outline-none focus:border-[var(--color-accent)] resize-none"
                        />
                    </div>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)]" @click="showRenameModal = false">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="button" class="px-4 py-2 text-sm rounded-lg bg-[var(--color-accent)] text-white font-medium hover:opacity-90 disabled:opacity-40" :disabled="!renameForm.name.trim()" @click="doRename">
                        {{ t('common.save') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete confirm dialog -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
            <div class="bg-[var(--color-bg-surface)] rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl">
                <h3 class="font-display font-bold text-lg text-[var(--color-text-primary)] mb-2">{{ t('collections.deleteConfirmTitle') }}</h3>
                <p class="text-sm text-[var(--color-text-muted)] mb-5">{{ t('collections.deleteConfirm') }}</p>
                <div class="flex gap-3 justify-end">
                    <button type="button" class="px-4 py-2 text-sm rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)]" @click="showDeleteConfirm = false">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="button" class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white font-medium hover:opacity-90" @click="doDelete">
                        {{ t('collections.deleteList') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Cover section -->
        <div class="group relative mb-6 rounded-xl overflow-hidden bg-[var(--color-bg-elevated)]" style="aspect-ratio: 16/9;">
            <!-- Custom uploaded cover -->
            <img
                v-if="collection.cover_image_url"
                :src="collection.cover_image_url"
                :alt="collection.name"
                class="w-full h-full object-cover"
                loading="lazy"
            />
            <!-- Auto cover: single poster full-size -->
            <img
                v-else-if="autoCoverUrls.length === 1"
                :src="autoCoverUrls[0]"
                :alt="collection.name"
                class="w-full h-full object-cover"
                loading="lazy"
            />
            <!-- Auto 2×2 collage from title posters -->
            <div v-else-if="autoCoverUrls.length > 1" class="grid grid-cols-2 w-full h-full">
                <img
                    v-for="(url, idx) in autoCoverUrls"
                    :key="idx"
                    :src="url"
                    class="w-full h-full object-cover"
                    loading="lazy"
                />
            </div>
            <!-- Placeholder -->
            <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="w-16 h-16 opacity-20 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                </svg>
            </div>
            <!-- Upload button visible on hover (owner only) -->
            <div v-if="can.editMeta" class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                <button
                    type="button"
                    class="px-3 py-1.5 text-xs rounded-lg bg-black/70 text-white font-medium hover:bg-black/90 backdrop-blur-sm transition-colors flex items-center gap-1.5"
                    @click="coverFileInput.click()"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    {{ t('collections.changeCover') }}
                </button>
            </div>
            <input ref="coverFileInput" type="file" accept="image/*" class="hidden" @change="uploadCover" />
        </div>

        <!-- Publish notes display (below cover, above header) -->
        <div
            v-if="collection.is_published && (collection.publish_headline || collection.publish_body)"
            class="mb-6 p-5 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-surface)]"
        >
            <h2 v-if="collection.publish_headline" class="font-display font-bold text-xl text-[var(--color-text-primary)] mb-2">
                {{ collection.publish_headline }}
            </h2>
            <div
                v-if="collection.publish_body"
                class="publish-notes-body text-sm text-[var(--color-text-secondary)] leading-relaxed"
                v-html="collection.publish_body"
            />
        </div>

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 text-xs text-[var(--color-text-muted)] mb-2">
                        <a :href="route('users.collections', collection.owner.id)" class="hover:text-[var(--color-accent)]">
                            {{ collection.owner.name }}
                        </a>
                        <svg class="w-3 h-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        <span>{{ t('collections.breadcrumb') }}</span>
                        <span v-if="collection.visibility === 'PRIVATE'" class="ml-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            {{ t('collections.private') }}
                        </span>
                        <span v-if="collection.is_published" class="ml-1 text-emerald-500 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ t('collections.published') }}
                        </span>
                    </div>
                    <h1 class="font-display font-black text-2xl text-[var(--color-text-primary)]">{{ collection.name }}</h1>
                    <p v-if="collection.original_author_name" class="text-xs text-[var(--color-text-muted)] mt-0.5 italic">
                        {{ t('collections.copiedFrom', { author: collection.original_author_name }) }}
                    </p>
                    <p v-if="collection.description" class="text-[var(--color-text-muted)] text-sm mt-1 max-w-2xl">{{ collection.description }}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs text-[var(--color-text-muted)]">
                        <span>{{ t('collections.titlesCount', { n: titles.length }) }}</span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.11a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                            </svg>
                            {{ t('collections.nominationCount', { n: collection.nomination_count }) }}
                        </span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex flex-wrap gap-2 shrink-0 items-center">
                    <!-- Nominate (public+published, any auth user) -->
                    <button v-if="can.nominate && !userHasNominated" type="button"
                        class="px-3 py-1.5 text-xs rounded-lg bg-[var(--color-accent)] text-white font-medium hover:opacity-90 transition-opacity"
                        @click="nominate"
                    >
                        {{ t('collections.nominate') }}
                    </button>
                    <button v-if="can.nominate && userHasNominated" type="button"
                        class="px-3 py-1.5 text-xs rounded-lg border border-[var(--color-accent)] text-[var(--color-accent)] hover:bg-[var(--color-accent)]/10 transition-colors"
                        @click="unnominate"
                    >
                        {{ t('collections.unnominate') }}
                    </button>

                    <!-- Copy (public+published) -->
                    <button v-if="can.copy" type="button"
                        class="px-3 py-1.5 text-xs rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)] transition-colors"
                        @click="copyList"
                    >
                        {{ t('collections.copy') }}
                    </button>

                    <!-- Settings dropdown (owner/editor actions) -->
                    <div v-if="can.editMeta || can.publish" class="relative">
                        <!-- Backdrop to close on outside click -->
                        <div v-if="settingsMenuOpen" class="fixed inset-0 z-10" @click="settingsMenuOpen = false" />

                        <!-- Trigger button -->
                        <button
                            type="button"
                            class="px-3 py-1.5 text-xs rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)] transition-colors flex items-center gap-1.5"
                            @click="settingsMenuOpen = !settingsMenuOpen"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>

                        <!-- Dropdown menu -->
                        <div v-if="settingsMenuOpen" class="absolute right-0 top-full mt-1 w-52 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-surface)] shadow-xl z-20 overflow-hidden py-1">
                            <!-- Visibility toggle -->
                            <button v-if="can.editMeta" type="button"
                                class="w-full text-left px-4 py-2.5 text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)] transition-colors flex items-center gap-2.5"
                                @click="settingsMenuOpen = false; toggleVisibility()"
                            >
                                <svg v-if="collection.visibility === 'PUBLIC'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                <svg v-else class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ collection.visibility === 'PUBLIC' ? t('collections.makePrivate') : t('collections.makePublic') }}
                            </button>

                            <!-- Publish -->
                            <button v-if="can.publish && !collection.is_published" type="button"
                                class="w-full text-left px-4 py-2.5 text-sm text-emerald-500 hover:bg-[var(--color-bg-elevated)] transition-colors flex items-center gap-2.5"
                                @click="settingsMenuOpen = false; confirmPublish()"
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                {{ t('collections.publish') }}
                            </button>

                            <!-- Unpublish -->
                            <button v-if="can.editMeta && collection.is_published" type="button"
                                class="w-full text-left px-4 py-2.5 text-sm text-amber-500 hover:bg-[var(--color-bg-elevated)] transition-colors flex items-center gap-2.5"
                                @click="settingsMenuOpen = false; unpublish()"
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                {{ t('collections.unpublish') }}
                            </button>

                            <!-- Divider before destructive actions -->
                            <div v-if="can.editMeta" class="my-1 border-t border-[var(--color-border)]" />

                            <!-- Rename -->
                            <button v-if="can.editMeta" type="button"
                                class="w-full text-left px-4 py-2.5 text-sm text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-elevated)] transition-colors flex items-center gap-2.5"
                                @click="settingsMenuOpen = false; openRename()"
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                                {{ t('collections.rename') }}
                            </button>

                            <!-- Delete -->
                            <button v-if="can.editMeta" type="button"
                                class="w-full text-left px-4 py-2.5 text-sm text-red-400 hover:bg-red-500/10 transition-colors flex items-center gap-2.5"
                                @click="settingsMenuOpen = false; showDeleteConfirm = true"
                            >
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                {{ t('collections.deleteList') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="titles.length === 0" class="text-center py-16 text-[var(--color-text-muted)]">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <p class="text-sm">{{ t('collections.collectionEmpty') }}</p>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div
                v-for="title in titles"
                :key="title.title_id"
                class="group relative"
            >
                <a :href="route('titles.show', title.slug)" class="block">
                    <div class="aspect-[2/3] rounded-lg overflow-hidden bg-[var(--color-bg-elevated)] border border-[var(--color-border)]">
                        <img
                            v-if="title.poster_url"
                            :src="title.poster_url"
                            :alt="title.title_name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-[var(--color-text-muted)] text-xs text-center p-2">
                            {{ title.title_name }}
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-[var(--color-text-secondary)] font-medium truncate">{{ title.title_name }}</p>
                    <p v-if="title.avg_rating" class="text-xs text-[var(--color-gold)] flex items-center gap-0.5">
                        <svg class="w-3 h-3 fill-current" viewBox="0 0 24 24"><path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.11a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/></svg>
                        {{ Number(title.avg_rating).toFixed(1) }}
                    </p>
                </a>

                <!-- Remove button (editor) -->
                <button
                    v-if="can.edit"
                    type="button"
                    class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                    :title="t('collections.removeFromCollection')"
                    @click.prevent="removeTitle(title.title_id)"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Note/Watch toggle (members only) -->
                <button
                    v-if="isMember"
                    type="button"
                    class="absolute bottom-7 right-1.5 w-6 h-6 rounded-full bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                    :title="t('collections.sharedNote')"
                    @click.prevent="toggleNotePanel(title.title_id)"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                    </svg>
                </button>

                <!-- Watch status indicator -->
                <div v-if="isMember && getWatchedAt(title.title_id)" class="absolute top-1.5 left-1.5">
                    <span class="w-4 h-4 bg-emerald-600/80 text-white rounded-full flex items-center justify-center">
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                </div>
            </div>
        </div>

        <!-- Note panel (members only, expands below grid item) -->
        <transition name="fade">
            <div v-if="isMember && expandedNoteFor !== null" class="mt-4 p-4 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-elevated)] space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-[var(--color-text-primary)]">{{ t('collections.sharedNote') }}</p>
                    <button type="button" class="text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]" @click="expandedNoteFor = null">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>

                <!-- My personal watched status (private per-user) -->
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        :checked="!!getWatchedAt(expandedNoteFor)"
                        class="w-4 h-4 rounded accent-emerald-500"
                        @change="toggleWatch(expandedNoteFor)"
                    />
                    <span :class="getWatchedAt(expandedNoteFor) ? 'opacity-50 line-through' : ''" class="text-sm text-[var(--color-text-secondary)]">
                        {{ t('collections.watched') }}
                    </span>
                    <span v-if="getWatchedAt(expandedNoteFor)" class="text-xs text-[var(--color-text-muted)]">
                        {{ fmtWatched(getWatchedAt(expandedNoteFor)) }}
                    </span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-[var(--color-bg-surface)] text-[var(--color-text-muted)] border border-[var(--color-border)]">{{ t('collections.watchedPrivate') }}</span>
                </label>

                <!-- Shared note (rich text, same for all members) -->
                <RichTextEditor
                    v-model="localSharedNotes[expandedNoteFor]"
                    :placeholder="t('collections.notePlaceholder')"
                />

                <button
                    type="button"
                    class="px-4 py-1.5 text-sm rounded-lg bg-[var(--color-accent)] text-white font-medium hover:opacity-90 transition-opacity"
                    @click="saveNote(expandedNoteFor)"
                >
                    {{ t('common.save') }}
                </button>
            </div>
        </transition>

        <!-- Members Panel -->
        <div class="mt-10 pt-8 border-t border-[var(--color-border)]">
            <h2 class="font-semibold text-sm text-[var(--color-text-primary)] mb-4">{{ t('collections.members') }}</h2>

            <div class="space-y-1">
                <!-- Owner row -->
                <div class="flex items-center gap-3 py-2">
                    <img v-if="collection.owner.avatar_url" :src="collection.owner.avatar_url" :alt="collection.owner.name" class="w-8 h-8 rounded-full object-cover shrink-0" />
                    <div v-else class="w-8 h-8 rounded-full bg-[var(--color-bg-elevated)] flex items-center justify-center text-xs font-medium text-[var(--color-text-muted)] shrink-0">
                        {{ collection.owner.name[0] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <a :href="route('users.show', collection.owner.username)" class="text-sm font-medium text-[var(--color-text-secondary)] hover:text-[var(--color-accent)]">{{ collection.owner.name }}</a>
                        <span class="text-[10px] text-[var(--color-text-muted)] ml-1.5">@{{ collection.owner.username }}</span>
                    </div>
                    <span class="text-xs text-[var(--color-accent)] font-medium shrink-0">{{ t('collections.roleOwner') }}</span>
                </div>

                <!-- Collaborator rows -->
                <div v-for="c in collaborators" :key="c.user_id" class="flex items-center gap-3 py-2">
                    <img v-if="c.avatar_url" :src="c.avatar_url" :alt="c.name" class="w-8 h-8 rounded-full object-cover shrink-0" />
                    <div v-else class="w-8 h-8 rounded-full bg-[var(--color-bg-elevated)] flex items-center justify-center text-xs font-medium text-[var(--color-text-muted)] shrink-0">
                        {{ c.name[0] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <a :href="route('users.show', c.username)" class="text-sm font-medium text-[var(--color-text-secondary)] hover:text-[var(--color-accent)]">{{ c.name }}</a>
                        <span class="text-[10px] text-[var(--color-text-muted)] ml-1.5">@{{ c.username }}</span>
                    </div>
                    <span :class="c.status === 'accepted' ? 'text-emerald-500' : 'text-amber-500'" class="text-xs font-medium shrink-0">
                        {{ c.status === 'accepted' ? t('collections.roleCollaborator') : t('collections.statusPending') }}
                    </span>
                    <button v-if="can.invite" type="button" class="ml-1 text-[var(--color-text-muted)] hover:text-red-500 transition-colors shrink-0" :title="t('collections.removeCollaborator')" @click="removeCollaborator(c.user_id)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </div>

            <!-- Invite form (owner only) -->
            <div v-if="can.invite" class="mt-5 flex gap-2 max-w-sm">
                <input
                    v-model="inviteUsername"
                    type="text"
                    :placeholder="t('collections.invitePlaceholder')"
                    class="flex-1 px-3 py-2 text-sm rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-base)] text-[var(--color-text-secondary)] placeholder:text-[var(--color-text-muted)] focus:outline-none focus:border-[var(--color-accent)]"
                    @keydown.enter.prevent="invite"
                />
                <button
                    type="button"
                    class="px-4 py-2 text-sm rounded-lg bg-[var(--color-accent)] text-white font-medium hover:opacity-90 transition-opacity disabled:opacity-40"
                    :disabled="!inviteUsername.trim()"
                    @click="invite"
                >
                    {{ t('collections.inviteBtn') }}
                </button>
            </div>

            <!-- Leave button (accepted collaborator) -->
            <div v-if="myRole === 'collaborator'" class="mt-5">
                <button type="button" class="text-xs text-[var(--color-text-muted)] hover:text-red-500 transition-colors" @click="declineOrLeave">
                    {{ t('collections.leaveCollection') }}
                </button>
            </div>
        </div>

        <!-- Discussion section (public + published collections only) -->
        <div
            v-if="collection.is_published && collection.visibility === 'PUBLIC' && comments !== null"
            class="mt-10 pt-8 border-t border-[var(--color-border)]"
        >
            <h2 class="font-semibold text-sm text-[var(--color-text-primary)] mb-4">{{ t('collections.discussion') }}</h2>
            <CommentList
                :comments="comments"
                :title-id="collection.collection_id"
                :can-comment="canComment"
                :store-url="route('collection.comments.store', collection.slug)"
                like-route-name="collection.comments.like"
                destroy-route-name="collection.comments.destroy"
            />
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Publish notes rich text styles */
.publish-notes-body :deep(h1) { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--color-text-primary); }
.publish-notes-body :deep(h2) { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--color-text-primary); }
.publish-notes-body :deep(h3) { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.375rem; color: var(--color-text-primary); }
.publish-notes-body :deep(strong) { font-weight: 700; }
.publish-notes-body :deep(em) { font-style: italic; }
.publish-notes-body :deep(u) { text-decoration: underline; }
.publish-notes-body :deep(ul) { list-style-type: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.publish-notes-body :deep(ol) { list-style-type: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.publish-notes-body :deep(p) { margin-bottom: 0.25rem; }
</style>
