import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/services/api'

export interface User {
  id: number
  name: string
  email: string
  phone: string | null
  avatar_url: string
  role: string
  user_mode: 'b2c' | 'b2b'
  status: string
  is_admin: boolean
  is_b2b: boolean
  b2b_profile: {
    company_name: string
    tier_level: string
    status: string
    credit_limit: number
  } | null
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)

  const isLoggedIn = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.is_admin ?? false)
  const isB2B = computed(() => user.value?.user_mode === 'b2b')
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const response: any = await authApi.login({ email, password })
      setAuth(response.data.token, response.data.user)
      return response
    } finally {
      loading.value = false
    }
  }

  async function register(data: any) {
    loading.value = true
    try {
      const response: any = await authApi.register(data)
      setAuth(response.data.token, response.data.user)
      return response
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {}
    clearAuth()
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const response: any = await authApi.me()
      user.value = response.data
    } catch {
      clearAuth()
    }
  }

  async function switchMode(mode: 'b2c' | 'b2b') {
    const response: any = await authApi.switchMode(mode)
    user.value = response.data
  }

  function setAuth(newToken: string, newUser: User) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('auth_token', newToken)
  }

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
  }

  return {
    user, token, loading,
    isLoggedIn, isAdmin, isB2B, isSuperAdmin,
    login, register, logout, fetchMe, switchMode, setAuth, clearAuth,
  }
})
