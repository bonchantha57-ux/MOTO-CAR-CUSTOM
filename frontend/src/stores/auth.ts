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

  const isAuthenticated = computed(() => !!token.value)
  const isLoggedIn = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.is_admin || user.value?.role === 'admin' || user.value?.role === 'super_admin')
  const isB2B = computed(() => user.value?.user_mode === 'b2b' || user.value?.role === 'b2b_client')
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const res: any = await authApi.login({ email, password })
      const authToken = res.token || res.data?.token
      const authUser = res.user || res.data?.user
      if (authToken && authUser) {
        setAuth(authToken, authUser)
      }
      return res
    } finally {
      loading.value = false
    }
  }

  async function register(data: any) {
    loading.value = true
    try {
      const res: any = await authApi.register(data)
      const authToken = res.token || res.data?.token
      const authUser = res.user || res.data?.user
      if (authToken && authUser) {
        setAuth(authToken, authUser)
      }
      return res
    } finally {
      loading.value = false
    }
  }

  async function b2bRegister(data: any) {
    loading.value = true
    try {
      const res: any = await authApi.b2bRegister(data)
      const authToken = res.token || res.data?.token
      const authUser = res.user || res.data?.user
      if (authToken && authUser) {
        setAuth(authToken, authUser)
      }
      return res
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
      const res: any = await authApi.me()
      user.value = res.user || res.data?.user || res.data
    } catch {
      clearAuth()
    }
  }

  function setMode(mode: 'b2c' | 'b2b') {
    if (user.value) {
      user.value.user_mode = mode
    }
    if (token.value) {
      authApi.switchMode(mode).catch(() => {})
    }
  }

  function setAuth(newToken: string, newUser: User) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('auth_token', newToken)
    localStorage.setItem('auth_user', JSON.stringify(newUser))
  }

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }

  // Restore stored user if available
  const storedUser = localStorage.getItem('auth_user')
  if (storedUser && token.value) {
    try {
      const parsed = JSON.parse(storedUser)
      // Safety: Customer user should never have the Admin photo
      if (parsed.role !== 'super_admin' && parsed.avatar_url && parsed.avatar_url.includes('1792386647_photo')) {
        parsed.avatar_url = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&h=200&q=80'
        localStorage.setItem('auth_user', JSON.stringify(parsed))
      }
      user.value = parsed
      // Sync fresh user data from server in background
      fetchMe().catch(() => {})
    } catch {}
  }


  return {
    user,
    token,
    loading,
    isAuthenticated,
    isLoggedIn,
    isAdmin,
    isB2B,
    isSuperAdmin,
    login,
    register,
    b2bRegister,
    logout,
    fetchMe,
    setMode,
    setAuth,
    clearAuth,
  }
})
