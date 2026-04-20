import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

export function useLanguage() {
  const { locale } = useI18n()

  const initLanguage = () => {
    const savedLocale = localStorage.getItem('locale') || 'en'
    locale.value = savedLocale
    document.documentElement.setAttribute('lang', savedLocale)
    document.documentElement.setAttribute('dir', savedLocale === 'ar' ? 'rtl' : 'ltr')
  }

  onMounted(() => {
    initLanguage()
  })

  return {
    locale,
    initLanguage
  }
}
