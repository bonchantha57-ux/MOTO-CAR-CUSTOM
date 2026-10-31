<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  Building2,
  User as UserIcon,
  Mail,
  Lock,
  Phone,
  MapPin,
  FileText,
  Wrench,
  ArrowRight,
  ShieldCheck,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
  contact_name: '',
  company_name: '',
  email: '',
  phone: '',
  password: '',
  address: '',
  city: 'Phnom Penh',
  tax_number: '',
  specialty: 'Superbike & Car Custom Performance',
})

const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const handleB2bRegister = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!form.value.company_name || !form.value.contact_name || !form.value.email || !form.value.phone || !form.value.password || !form.value.address) {
    errorMessage.value = 'Please fill in all required workshop registration fields.'
    return
  }

  isSubmitting.value = true
  try {
    const res: any = await authStore.b2bRegister(form.value)
    successMessage.value = res.message || 'B2B Wholesale Account registered successfully!'

    setTimeout(() => {
      router.push('/shop?b2b=true')
    }, 1200)
  } catch (err: any) {
    errorMessage.value =
      err.response?.data?.message ||
      err.message ||
      'B2B registration failed. Please check your information.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="b2b-register-card glass-panel">
    <!-- Header Title -->
    <div class="card-top-title">
      <div class="tag-row">
        <span class="auth-tag b2b-tag">WORKSHOP & DISTRIBUTOR ENROLLMENT</span>
      </div>
      <h2>Register B2B Wholesale Garage</h2>
      <p>Unlock wholesale tier pricing, RFQ bulk ordering, and official installation customer referrals</p>
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

    <!-- B2B Form -->
    <form class="auth-form" @submit.prevent="handleB2bRegister">
      <!-- Row 1: Workshop Name & Contact Person -->
      <div class="form-row">
        <div class="form-group">
          <label>Workshop / Garage Name *</label>
          <div class="input-wrapper">
            <Building2 :size="18" class="field-icon" />
            <input 
              v-model="form.company_name" 
              type="text" 
              placeholder="e.g. Apex Speed Garage" 
              required 
            />
          </div>
        </div>

        <div class="form-group">
          <label>Manager / Contact Name *</label>
          <div class="input-wrapper">
            <UserIcon :size="18" class="field-icon" />
            <input 
              v-model="form.contact_name" 
              type="text" 
              placeholder="e.g. Vannak Heng" 
              required 
            />
          </div>
        </div>
      </div>

      <!-- Row 2: Email & Phone -->
      <div class="form-row">
        <div class="form-group">
          <label>Business Email *</label>
          <div class="input-wrapper">
            <Mail :size="18" class="field-icon" />
            <input 
              v-model="form.email" 
              type="email" 
              placeholder="e.g. garage@example.com" 
              required 
            />
          </div>
        </div>

        <div class="form-group">
          <label>Contact Phone (Telegram) *</label>
          <div class="input-wrapper">
            <Phone :size="18" class="field-icon" />
            <input 
              v-model="form.phone" 
              type="tel" 
              placeholder="+855 23 888 111" 
              required 
            />
          </div>
        </div>
      </div>

      <!-- Workshop Location -->
      <div class="form-group">
        <label>Workshop Physical Address *</label>
        <div class="input-wrapper">
          <MapPin :size="18" class="field-icon" />
          <input 
            v-model="form.address" 
            type="text" 
            placeholder="e.g. #88 St 271, Sangkat Toul Tumpoung, Phnom Penh" 
            required 
          />
        </div>
      </div>

      <!-- Specialization & Tax ID -->
      <div class="form-row">
        <div class="form-group">
          <label>Workshop Specialty</label>
          <div class="input-wrapper">
            <Wrench :size="18" class="field-icon" />
            <input 
              v-model="form.specialty" 
              type="text" 
              placeholder="e.g. Dyno Tuning, Carbon Body, Akrapovic" 
            />
          </div>
        </div>

        <div class="form-group">
          <label>Patent / Tax Number (Optional)</label>
          <div class="input-wrapper">
            <FileText :size="18" class="field-icon" />
            <input 
              v-model="form.tax_number" 
              type="text" 
              placeholder="e.g. K001-902..." 
            />
          </div>
        </div>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label>Account Password *</label>
        <div class="input-wrapper">
          <Lock :size="18" class="field-icon" />
          <input 
            v-model="form.password" 
            type="password" 
            placeholder="Set a secure password (min. 6 characters)" 
            required 
          />
        </div>
      </div>

      <!-- B2B Benefits Highlight Box -->
      <div class="b2b-perks-box">
        <div class="perk-item">
          <ShieldCheck :size="16" class="text-cyan" />
          <span>Instant Access to Wholesale Tier 1 & Tier 2 Pricing</span>
        </div>
        <div class="perk-item">
          <ShieldCheck :size="16" class="text-cyan" />
          <span>Priority Tech Support & Direct Fitment Consulting</span>
        </div>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="btn-submit-b2b" :disabled="isSubmitting">
        <span v-if="isSubmitting" class="spinner"></span>
        <span v-else class="btn-text">
          <span>Submit Wholesale Partner Registration</span>
          <ArrowRight :size="16" />
        </span>
      </button>
    </form>

    <!-- Footer Links -->
    <div class="card-footer-box">
      <p class="new-user-text">
        Already have a B2B or Retail Account?
        <router-link to="/auth/login" class="login-link">Sign In Here</router-link>
      </p>
    </div>
  </div>
</template>

<style scoped>
.b2b-register-card {
  background: rgba(13, 18, 29, 0.94);
  border: 1px solid rgba(0, 210, 255, 0.4);
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 35px rgba(0, 210, 255, 0.15);
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

.auth-tag.b2b-tag {
  display: inline-block;
  background: rgba(0, 210, 255, 0.15);
  border: 1px solid rgba(0, 210, 255, 0.4);
  color: #00d2ff;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

label {
  font-size: 0.8rem;
  font-weight: 700;
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
  border-color: #00d2ff;
  box-shadow: 0 0 15px rgba(0, 210, 255, 0.25);
  background: #090e18;
}

.b2b-perks-box {
  background: rgba(0, 210, 255, 0.08);
  border: 1px solid rgba(0, 210, 255, 0.2);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.perk-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: #e0f2fe;
  font-weight: 600;
}

.text-cyan {
  color: #00d2ff;
}

/* Submit Button */
.btn-submit-b2b {
  margin-top: 0.5rem;
  width: 100%;
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
  color: white;
  border: none;
  border-radius: 10px;
  padding: 0.85rem 1.5rem;
  font-weight: 800;
  font-size: 0.95rem;
  box-shadow: 0 4px 18px rgba(2, 132, 199, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-submit-b2b:hover:not(:disabled) {
  background: #0095de;
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(0, 210, 255, 0.5);
}

.btn-submit-b2b:disabled {
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
}

.login-link {
  color: #00d2ff;
  font-weight: 700;
  margin-left: 0.35rem;
}

.login-link:hover {
  text-decoration: underline;
}

@media (max-width: 600px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
