<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLanguageStore } from '@/stores/language'
import axios from 'axios'
import {
  LayoutDashboard,
  Package,
  Users,
  ShoppingBag,
  Building2,
  BarChart3,
  ExternalLink,
  LogOut,
  ShieldCheck,
  Wrench,
  ChevronRight,
  Camera,
  Upload,
  User,
  CheckCircle,
  X,
  Globe,
  ChevronDown
} from 'lucide-vue-next'
import { formatImageUrl, handleAvatarError, DEFAULT_AVATAR } from '@/utils/image'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const langStore = useLanguageStore()

const isLangDropdownOpen = ref(false)
const isAdminModalOpen = ref(false)
const isUploadingAvatar = ref(false)
const isSavingAdmin = ref(false)
const adminFileRef = ref<HTMLInputElement | null>(null)
const adminSuccess = ref('')
const adminError = ref('')

const selectLanguage = (code: any) => {
  langStore.setLanguage(code)
  isLangDropdownOpen.value = false
}

const handleGlobalClick = () => {
  isLangDropdownOpen.value = false
}

onMounted(() => {
  window.addEventListener('click', handleGlobalClick)
})

onUnmounted(() => {
  window.removeEventListener('click', handleGlobalClick)
})

const adminForm = ref({
  name: authStore.user?.name || 'MotoCar Director',
  email: authStore.user?.email || 'admin@motocar.com',
  phone: authStore.user?.phone || '+855 23 888 999',
  avatar: authStore.user?.avatar_url || ''
})

const openAdminModal = () => {
  adminForm.value = {
    name: authStore.user?.name || 'MotoCar Director',
    email: authStore.user?.email || 'admin@motocar.com',
    phone: authStore.user?.phone || '+855 23 888 999',
    avatar: authStore.user?.avatar_url || ''
  }
  adminSuccess.value = ''
  adminError.value = ''
  isAdminModalOpen.value = true
}

const triggerAdminFile = () => {
  adminFileRef.value?.click()
}

const handleAdminAvatarUpload = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  const file = target.files[0]

  isUploadingAvatar.value = true
  adminError.value = ''
  try {
    const formData = new FormData()
    formData.append('image', file)
    formData.append('folder', 'avatars')

    const res = await axios.post('/api/v1/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (res.data.url) {
      const savedUrl = formatImageUrl(res.data.url)
      adminForm.value.avatar = savedUrl
      
      // Auto save to DB for Admin (Target Super Admin User ID 1)
      const adminId = 1
      await axios.put(`/api/v1/admin/users/${adminId}`, {
        name: adminForm.value.name,
        email: adminForm.value.email,
        phone: adminForm.value.phone,
        role: 'super_admin',
        avatar: savedUrl
      })

      // Only update authStore if logged-in user is actually the super admin
      if (authStore.user && (authStore.user.role === 'super_admin' || authStore.user.id === 1)) {
        authStore.user.avatar_url = savedUrl
        authStore.user.avatar = savedUrl
        localStorage.setItem('auth_user', JSON.stringify(authStore.user))
      }
      adminSuccess.value = 'Admin profile picture updated & saved!'
      setTimeout(() => { adminSuccess.value = '' }, 3500)
    }
  } catch (err: any) {
    adminError.value = err.response?.data?.message || 'Failed to upload photo.'
  } finally {
    isUploadingAvatar.value = false
    if (adminFileRef.value) adminFileRef.value.value = ''
  }
}

const saveAdminProfile = async () => {
  isSavingAdmin.value = true
  adminError.value = ''
  try {
    const adminId = 1
    await axios.put(`/api/v1/admin/users/${adminId}`, {
      name: adminForm.value.name,
      email: adminForm.value.email,
      phone: adminForm.value.phone,
      role: 'super_admin',
      avatar: adminForm.value.avatar
    })

    // Only update authStore if logged-in user is actually the super admin
    if (authStore.user && (authStore.user.role === 'super_admin' || authStore.user.id === 1)) {
      authStore.user.name = adminForm.value.name
      authStore.user.phone = adminForm.value.phone
      authStore.user.avatar_url = adminForm.value.avatar
      localStorage.setItem('auth_user', JSON.stringify(authStore.user))
    }

    adminSuccess.value = 'Admin profile details saved successfully!'
    setTimeout(() => {
      adminSuccess.value = ''
      isAdminModalOpen.value = false
    }, 1500)
  } catch (err: any) {
    adminError.value = err.response?.data?.message || 'Failed to save admin profile.'
  } finally {
    isSavingAdmin.value = false
  }
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/auth/login')
}
</script>

<template>
  <div class="admin-shell">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
      <!-- Logo Branding -->
      <div class="sidebar-brand">
        <router-link to="/admin/dashboard" class="brand-link">
          <div class="logo-mark">
            <img src="/images/logo.png" alt="MotoCar Custom" class="brand-logo-img" />
          </div>
          <div class="brand-text">
            <span class="part-1">MOTO</span><span class="part-2">CAR</span>
            <span class="brand-sub">{{ langStore.t.admin?.commandCenter || 'COMMAND CENTER' }}</span>
          </div>
        </router-link>
      </div>

      <!-- Navigation Links -->
      <nav class="sidebar-nav">
        <div class="nav-section-title">{{ langStore.t.admin?.coreOperations || 'CORE OPERATIONS' }}</div>

        <router-link to="/admin/dashboard" class="nav-item">
          <LayoutDashboard :size="18" />
          <span>{{ langStore.t.admin?.dashboardOverview || 'Dashboard Overview' }}</span>
        </router-link>

        <router-link to="/admin/products" class="nav-item">
          <Package :size="18" />
          <span>{{ langStore.t.admin?.productsInventory || 'Products & Inventory' }}</span>
        </router-link>

        <router-link to="/admin/users" class="nav-item">
          <Users :size="18" />
          <span>{{ langStore.t.admin?.userPasswordControl || 'User & Password Control' }}</span>
        </router-link>

        <router-link to="/admin/orders" class="nav-item">
          <ShoppingBag :size="18" />
          <span>{{ langStore.t.admin?.ordersManagement || 'Orders Management' }}</span>
        </router-link>

        <div class="nav-section-title">{{ langStore.t.admin?.b2bIntelligence || 'B2B & INTELLIGENCE' }}</div>

        <router-link to="/admin/b2b-accounts" class="nav-item">
          <Building2 :size="18" />
          <span>{{ langStore.t.admin?.b2bGaragesRfq || 'B2B Garages & RFQ' }}</span>
        </router-link>

        <router-link to="/admin/reports" class="nav-item">
          <BarChart3 :size="18" />
          <span>{{ langStore.t.admin?.analyticsReports || 'Analytics & Reports' }}</span>
        </router-link>
      </nav>

      <!-- Sidebar Footer -->
      <div class="sidebar-footer">
        <router-link to="/" class="storefront-link" target="_blank">
          <ExternalLink :size="16" />
          <span>{{ langStore.t.admin?.viewStorefront || 'View Public Storefront' }}</span>
        </router-link>

        <button type="button" class="btn-sidebar-logout" @click="handleLogout">
          <LogOut :size="16" />
          <span>{{ langStore.t.admin?.signOutAdmin || 'Sign Out Admin' }}</span>
        </button>
      </div>
    </aside>

    <!-- Main Container -->
    <div class="admin-main-wrap">
      <!-- Top Header Bar -->
      <header class="admin-top-header">
        <div class="header-left">
          <span class="portal-badge">
            <ShieldCheck :size="14" class="text-orange" />
            {{ langStore.t.admin?.adminPrivilegesActive || 'ADMINISTRATOR PRIVILEGES ACTIVE' }}
          </span>
        </div>

        <div class="header-right">
          <!-- Admin Language Switcher Dropdown (Khmer / Chinese / English) -->
          <div class="lang-dropdown-wrapper" @click.stop="isLangDropdownOpen = !isLangDropdownOpen">
            <div class="lang-pill-btn" :title="langStore.t.admin?.selectLanguage || 'Change Language / ផ្លាស់ប្ដូរភាសា / 切换语言'">
              <Globe :size="14" class="text-orange" />
              <span class="lang-flag">{{ langStore.currentOption.flag }}</span>
              <span class="lang-label">{{ langStore.currentOption.nativeName }}</span>
              <ChevronDown :size="12" class="arrow-icon" :class="{ open: isLangDropdownOpen }" />
            </div>

            <transition name="dropdown-fade">
              <div v-if="isLangDropdownOpen" class="lang-dropdown-menu glass-panel" @click.stop>
                <div 
                  v-for="opt in langStore.options" 
                  :key="opt.code"
                  class="lang-item"
                  :class="{ active: langStore.currentLang === opt.code }"
                  @click="selectLanguage(opt.code)"
                >
                  <span class="lang-item-flag">{{ opt.flag }}</span>
                  <div class="lang-item-text">
                    <span class="lang-item-native">{{ opt.nativeName }}</span>
                    <small class="lang-item-sub">{{ opt.label }}</small>
                  </div>
                  <span v-if="langStore.currentLang === opt.code" class="check-mark">✓</span>
                </div>
              </div>
            </transition>
          </div>

          <!-- Admin Profile Pill -->
          <div 
            class="admin-profile-pill" 
            title="Click to view & change Admin Profile / Photo"
            @click="openAdminModal"
            style="cursor: pointer;"
          >
            <div class="admin-avatar-wrap" style="position: relative;">
              <img 
                :src="formatImageUrl(authStore.user?.avatar_url, DEFAULT_AVATAR)" 
                class="admin-avatar" 
                @error="handleAvatarError($event)"
              />
              <span class="avatar-edit-dot" style="position: absolute; bottom: -2px; right: -2px; background: #ff5500; border-radius: 50%; width: 14px; height: 14px; display: flex; align-items: center; justify-content: center; font-size: 8px; border: 1.5px solid #080c16;">📷</span>
            </div>
            <div class="profile-info">
              <strong class="admin-name">{{ authStore.user?.name || 'MotoCar Director' }}</strong>
              <small class="admin-role">{{ langStore.t.admin?.superAdminEdit || 'Super Administrator (Edit)' }}</small>
            </div>
          </div>
        </div>
      </header>

      <!-- Admin Profile Modal -->
      <div v-if="isAdminModalOpen" class="admin-modal-backdrop" @click.self="isAdminModalOpen = false" style="position: fixed; inset: 0; background: rgba(0,0,0,0.75); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem; backdrop-filter: blur(4px);">
        <div class="admin-modal-box glass-panel" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; width: 100%; max-width: 480px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.6);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
              <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,85,0,0.15); display: flex; align-items: center; justify-content: center; color: #ff5500;">
                <User :size="18" />
              </div>
              <h3 style="margin: 0; color: #ffffff; font-size: 1.2rem; font-weight: 700;">{{ langStore.t.admin?.adminProfilePicture || 'Admin Profile & Picture' }}</h3>
            </div>
            <button @click="isAdminModalOpen = false" style="background: none; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer;">✕</button>
          </div>

          <div v-if="adminSuccess" style="background: rgba(34,197,94,0.15); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; padding: 0.65rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
            <CheckCircle :size="16" />
            <span>{{ adminSuccess }}</span>
          </div>

          <div v-if="adminError" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; padding: 0.65rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1.25rem;">
            <span>{{ adminError }}</span>
          </div>

          <!-- Photo Upload Section -->
          <div style="text-align: center; margin-bottom: 1.75rem;">
            <div style="position: relative; width: 90px; height: 90px; margin: 0 auto 0.75rem;">
              <img 
                :src="formatImageUrl(adminForm.avatar || authStore.user?.avatar_url, DEFAULT_AVATAR)" 
                style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #ff5500; box-shadow: 0 0 15px rgba(255,85,0,0.35);" 
                @error="handleAvatarError($event)"
              />
              <button 
                type="button" 
                @click="triggerAdminFile"
                style="position: absolute; bottom: 0; right: 0; width: 30px; height: 30px; background: #ff5500; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid #0f172a; cursor: pointer;"
                title="Upload Photo from PC"
              >
                <Camera :size="14" />
              </button>
              <input 
                ref="adminFileRef" 
                type="file" 
                accept="image/*" 
                style="display: none;" 
                @change="handleAdminAvatarUpload" 
              />
            </div>

            <button 
              type="button" 
              @click="triggerAdminFile"
              :disabled="isUploadingAvatar"
              style="background: rgba(255,85,0,0.12); border: 1px solid rgba(255,85,0,0.3); color: #ff5500; padding: 0.45rem 1rem; border-radius: 8px; font-size: 0.78rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem;"
            >
              <Upload :size="13" />
              <span>{{ isUploadingAvatar ? (langStore.t.admin?.uploading || 'Uploading Picture...') : (langStore.t.admin?.choosePhoto || 'Choose Photo from Computer') }}</span>
            </button>
          </div>

          <!-- Edit Profile Form -->
          <form @submit.prevent="saveAdminProfile" style="display: flex; flex-direction: column; gap: 1rem;">
            <div>
              <label style="display: block; font-size: 0.78rem; color: #94a3b8; font-weight: 600; margin-bottom: 0.35rem;">{{ langStore.t.admin?.adminName || 'Admin Name' }}</label>
              <input 
                v-model="adminForm.name" 
                type="text" 
                required 
                style="width: 100%; background: #080c16; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.65rem 0.85rem; color: white; font-size: 0.9rem;"
              />
            </div>

            <div>
              <label style="display: block; font-size: 0.78rem; color: #94a3b8; font-weight: 600; margin-bottom: 0.35rem;">{{ langStore.t.admin?.emailAddress || 'Email Address' }}</label>
              <input 
                v-model="adminForm.email" 
                type="email" 
                required 
                style="width: 100%; background: #080c16; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.65rem 0.85rem; color: white; font-size: 0.9rem;"
              />
            </div>

            <div>
              <label style="display: block; font-size: 0.78rem; color: #94a3b8; font-weight: 600; margin-bottom: 0.35rem;">{{ langStore.t.admin?.phoneNumber || 'Phone Number' }}</label>
              <input 
                v-model="adminForm.phone" 
                type="text" 
                style="width: 100%; background: #080c16; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.65rem 0.85rem; color: white; font-size: 0.9rem;"
              />
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
              <button 
                type="button" 
                @click="isAdminModalOpen = false"
                style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; padding: 0.65rem 1.25rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer;"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                :disabled="isSavingAdmin"
                style="background: linear-gradient(135deg, #ff5500, #ff7700); border: none; color: white; padding: 0.65rem 1.4rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer;"
              >
                {{ isSavingAdmin ? 'Saving Changes...' : (langStore.t.admin?.saveAdminProfile || 'Save Admin Profile') }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Dynamic Page Content -->
      <main class="admin-page-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
.admin-shell {
  display: flex;
  min-height: 100vh;
  width: 100%;
  max-width: 100vw;
  background: #050811;
  color: #f1f5f9;
  font-family: inherit;
  overflow-x: hidden;
}

/* Sidebar */
.admin-sidebar {
  width: 270px;
  min-width: 270px;
  max-width: 270px;
  flex-shrink: 0;
  background: #080c16;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
  z-index: 50;
}

.sidebar-brand {
  padding: 1.5rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
}

.logo-mark {
  width: 40px;
  height: 40px;
  border-radius: 9px;
  background: #000000;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 0 15px rgba(255, 85, 0, 0.4);
  border: 1px solid rgba(255, 85, 0, 0.5);
  flex-shrink: 0;
}

.brand-logo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.part-1 {
  font-size: 1.15rem;
  font-weight: 900;
  color: #ff5500;
  letter-spacing: -0.02em;
}

.part-2 {
  font-size: 1.15rem;
  font-weight: 900;
  color: #ffffff;
  letter-spacing: -0.02em;
}

.brand-sub {
  font-size: 0.62rem;
  font-weight: 800;
  color: #38bdf8;
  letter-spacing: 0.12em;
  margin-top: -0.1rem;
}

.sidebar-nav {
  padding: 1.25rem 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex: 1;
  overflow-y: auto;
}

.nav-section-title {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #64748b;
  padding: 0.8rem 0.75rem 0.35rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0.9rem;
  color: #94a3b8;
  text-decoration: none;
  font-size: 0.86rem;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}

.nav-item.router-link-active {
  background: rgba(255, 85, 0, 0.15);
  color: #ff7834;
  border: 1px solid rgba(255, 85, 0, 0.3);
  font-weight: 700;
}

.sidebar-footer {
  padding: 1rem 0.85rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.storefront-link {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 0.9rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  text-decoration: none;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.2s;
}

.storefront-link:hover {
  background: rgba(255, 255, 255, 0.08);
  color: white;
}

.btn-sidebar-logout {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.65rem 0.9rem;
  background: none;
  border: none;
  color: #f87171;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
}

.btn-sidebar-logout:hover {
  background: rgba(239, 68, 68, 0.1);
}

/* Main Area */
.admin-main-wrap {
  flex: 1 1 0%;
  display: flex;
  flex-direction: column;
  min-width: 0;
  max-width: calc(100vw - 270px);
  width: calc(100vw - 270px);
  overflow-x: hidden;
}

.admin-top-header {
  height: 68px;
  background: #080c16;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0 1.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 40;
  width: 100%;
  box-sizing: border-box;
}

.portal-badge {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.72rem;
  font-weight: 800;
  color: #cbd5e1;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.3rem 0.75rem;
  border-radius: 9999px;
  letter-spacing: 0.06em;
  flex-shrink: 0;
  white-space: nowrap;
}

.text-orange { color: #ff5500; }

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* Language Dropdown */
.lang-dropdown-wrapper {
  position: relative;
  user-select: none;
}

.lang-pill-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 9999px;
  padding: 0.38rem 0.85rem;
  font-size: 0.82rem;
  color: #e2e8f0;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
}

.lang-pill-btn:hover {
  background: rgba(255, 85, 0, 0.1);
  border-color: rgba(255, 85, 0, 0.35);
  color: #ffffff;
  transform: translateY(-1px);
}

.lang-flag {
  font-size: 1rem;
  line-height: 1;
}

.lang-label {
  font-weight: 600;
  letter-spacing: 0.01em;
}

.arrow-icon {
  color: #94a3b8;
  transition: transform 0.2s ease;
}

.arrow-icon.open {
  transform: rotate(180deg);
}

.lang-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 190px;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  padding: 0.4rem;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.65);
  z-index: 100;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  backdrop-filter: blur(12px);
}

.lang-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.55rem 0.75rem;
  border-radius: 8px;
  color: #cbd5e1;
  font-size: 0.84rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.lang-item:hover {
  background: rgba(255, 85, 0, 0.12);
  color: #ff5500;
}

.lang-item.active {
  background: rgba(255, 85, 0, 0.18);
  color: #ffffff;
  font-weight: 700;
  border: 1px solid rgba(255, 85, 0, 0.3);
}

.lang-item-flag {
  font-size: 1.15rem;
}

.lang-item-text {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.lang-item-native {
  font-weight: 600;
}

.lang-item-sub {
  font-size: 0.7rem;
  color: #94a3b8;
}

.check-mark {
  color: #ff5500;
  font-weight: 900;
  font-size: 0.9rem;
}

.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.admin-profile-pill {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.35rem 0.85rem 0.35rem 0.45rem;
  border-radius: 9999px;
  flex-shrink: 0;
  white-space: nowrap;
}

.admin-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #ff5500;
}

.profile-info {
  display: flex;
  flex-direction: column;
}

.admin-name {
  font-size: 0.82rem;
  color: #ffffff;
  font-weight: 700;
}

.admin-role {
  font-size: 0.68rem;
  color: #ff7834;
}

.admin-page-content {
  flex: 1;
  background: #050811;
  min-width: 0;
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
}

/* Ensure all admin sub-views span full width and align symmetrically with top header padding */
.admin-page-content :deep(.container),
.admin-page-content :deep(.dashboard-inner),
.admin-page-content :deep(.page-container),
.admin-page-content :deep(.admin-page-container) {
  max-width: 100% !important;
  margin: 0 !important;
  padding-left: 1.75rem !important;
  padding-right: 1.75rem !important;
  width: 100% !important;
  box-sizing: border-box !important;
}
</style>
