import { createI18n } from 'vue-i18n'
import en from './locales/en.js'
import vi from './locales/vi.js'

const locale = localStorage.getItem('locale') ?? 'vi'

export const i18n = createI18n({
    legacy: false,
    locale,
    fallbackLocale: 'vi',
    messages: { en, vi },
})
