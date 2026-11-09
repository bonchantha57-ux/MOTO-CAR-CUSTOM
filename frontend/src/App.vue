<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { useThemeStore } from '@/stores/theme'
import { useLanguageStore } from '@/stores/language'

const themeStore = useThemeStore()
const langStore = useLanguageStore()

let keepAliveTimer: any = null

onMounted(() => {
  // 1. Warm up Railway backend immediately on page open
  axios.get('/up').catch(() => {})
  axios.get('/api/v1/products?per_page=12').catch(() => {})

  // 2. Periodic keep-alive ping every 4 minutes while browser tab is active
  keepAliveTimer = setInterval(() => {
    axios.get('/up').catch(() => {})
  }, 4 * 60 * 1000)
})

onUnmounted(() => {
  if (keepAliveTimer) clearInterval(keepAliveTimer)
})
</script>

<template>
  <router-view />
</template>

<style>
/* Base styles */
*, *::before, *::after {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  background-color: #0f172a;
  color: #f8fafc;
}
</style>
