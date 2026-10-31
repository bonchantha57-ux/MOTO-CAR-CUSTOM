<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  User as UserIcon,
  Mail,
  Lock,
  Phone,
  Eye,
  EyeOff,
  ArrowRight,
  ShieldCheck,
  Building2,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  vehicle_interest: 'both',
  agree_terms: true,
})

const showPassword = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const handleRegister = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!form.value.name || !form.value.email || !form.value.password) {
    errorMessage.value = 'Please fill in all required fields.'
    return
  }

  if (form.value.password.length < 6) {
    errorMessage.value = 'Password must be at least 6 characters long.'
    return
  }

  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Passwords do not match.'
    return
  }

  if (!form.value.agree_terms) {
    errorMessage.value = 'Please agree to the Terms of Service & Fitment Policy.'
    return
  }

  isSubmitting.value = true
  try {
    const res: any = await authStore.register({
      name: form.value.name,
      email: form.value.email,
      phone: form.value.phone,
      password: form.value.password,
    })

    successMessage.value = res.message || 'Account created successfully! Welcome to MotoCar Custom.'

    setTimeout(() => {
      router.push('/')
    }, 1000)
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message ||
      err.message ||
      'Registration failed. Please check your information.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="register-card glass-panel">
    <!-- Header Title -->
    <div class="card-top-title">
      <div class="tag-row">
        <span class="auth-tag">NEW MEMBER ENROLLMENT</span>
      </div>
      <h2>Create Your Account</h2>
      <p>Save your vehicle in My Garage, track genuine orders, and access special retail offers</p>
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

    <!-- Register Form -->
    <form class="auth-form" @submit.prevent="handleRegister">
      <!-- Full Name -->
      <div class="form-group">
        <label for="name">Full Name *</label>
        <div class="input-wrapper">
          <UserIcon :size="18" class="field-icon" />
          <input 
            id="name"
            v-model="form.name" 
            type="text" 
            placeholder="e.g. Sokha Rith" 
            required 
            autocomplete="name"
          />
        </div>
      </div>

      <!-- Email Address -->
      <div class="form-group">
        <label for="reg-email">Email Address *</label>
        <div class="input-wrapper">
          <Mail :size="18" class="field-icon" />
          <input 
            id="reg-email"
            v-model="form.email" 
            type="email" 
            placeholder="e.g. yourname@example.com" 
            required 
            autocomplete="email"
          />
        </div>
      </div>

      <!-- Phone Number -->
      <div class="form-group">
        <label for="phone">Phone Number (Optional)</label>
        <div class="input-wrapper">
          <Phone :size="18" class="field-icon" />
          <input 
            id="phone"
            v-model="form.phone" 
            type="tel" 
            placeholder="+855 12 345 678" 
            autocomplete="tel"
          />
        </div>
      </div>

      <!-- Password Grid -->
      <div class="password-grid">
        <div class="form-group">
          <label for="reg-password">Password *</label>
          <div class="input-wrapper">
            <Lock :size="18" class="field-icon" />
            <input 
              id="reg-password"
              v-model="form.password" 
              :type="showPassword ? 'text' : 'password'" 
              placeholder="Min. 6 characters" 
              required 
              autocomplete="new-password"
            />
          </div>
        </div>

        <div class="form-group">
          <div class="label-row">
            <label for="confirm-password">Confirm *</label>
            <button 
              type="button" 
              class="show-pass-btn" 
              @click="showPassword = !showPassword"
              tabindex="-1"
            >
              {{ showPassword ? 'Hide' : 'Show' }}
            </button>
          </div>
          <div class="input-wrapper">
            <Lock :size="18" class="field-icon" />
            <input 
              id="confirm-password"
              v-model="form.password_confirmation" 
              :type="showPassword ? 'text' : 'password'" 
              placeholder="Repeat password" 
              required 
              autocomplete="new-password"
            />
          </div>
        </div>
      </div>

      <!-- Primary Vehicle Interest -->
      <div class="form-group">
        <label>Primary Vehicle Interest</label>
        <div class="interest-options">
          <label class="interest-pill" :class="{ active: form.vehicle_interest === 'motorcycle' }">
            <input v-model="form.vehicle_interest" type="radio" value="motorcycle" />
            <span>🏍️ Motorcycle</span>
          </label>
          <label class="interest-pill" :class="{ active: form.vehicle_interest === 'car' }">
            <input v-model="form.vehicle_interest" type="radio" value="car" />
            <span>🏎️ Car & 4x4</span>
          </label>
          <label class="interest-pill" :class="{ active: form.vehicle_interest === 'both' }">
            <input v-model="form.vehicle_interest" type="radio" value="both" />
            <span>⚡ Both</span>
          </label>
        </div>
      </div>

      <!-- Terms Checkbox -->
      <div class="remember-row">
        <label class="checkbox-container">
          <input v-model="form.agree_terms" type="checkbox" required />
          <span class="checkmark"></span>
          <span class="checkbox-label">
            I agree to the <a href="#terms" class="text-orange">Terms of Service</a> & 100% Fitment Guarantee Policy
          </span>
        </label>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="btn-submit-auth" :disabled="isSubmitting">
        <span v-if="isSubmitting" class="spinner"></span>
        <span v-else class="btn-text">
          <span>Create Customer Account</span>
          <ArrowRight :size="16" />
        </span>
      </button>
    </form>

    <!-- Footer Links -->
    <div class="card-footer-box">
      <p class="new-user-text">
        Already have an account?
        <router-link to="/auth/login" class="register-link">Sign In Here</router-link>
      </p>

      <div class="divider">
        <span>ARE YOU A GARAGE OWNER?</span>
      </div>

      <router-link to="/auth/b2b-register" class="b2b-apply-btn">
        <Building2 :size="16" />
        <span>Register for B2B Wholesale Garage Account</span>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.register-card {
  background: rgba(13, 18, 29, 0.92);
  border: 1px solid rgba(255, 85, 0, 0.3);
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 35px rgba(255, 85, 0, 0.15);
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
  background: rgba(255, 85, 0, 0.15);
  border: 1px solid rgba(255, 85, 0, 0.4);
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

.show-pass-btn {
  background: transparent;
  border: none;
  color: #ff7834;
  font-size: 0.75rem;
  font-weight: 600;
}

.password-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
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
  padding: 0.75rem 0.9rem 0.75rem 2.6rem;
  color: #ffffff;
  font-size: 0.9rem;
  font-family: inherit;
  outline: none;
  transition: all 0.2s;
}

.input-wrapper input:focus {
  border-color: #ff5500;
  box-shadow: 0 0 15px rgba(255, 85, 0, 0.25);
  background: #090e18;
}

/* Vehicle Interest Pills */
.interest-options {
  display: flex;
  gap: 0.5rem;
}

.interest-pill {
  flex: 1;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 0.5rem;
  text-align: center;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.interest-pill input {
  display: none;
}

.interest-pill.active {
  background: rgba(255, 85, 0, 0.2);
  border-color: #ff5500;
  color: #ff7834;
}

/* Checkbox */
.remember-row {
  display: flex;
  align-items: center;
  font-size: 0.82rem;
}

.checkbox-container {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
  color: #94a3b8;
  line-height: 1.4;
}

.checkbox-container input {
  accent-color: #ff5500;
  width: 16px;
  height: 16px;
  margin-top: 2px;
}

.text-orange {
  color: #ff5500;
}

/* Submit Button */
.btn-submit-auth {
  margin-top: 0.5rem;
  width: 100%;
  background: linear-gradient(135deg, #ff5500 0%, #ea580c 100%);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 0.85rem 1.5rem;
  font-weight: 800;
  font-size: 0.95rem;
  box-shadow: 0 4px 18px rgba(255, 85, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-submit-auth:hover:not(:disabled) {
  background: #e04b00;
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(255, 85, 0, 0.5);
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
  color: #ff5500;
  font-weight: 700;
  margin-left: 0.35rem;
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
  font-size: 0.72rem;
  font-weight: 800;
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

@media (max-width: 500px) {
  .password-grid {
    grid-template-columns: 1fr;
  }
}
</style>
