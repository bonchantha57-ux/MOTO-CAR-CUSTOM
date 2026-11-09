<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/services/api'
import axios from 'axios'
import {
  User as UserIcon,
  Mail,
  Phone,
  Lock,
  Save,
  CheckCircle2,
  AlertCircle,
  Building2,
  ShieldCheck,
  Sparkles,
  Camera,
  Upload
} from 'lucide-vue-next'
import { formatImageUrl, handleAvatarError, DEFAULT_USER_AVATAR } from '@/utils/image'

const authStore = useAuthStore()

const form = ref({
  name: '',
  phone: '',
  avatar: '',
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const isSubmitting = ref(false)
const isUploading = ref(false)
const fileInputRef = ref<HTMLInputElement | null>(null)
const successMessage = ref('')
const errorMessage = ref('')

onMounted(() => {
  if (authStore.user) {
    form.value.name = authStore.user.name || ''
    form.value.phone = authStore.user.phone || ''
    form.value.avatar = authStore.user.avatar_url || ''
  }
})

const triggerFileInput = () => {
  fileInputRef.value?.click()
}

const handleFileUpload = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  const file = target.files[0]

  isUploading.value = true
  errorMessage.value = ''
  try {
    const formData = new FormData()
    formData.append('image', file)
    formData.append('folder', 'avatars')

    const res = await axios.post('/api/v1/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (res.data.url) {
      const savedUrl = formatImageUrl(res.data.url)
      form.value.avatar = savedUrl
      // Instantly save to user in database
      const profileRes: any = await authApi.updateProfile({
        name: form.value.name || authStore.user?.name || 'Member',
        phone: form.value.phone,
        avatar: savedUrl
      } as any)
      if (profileRes.user) {
        authStore.user = profileRes.user
        localStorage.setItem('auth_user', JSON.stringify(profileRes.user))
      }
      successMessage.value = 'Profile picture updated & saved successfully!'
      setTimeout(() => { successMessage.value = '' }, 4000)
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Failed to upload profile picture.'
  } finally {
    isUploading.value = false
    if (fileInputRef.value) fileInputRef.value.value = ''
  }
}

const handleSave = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!form.value.name) {
    errorMessage.value = 'Full name is required.'
    return
  }

  if (form.value.new_password) {
    if (form.value.new_password.length < 6) {
      errorMessage.value = 'New password must be at least 6 characters long.'
      return
    }
    if (form.value.new_password !== form.value.new_password_confirmation) {
      errorMessage.value = 'New passwords do not match.'
      return
    }
    if (!form.value.current_password) {
      errorMessage.value = 'Current password is required to set a new password.'
      return
    }
  }

  isSubmitting.value = true
  try {
    const payload: any = {
      name: form.value.name,
      phone: form.value.phone,
      avatar: form.value.avatar
    }
    if (form.value.new_password) {
      payload.current_password = form.value.current_password
      payload.new_password = form.value.new_password
    }

    const res: any = await authApi.updateProfile(payload)
    if (res.user) {
      authStore.user = res.user
      localStorage.setItem('auth_user', JSON.stringify(res.user))
    }
    successMessage.value = res.message || 'Profile updated successfully!'
    form.value.current_password = ''
    form.value.new_password = ''
    form.value.new_password_confirmation = ''
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || err.message || 'Failed to update profile.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="profile-page-container">
    <div class="container">
      <!-- Breadcrumb / Header -->
      <div class="page-top-header">
        <div>
          <span class="sub-badge">ACCOUNT PREFERENCES</span>
          <h1 class="page-title">Personal Profile & Security</h1>
          <p class="page-subtitle">Manage your contact details, security credentials, and automotive preferences</p>
        </div>
      </div>

      <!-- Status Alerts -->
      <div v-if="successMessage" class="status-alert success-alert">
        <CheckCircle2 :size="18" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="status-alert error-alert">
        <AlertCircle :size="18" />
        <span>{{ errorMessage }}</span>
      </div>

      <div class="profile-grid">
        <!-- Left: Profile Summary Card -->
        <div class="summary-card glass-panel">
          <div class="avatar-holder">
            <img 
              :src="formatImageUrl(form.avatar || authStore.user?.avatar_url, DEFAULT_USER_AVATAR)" 
              class="avatar-img" 
              @error="handleAvatarError($event, DEFAULT_USER_AVATAR)"
            />
            <button 
              type="button" 
              class="avatar-camera-btn" 
              title="Click to choose a photo from your computer"
              @click="triggerFileInput"
            >
              <Camera :size="16" />
            </button>
            <input 
              ref="fileInputRef" 
              type="file" 
              accept="image/*" 
              style="display: none;" 
              @change="handleFileUpload" 
            />
            <span class="role-badge" :class="authStore.isB2B ? 'role-b2b' : 'role-b2c'">
              {{ authStore.isAdmin ? '👑 ADMIN' : (authStore.isB2B ? '🏢 B2B GARAGE' : '⚡ VIP MEMBER') }}
            </span>
          </div>

          <button 
            type="button" 
            class="btn-upload-avatar" 
            :disabled="isUploading"
            @click="triggerFileInput"
          >
            <Upload :size="14" />
            <span>{{ isUploading ? 'Uploading Image...' : 'Upload Profile Picture' }}</span>
          </button>

          <h3 class="user-display-name">{{ authStore.user?.name || 'Member' }}</h3>
          <p class="user-email">{{ authStore.user?.email }}</p>

          <div class="card-meta-list">
            <div class="meta-row">
              <span class="lbl">Account Status</span>
              <span class="val text-green">
                <span class="dot-live"></span> Verified Active
              </span>
            </div>
            <div class="meta-row">
              <span class="lbl">Member Tier</span>
              <span class="val text-orange">
                {{ authStore.isB2B ? 'B2B Wholesale Tier 1' : 'Retail Premium Driver' }}
              </span>
            </div>
            <div class="meta-row">
              <span class="lbl">Fitment Guarantee</span>
              <span class="val text-cyan">100% Certified</span>
            </div>
          </div>
        </div>

        <!-- Right: Edit Form Card -->
        <div class="form-card glass-panel">
          <form @submit.prevent="handleSave">
            <!-- Basic Information Section -->
            <div class="form-section">
              <h3 class="section-title">
                <UserIcon :size="18" class="text-orange" />
                <span>Basic Information</span>
              </h3>

              <div class="form-row">
                <div class="form-group">
                  <label class="input-lbl">Full Name</label>
                  <div class="input-wrapper">
                    <UserIcon :size="16" class="field-icon" />
                    <input 
                      v-model="form.name" 
                      type="text" 
                      placeholder="e.g. Sok Piseth" 
                      class="custom-input"
                      required
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="input-lbl">Email Address (Read-only)</label>
                  <div class="input-wrapper disabled">
                    <Mail :size="16" class="field-icon" />
                    <input 
                      :value="authStore.user?.email" 
                      type="email" 
                      class="custom-input"
                      disabled
                    />
                  </div>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="input-lbl">Phone Number (Phnom Penh / Express)</label>
                  <div class="input-wrapper">
                    <Phone :size="16" class="field-icon" />
                    <input 
                      v-model="form.phone" 
                      type="tel" 
                      placeholder="+855 12 345 678" 
                      class="custom-input"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="input-lbl">Profile Avatar Image URL</label>
                  <div class="input-wrapper">
                    <Sparkles :size="16" class="field-icon" />
                    <input 
                      v-model="form.avatar" 
                      type="url" 
                      placeholder="https://..." 
                      class="custom-input"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="section-divider"></div>

            <!-- Security & Password Change Section -->
            <div class="form-section">
              <h3 class="section-title">
                <Lock :size="18" class="text-orange" />
                <span>Security & Password (Optional)</span>
              </h3>
              <p class="section-desc">Leave password fields blank if you do not wish to change your current password.</p>

              <div class="form-row">
                <div class="form-group">
                  <label class="input-lbl">Current Password</label>
                  <div class="input-wrapper">
                    <Lock :size="16" class="field-icon" />
                    <input 
                      v-model="form.current_password" 
                      type="password" 
                      placeholder="Enter current password" 
                      class="custom-input"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="input-lbl">New Password</label>
                  <div class="input-wrapper">
                    <Lock :size="16" class="field-icon" />
                    <input 
                      v-model="form.new_password" 
                      type="password" 
                      placeholder="At least 6 characters" 
                      class="custom-input"
                    />
                  </div>
                </div>
              </div>

              <div class="form-group" style="max-width: 48%;">
                <label class="input-lbl">Confirm New Password</label>
                <div class="input-wrapper">
                  <Lock :size="16" class="field-icon" />
                  <input 
                    v-model="form.new_password_confirmation" 
                    type="password" 
                    placeholder="Repeat new password" 
                    class="custom-input"
                  />
                </div>
              </div>
            </div>

            <!-- Submit Action Bar -->
            <div class="form-actions">
              <button 
                type="submit" 
                class="save-btn" 
                :disabled="isSubmitting"
              >
                <Save :size="16" />
                <span>{{ isSubmitting ? 'Saving Changes...' : 'Save Profile Changes' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile-page-container {
  padding: 3rem 0 5rem;
  min-height: 80vh;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.25rem;
}

.page-top-header {
  margin-bottom: 2rem;
}

.sub-badge {
  display: inline-block;
  background: rgba(249, 115, 22, 0.12);
  color: #ff7834;
  border: 1px solid rgba(249, 115, 22, 0.3);
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.page-title {
  font-size: 1.85rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.35rem;
}

.page-subtitle {
  color: #94a3b8;
  font-size: 0.95rem;
  margin: 0;
}

.status-alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1.25rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
}

.success-alert {
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #4ade80;
}

.error-alert {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.profile-grid {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 2rem;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

/* Left Summary Card */
.summary-card {
  padding: 2rem 1.5rem;
  text-align: center;
  height: fit-content;
}

.avatar-holder {
  position: relative;
  width: 100px;
  height: 100px;
  margin: 0 auto 1.25rem;
}

.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--orange-500);
  box-shadow: 0 0 20px rgba(249, 115, 22, 0.3);
}

.role-badge {
  position: absolute;
  bottom: -6px;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  border: 1px solid transparent;
}

.role-b2c {
  background: var(--orange-500);
  color: #ffffff;
}

.role-b2b {
  background: #0284c7;
  color: #ffffff;
  border-color: #38bdf8;
}

.avatar-camera-btn {
  position: absolute;
  top: 0;
  right: -4px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--orange-500);
  color: white;
  border: 2px solid #0b0f19;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
}

.avatar-camera-btn:hover {
  background: #ff6a1a;
  transform: scale(1.1);
}

.btn-upload-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  background: rgba(249, 115, 22, 0.12);
  color: var(--orange-500);
  border: 1px solid rgba(249, 115, 22, 0.3);
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  margin-bottom: 1rem;
  transition: all 0.2s ease;
}

.btn-upload-avatar:hover {
  background: var(--orange-500);
  color: #ffffff;
  border-color: var(--orange-500);
}

.user-display-name {
  color: #ffffff;
  font-size: 1.25rem;
  margin: 0 0 0.25rem;
}

.user-email {
  color: #94a3b8;
  font-size: 0.82rem;
  margin: 0 0 1.5rem;
}

.card-meta-list {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  text-align: left;
}

.meta-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.82rem;
}

.meta-row .lbl {
  color: #64748b;
}

.meta-row .val {
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.dot-live {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 6px #22c55e;
}

.text-green { color: #4ade80; }
.text-orange { color: #ff7834; }
.text-cyan { color: #38bdf8; }

/* Right Form Card */
.form-card {
  padding: 2.25rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1.1rem;
  color: #ffffff;
  margin: 0 0 0.35rem;
}

.section-desc {
  color: #94a3b8;
  font-size: 0.82rem;
  margin: 0 0 1.25rem;
}

.section-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.06);
  margin: 2rem 0;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.input-lbl {
  font-size: 0.82rem;
  font-weight: 600;
  color: #cbd5e1;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.field-icon {
  position: absolute;
  left: 0.9rem;
  color: #64748b;
  pointer-events: none;
}

.custom-input {
  width: 100%;
  background: rgba(2, 6, 23, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #ffffff;
  padding: 0.75rem 0.9rem 0.75rem 2.5rem;
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
}

.custom-input:focus {
  border-color: var(--orange-500);
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
}

.input-wrapper.disabled .custom-input {
  background: rgba(255, 255, 255, 0.03);
  color: #64748b;
  cursor: not-allowed;
  border-color: rgba(255, 255, 255, 0.06);
}

.form-actions {
  margin-top: 2rem;
  display: flex;
  justify-content: flex-end;
}

.save-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--orange-500);
  color: white;
  border: none;
  padding: 0.8rem 1.6rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.save-btn:hover:not(:disabled) {
  background: #ff6a1f;
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(249, 115, 22, 0.35);
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
