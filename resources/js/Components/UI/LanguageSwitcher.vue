<script setup>
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()

function toggle() {
    const next = locale.value === 'vi' ? 'en' : 'vi'
    locale.value = next
    localStorage.setItem('locale', next)
}
</script>

<template>
    <button
        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-sm font-semibold transition-colors hover:bg-[var(--color-bg-elevated)] text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] border border-[var(--color-border)]"
        :aria-label="locale === 'vi' ? 'Switch to English' : 'Chuyển sang tiếng Việt'"
        @click="toggle"
    >
        <span class="text-base leading-none">{{ locale === 'vi' ? '🇻🇳' : '🇺🇸' }}</span>
        <span class="font-mono tracking-wider text-xs">{{ locale === 'vi' ? 'VI' : 'EN' }}</span>
    </button>
</template>
