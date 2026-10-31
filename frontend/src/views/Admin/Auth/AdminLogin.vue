<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  ShieldAlert,
  Lock,
  Mail,
  ArrowRight,
  CheckCircle2,
  AlertCircle,
  Eye,
  EyeOff
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

onMounted(() => {
  email.value = ''
  password.value = ''
})

const handleLogin = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!email.value || !password.value) {
    errorMessage.value = 'Please enter administrator email and password.'
    return
  }

  isSubmitting.value = true
  try {
    await authStore.login(email.value, password.value)

    if (!authStore.isAdmin) {
      errorMessage.value = 'Access denied. This account does not possess Administrator privileges.'
      await authStore.logout()
      return
    }

    successMessage.value = 'Authentication successful! Loading Command Center...'
    setTimeout(() => {
      const redirectPath = (route.query.redirect as string) || '/admin/products'
      router.push(redirectPath)
    }, 600)
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || err.message || 'Invalid administrator credentials.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="admin-login-wrapper">
    <div class="admin-login-card glass-panel">
      <!-- Header -->
      <div class="card-header">
        <div class="logo-circle">
          <ShieldAlert :size="28" class="text-orange" />
        </div>
        <span class="auth-tag">RESTRICTED ACCESS</span>
        <h2 class="auth-title">MotoCar Command Center</h2>
        <p class="auth-sub">Administrative portal for inventory, wholesale operations & security</p>
      </div>

      <!-- Alerts -->
      <div v-if="successMessage" class="alert-box success-box">
        <CheckCircle2 :size="16" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="alert-box error-box">
        <AlertCircle :size="16" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Form with Anti-Autofill -->
      <form @submit.prevent="handleLogin" class="login-form" autocomplete="off">
        <!-- Hidden decoy inputs to suppress Chrome / browser auto-fill -->
        <input style="display:none" type="text" name="fakeusernameremembered" />
        <input style="display:none" type="password" name="fakepasswordremembered" />

        <div class="form-group">
          <label>Admin Email</label>
          <div class="input-wrapper">
            <Mail :size="16" class="field-icon" />
            <input 
              v-model="email" 
              type="email" 
              name="admin_login_account_field"
              id="admin_login_account_field"
              placeholder="Enter admin email address" 
              class="custom-input"
              autocomplete="off"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label>Password</label>
          <div class="input-wrapper">
            <Lock :size="16" class="field-icon" />
            <input 
              v-model="password" 
              :type="showPassword ? 'text' : 'password'" 
              name="admin_login_secure_field"
              id="admin_login_secure_field"
              placeholder="Enter admin password" 
              class="custom-input"
              autocomplete="new-password"
              required
            />
            <button 
              type="button" 
              class="eye-btn" 
              @click="showPassword = !showPassword"
              tabindex="-1"
            >
              <EyeOff v-if="showPassword" :size="16" />
              <Eye v-else :size="16" />
            </button>
          </div>
        </div>

        <button 
          type="submit" 
          class="btn-admin-submit" 
          :disabled="isSubmitting"
        >
          <span>{{ isSubmitting ? 'Authenticating...' : 'Sign In as Administrator' }}</span>
          <ArrowRight :size="16" />
        </button>
      </form>

      <div class="card-footer">
        <router-link to="/" class="back-home-link">
          ← Return to Public Storefront
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: radial-gradient(circle at center, #0f172a 0%, #020617 100%);
  padding: 1.5rem;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 85, 0, 0.2);
  box-shadow: 0 0 50px rgba(0, 0, 0, 0.7);
  border-radius: 16px;
}

.admin-login-card {
  width: 100%;
  max-width: 440px;
  padding: 2.5rem 2rem;
}

.card-header {
  text-align: center;
  margin-bottom: 1.75rem;
}

.logo-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(255, 85, 0, 0.12);
  border: 1px solid rgba(255, 85, 0, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.auth-tag {
  display: inline-block;
  background: rgba(255, 85, 0, 0.15);
  color: #ff7834;
  border: 1px solid rgba(255, 85, 0, 0.35);
  padding: 0.18rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  margin-bottom: 0.5rem;
}

.auth-title {
  color: #ffffff;
  font-size: 1.45rem;
  font-weight: 800;
  margin: 0 0 0.35rem;
}

.auth-sub {
  color: #94a3b8;
  font-size: 0.82rem;
  margin: 0;
  line-height: 1.4;
}

.quick-demo-box {
  background: rgba(255, 255, 255, 0.04);
  border: 1px dashed rgba(255, 85, 0, 0.3);
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.demo-info {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.78rem;
  color: #cbd5e1;
  font-weight: 600;
}

.btn-demo-fill {
  background: rgba(255, 85, 0, 0.15);
  border: 1px solid rgba(255, 85, 0, 0.3);
  color: #ff7834;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}

.btn-demo-fill:hover {
  background: #ff5500;
  color: white;
}

.alert-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 1.25rem;
  font-size: 0.82rem;
}

.success-box {
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #4ade80;
}

.error-box {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group label {
  font-size: 0.8rem;
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
  left: 0.85rem;
  color: #64748b;
  pointer-events: none;
}

.eye-btn {
  position: absolute;
  right: 0.85rem;
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
}

.eye-btn:hover {
  color: #ffffff;
}

.custom-input {
  width: 100%;
  background: rgba(2, 6, 23, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #ffffff;
  padding: 0.75rem 2.4rem 0.75rem 2.4rem;
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
}

.custom-input:focus {
  border-color: #ff5500;
  box-shadow: 0 0 0 3px rgba(255, 85, 0, 0.15);
}

.btn-admin-submit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.85rem;
  border-radius: 8px;
  font-size: 0.92rem;
  font-weight: 800;
  cursor: pointer;
  margin-top: 0.5rem;
  transition: all 0.2s;
}

.btn-admin-submit:hover:not(:disabled) {
  background: #ff6a1f;
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(255, 85, 0, 0.35);
}

.btn-admin-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.card-footer {
  margin-top: 1.75rem;
  text-align: center;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 1.25rem;
}

.back-home-link {
  color: #94a3b8;
  font-size: 0.8rem;
  text-decoration: none;
  transition: color 0.2s;
}

.back-home-link:hover {
  color: #ff5500;
}

.text-orange { color: #ff5500; }
</style>
