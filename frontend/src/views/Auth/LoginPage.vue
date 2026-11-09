<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  Mail,
  Lock,
  Eye,
  EyeOff,
  ArrowRight,
  Building2,
  User as UserIcon,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const loginType = ref<'customer' | 'b2b'>('customer')
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const rememberMe = ref(false)

const errorMessage = ref('')
const successMessage = ref('')
const isSubmitting = ref(false)

onMounted(() => {
  email.value = ''
  password.value = ''
})

const handleLogin = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!email.value || !password.value) {
    errorMessage.value = 'Please enter both email and password.'
    return
  }

  isSubmitting.value = true
  try {
    const res: any = await authStore.login(email.value, password.value)
    successMessage.value = res.message || 'Login successful! Redirecting...'

    setTimeout(() => {
      const redirectPath = (route.query.redirect as string) || (authStore.isAdmin ? '/admin/dashboard' : '/')
      router.push(redirectPath)
    }, 800)
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message ||
      err.message ||
      'Invalid email or password. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="login-card glass-panel">
    <!-- Header Title -->
    <div class="card-top-title">
      <div class="tag-row">
        <span class="auth-tag">ACCOUNT ACCESS</span>
      </div>
      <h2>Sign In to MotoCar Custom</h2>
      <p>Access your saved vehicles, garage order fitments, and wholesale catalog</p>
    </div>

    <!-- Mode Selector Tabs -->
    <div class="login-type-tabs">
      <button 
        type="button" 
        class="tab-btn" 
        :class="{ active: loginType === 'customer' }"
        @click="loginType = 'customer'"
      >
        <UserIcon :size="16" />
        <span>Retail Consumer</span>
      </button>

      <button 
        type="button" 
        class="tab-btn b2b-tab" 
        :class="{ active: loginType === 'b2b' }"
        @click="loginType = 'b2b'"
      >
        <Building2 :size="16" />
        <span>B2B Garage Partner</span>
      </button>
    </div>

    <!-- Alert Messages -->
    <div v-if="errorMessage" class="auth-alert error-alert">
      <AlertCircle :size="18" class="alert-icon" />
      <span>{{ errorMessage }}</span>
    </div>

    <div v-if="successMessage" class="auth-alert success-alert">
      <CheckCircle2 :size="18" class="alert-icon" />
      <span>{{ successMessage }}</span>
    </div>

    <!-- Login Form with Anti-Autofill -->
    <form class="auth-form" @submit.prevent="handleLogin" autocomplete="off">
      <!-- Decoy inputs to suppress browser Gmail/password auto-fill popups -->
      <input style="display:none" type="text" name="fakeusernameremembered" />
      <input style="display:none" type="password" name="fakepasswordremembered" />

      <!-- Email Field -->
      <div class="form-group">
        <label for="client_auth_email_field">Email Address</label>
        <div class="input-wrapper">
          <Mail :size="18" class="field-icon" />
          <input 
            id="client_auth_email_field"
            name="client_auth_email_field"
            v-model="email" 
            type="email" 
            placeholder="Enter your email address" 
            required 
            autocomplete="off"
          />
        </div>
      </div>

      <!-- Password Field -->
      <div class="form-group">
        <div class="label-row">
          <label for="client_auth_password_field">Password</label>
          <router-link to="/auth/forgot-password" class="forgot-link">Forgot password?</router-link>
        </div>
        <div class="input-wrapper">
          <Lock :size="18" class="field-icon" />
          <input 
            id="client_auth_password_field"
            name="client_auth_password_field"
            v-model="password" 
            :type="showPassword ? 'text' : 'password'" 
            placeholder="Enter your password" 
            required 
            autocomplete="new-password"
          />
          <button 
            type="button" 
            class="toggle-eye-btn" 
            @click="showPassword = !showPassword"
            tabindex="-1"
          >
            <EyeOff v-if="showPassword" :size="17" />
            <Eye v-else :size="17" />
          </button>
        </div>
      </div>

      <!-- Remember Me -->
      <div class="remember-row">
        <label class="checkbox-container">
          <input v-model="rememberMe" type="checkbox" />
          <span class="checkmark"></span>
          <span class="checkbox-label">Keep me logged in on this browser</span>
        </label>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="btn-submit-auth" :disabled="isSubmitting">
        <span v-if="isSubmitting" class="spinner"></span>
        <span v-else class="btn-text">
          <span>Sign In as {{ loginType === 'b2b' ? 'B2B Partner' : 'Consumer' }}</span>
          <ArrowRight :size="16" />
        </span>
      </button>
    </form>

    <!-- Footer Links to Register -->
    <div class="card-footer-box">
      <p class="new-user-text">
        Don't have an account yet?
        <router-link to="/auth/register" class="register-link">Create an Account</router-link>
      </p>

      <div class="divider">
        <span>OR</span>
      </div>

      <router-link to="/auth/b2b-register" class="b2b-apply-btn">
        <Building2 :size="16" />
        <span>Register Workshop / Garage for Wholesale Account</span>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.login-card {
  background: rgba(13, 18, 29, 0.92);
  border: 1px solid rgba(249, 115, 22, 0.3);
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 35px rgba(249, 115, 22, 0.15);
  border-radius: 16px;
  padding: 2.25rem 2rem;
  backdrop-filter: blur(20px);
}

.card-top-title {
  text-align: center;
  margin-bottom: 1.5rem;
}

.tag-row {
  margin-bottom: 0.5rem;
}

.auth-tag {
  display: inline-block;
  background: rgba(249, 115, 22, 0.15);
  border: 1px solid rgba(249, 115, 22, 0.4);
  color: #ff7834;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.card-top-title h2 {
  font-size: 1.75rem;
  font-weight: 900;
  color: #ffffff;
  margin-bottom: 0.4rem;
}

.card-top-title p {
  color: #94a3b8;
  font-size: 0.86rem;
  line-height: 1.45;
}

/* Tabs */
.login-type-tabs {
  display: flex;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 4px;
  border-radius: 10px;
  margin-bottom: 1.25rem;
  gap: 4px;
}

.tab-btn {
  flex: 1;
  background: transparent;
  border: none;
  color: #94a3b8;
  font-weight: 700;
  font-size: 0.84rem;
  padding: 0.6rem 0.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  transition: all 0.2s;
}

.tab-btn.active {
  background: var(--orange-500);
  color: white;
  box-shadow: 0 4px 15px rgba(249, 115, 22, 0.35);
}

.tab-btn.b2b-tab.active {
  background: #0284c7;
  box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4);
}

/* Quick Demo Box */
.quick-demo-box {
  background: rgba(255, 255, 255, 0.03);
  border: 1px dashed rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 0.6rem 0.85rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.demo-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: #cbd5e1;
}

.text-orange {
  color: var(--orange-500);
}

.demo-buttons {
  display: flex;
  gap: 0.4rem;
}

.demo-btn {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #f1f5f9;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.25rem 0.55rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.demo-btn:hover {
  background: var(--orange-500);
  border-color: var(--orange-500);
}

.demo-btn.b2b:hover {
  background: #0284c7;
  border-color: #0284c7;
}

.demo-btn.admin:hover {
  background: #a855f7;
  border-color: #a855f7;
}

/* Alerts */
.auth-alert {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.84rem;
  margin-bottom: 1.25rem;
  font-weight: 600;
}

.error-alert {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
}

.success-alert {
  background: rgba(34, 197, 94, 0.15);
  border: 1px solid rgba(34, 197, 94, 0.4);
  color: #86efac;
}

.alert-icon {
  flex-shrink: 0;
}

/* Form */
.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

label {
  font-size: 0.8rem;
  font-weight: 700;
  color: #cbd5e1;
}

.forgot-link {
  font-size: 0.76rem;
  color: #ff7834;
  font-weight: 600;
  transition: color 0.2s;
}

.forgot-link:hover {
  color: var(--orange-500);
  text-decoration: underline;
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

.input-wrapper input {
  width: 100%;
  background: #080c14;
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 10px;
  padding: 0.75rem 2.8rem 0.75rem 2.6rem;
  color: #ffffff;
  font-size: 0.9rem;
  font-family: inherit;
  outline: none;
  transition: all 0.2s;
}

.input-wrapper input:focus {
  border-color: var(--orange-500);
  box-shadow: 0 0 15px rgba(249, 115, 22, 0.25);
  background: #090e18;
}

.toggle-eye-btn {
  position: absolute;
  right: 0.85rem;
  background: transparent;
  border: none;
  color: #64748b;
  display: flex;
  align-items: center;
  padding: 0.2rem;
  transition: color 0.2s;
}

.toggle-eye-btn:hover {
  color: #cbd5e1;
}

/* Checkbox */
.remember-row {
  display: flex;
  align-items: center;
  font-size: 0.82rem;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
  color: #94a3b8;
}

.checkbox-container input {
  accent-color: var(--orange-500);
  width: 16px;
  height: 16px;
}

/* Submit Button */
.btn-submit-auth {
  margin-top: 0.5rem;
  width: 100%;
  background: linear-gradient(135deg, var(--orange-500) 0%, #ea580c 100%);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 0.85rem 1.5rem;
  font-weight: 800;
  font-size: 0.95rem;
  box-shadow: 0 4px 18px rgba(249, 115, 22, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-submit-auth:hover:not(:disabled) {
  background: #e04b00;
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(249, 115, 22, 0.5);
}

.btn-submit-auth:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-text {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Footer Box */
.card-footer-box {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  text-align: center;
}

.new-user-text {
  font-size: 0.86rem;
  color: #94a3b8;
  margin-bottom: 1.25rem;
}

.register-link {
  color: var(--orange-500);
  font-weight: 700;
  margin-left: 0.35rem;
  transition: text-decoration 0.2s;
}

.register-link:hover {
  text-decoration: underline;
}

.divider {
  position: relative;
  text-align: center;
  margin-bottom: 1.25rem;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
}

.divider span {
  position: relative;
  background: #0d121d;
  padding: 0 0.8rem;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 700;
}

.b2b-apply-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  background: rgba(2, 132, 199, 0.12);
  border: 1px solid rgba(56, 189, 248, 0.35);
  color: #38bdf8;
  padding: 0.75rem 1rem;
  border-radius: 9px;
  font-size: 0.84rem;
  font-weight: 700;
  transition: all 0.2s;
}

.b2b-apply-btn:hover {
  background: #0284c7;
  color: white;
}
</style>
