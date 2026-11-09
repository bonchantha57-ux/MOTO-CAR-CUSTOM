import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export type ThemeMode = 'dark' | 'light'

const STORAGE_KEY = 'mcc_theme_mode_v3'

export const useThemeStore = defineStore('theme', () => {
  // Clear any legacy bright/stale mode from older versions that caused eye glare
  if (typeof window !== 'undefined') {
    try {
      localStorage.removeItem('mcc_theme')
      localStorage.removeItem('mcc_theme_mode_v2')
    } catch (e) {}
  }

  // Always default strictly to 'dark' (Supercar Obsidian Luxury) for optimal eye comfort and automotive prestige
  const saved = (typeof window !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null) as ThemeMode | null
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
      localStorage.setItem(STORAGE_KEY, theme)
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
