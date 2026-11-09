import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export type ThemeMode = 'dark' | 'light'

export const useThemeStore = defineStore('theme', () => {
  const saved = (typeof window !== 'undefined' ? localStorage.getItem('mcc_theme') : null) as ThemeMode | null
  const currentTheme = ref<ThemeMode>(saved === 'light' ? 'light' : 'dark')

  const isLight = computed(() => currentTheme.value === 'light')
  const isDark = computed(() => currentTheme.value === 'dark')

  const applyThemeToDOM = (theme: ThemeMode) => {
    if (typeof document === 'undefined') return
    const root = document.documentElement
    const body = document.body

    root.setAttribute('data-theme', theme)
    if (theme === 'light') {
      root.classList.add('theme-light')
      root.classList.remove('theme-dark')
      body.classList.add('theme-light')
      body.classList.remove('theme-dark')
    } else {
      root.classList.add('theme-dark')
      root.classList.remove('theme-light')
      body.classList.add('theme-dark')
      body.classList.remove('theme-light')
    }
  }

  // Initialize immediately
  applyThemeToDOM(currentTheme.value)

  const setTheme = (theme: ThemeMode) => {
    currentTheme.value = theme
    if (typeof window !== 'undefined') {
      localStorage.setItem('mcc_theme', theme)
    }
    applyThemeToDOM(theme)
  }

  const toggleTheme = () => {
    setTheme(currentTheme.value === 'dark' ? 'light' : 'dark')
  }

  return {
    currentTheme,
    isLight,
    isDark,
    setTheme,
    toggleTheme
  }
})
