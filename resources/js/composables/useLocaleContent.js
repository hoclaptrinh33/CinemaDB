import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

/**
 * Returns localised { name, description } for a title object.
 * Falls back to the English field if the VI field is null/empty.
 */
export function useLocaleContent(title) {
    const { locale } = useI18n()

    const name = computed(() => {
        if (locale.value === 'vi' && title.value?.title_name_vi) {
            return title.value.title_name_vi
        }
        return title.value?.title_name ?? ''
    })

    const description = computed(() => {
        if (locale.value === 'vi' && title.value?.description_vi) {
            return title.value.description_vi
        }
        return title.value?.description ?? ''
    })

    return { name, description }
}
