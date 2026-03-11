<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import { watch, computed } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Viết nội dung...' },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Underline,
    ],
    editorProps: {
        attributes: {
            class: 'rich-text-content min-h-[120px] px-4 py-3 focus:outline-none',
        },
    },
    onUpdate({ editor }) {
        emit('update:modelValue', editor.getHTML());
    },
});

// Keep editor in sync if parent changes the value externally
watch(() => props.modelValue, (val) => {
    if (editor.value && editor.value.getHTML() !== val) {
        editor.value.commands.setContent(val, false);
    }
});

const toolbarButtons = computed(() => {
    if (!editor.value) return [];
    return [
        {
            label: 'Bold',
            icon: '<strong>B</strong>',
            active: () => editor.value.isActive('bold'),
            action: () => editor.value.chain().focus().toggleBold().run(),
        },
        {
            label: 'Italic',
            icon: '<em>I</em>',
            active: () => editor.value.isActive('italic'),
            action: () => editor.value.chain().focus().toggleItalic().run(),
        },
        {
            label: 'Underline',
            icon: '<u>U</u>',
            active: () => editor.value.isActive('underline'),
            action: () => editor.value.chain().focus().toggleUnderline().run(),
        },
        {
            label: 'H1',
            icon: 'H1',
            active: () => editor.value.isActive('heading', { level: 1 }),
            action: () => editor.value.chain().focus().toggleHeading({ level: 1 }).run(),
        },
        {
            label: 'H2',
            icon: 'H2',
            active: () => editor.value.isActive('heading', { level: 2 }),
            action: () => editor.value.chain().focus().toggleHeading({ level: 2 }).run(),
        },
        {
            label: 'H3',
            icon: 'H3',
            active: () => editor.value.isActive('heading', { level: 3 }),
            action: () => editor.value.chain().focus().toggleHeading({ level: 3 }).run(),
        },
        {
            label: 'Bullet list',
            icon: '&#8226;&#8212;',
            active: () => editor.value.isActive('bulletList'),
            action: () => editor.value.chain().focus().toggleBulletList().run(),
        },
    ];
});
</script>

<template>
    <div class="rich-text-editor rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-elevated)] overflow-hidden focus-within:border-[var(--color-accent)]/60 focus-within:ring-1 focus-within:ring-[var(--color-accent)]/30 transition-colors">
        <!-- Toolbar -->
        <div class="toolbar flex items-center gap-0.5 px-2 py-1.5 border-b border-[var(--color-border)] flex-wrap">
            <button
                v-for="btn in toolbarButtons"
                :key="btn.label"
                type="button"
                :title="btn.label"
                :class="[
                    'toolbar-btn px-2 py-1 rounded text-xs font-medium transition-colors',
                    btn.active() ? 'bg-[var(--color-accent)]/20 text-[var(--color-accent)]' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-bg-surface)]',
                ]"
                @click.prevent="btn.action()"
            >
                <span v-html="btn.icon" />
            </button>
        </div>

        <!-- Editor area -->
        <div class="relative">
            <EditorContent :editor="editor" class="text-sm text-[var(--color-text-primary)]" />
            <p
                v-if="!modelValue || modelValue === '<p></p>'"
                class="absolute top-3 left-4 text-sm text-[var(--color-text-muted)] pointer-events-none select-none"
            >{{ placeholder }}</p>
        </div>
    </div>
</template>

<style scoped>
.rich-text-content :deep(h1) { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--color-text-primary); }
.rich-text-content :deep(h2) { font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--color-text-primary); }
.rich-text-content :deep(h3) { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.375rem; color: var(--color-text-primary); }
.rich-text-content :deep(strong) { font-weight: 700; }
.rich-text-content :deep(em) { font-style: italic; }
.rich-text-content :deep(u) { text-decoration: underline; }
.rich-text-content :deep(ul) { list-style-type: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.rich-text-content :deep(ol) { list-style-type: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.rich-text-content :deep(p) { margin-bottom: 0.25rem; }
</style>
