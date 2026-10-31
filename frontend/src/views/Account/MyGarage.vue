<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import {
  Car,
  Plus,
  Trash2,
  CheckCircle,
  Wrench,
  ShoppingBag,
  ShieldCheck,
  Sparkles,
  Search,
  Loader2
} from 'lucide-vue-next'

const router = useRouter()

interface Vehicle {
  id: number
  make: string
  model: string
  year: number
  type: 'motorcycle' | 'car'
  trim?: string
  plate?: string
  active?: boolean
}

const savedVehicles = ref<Vehicle[]>([])
const isLoading = ref(true)
const isAddingNew = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const newVehicle = ref<Omit<Vehicle, 'id' | 'active'>>({
  make: '',
  model: '',
  year: 2024,
  type: 'motorcycle',
  trim: '',
  plate: ''
})

const fetchVehicles = async () => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/account/vehicles')
    savedVehicles.value = res.data.data || []
  } catch (err: any) {
    console.error('Failed to fetch vehicles:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchVehicles()
})

const setActiveVehicle = async (id: number) => {
  try {
    await axios.put(`/api/v1/account/vehicles/${id}/primary`)
    savedVehicles.value.forEach(v => {
      v.active = v.id === id
    })
    successMessage.value = 'Active fitment vehicle updated!'
    setTimeout(() => { successMessage.value = '' }, 3000)
  } catch (err: any) {
    errorMessage.value = 'Failed to update active vehicle.'
  }
}

const removeVehicle = async (id: number) => {
  if (confirm('Are you sure you want to remove this vehicle from your garage?')) {
    try {
      await axios.delete(`/api/v1/account/vehicles/${id}`)
      await fetchVehicles()
      successMessage.value = 'Vehicle removed successfully.'
      setTimeout(() => { successMessage.value = '' }, 3000)
    } catch (err: any) {
      errorMessage.value = 'Failed to delete vehicle.'
    }
  }
}

const handleAddVehicle = async () => {
  if (!newVehicle.value.make || !newVehicle.value.model) return

  errorMessage.value = ''
  try {
    await axios.post('/api/v1/account/vehicles', {
      make: newVehicle.value.make,
      model: newVehicle.value.model,
      year: Number(newVehicle.value.year),
      type: newVehicle.value.type,
      trim: newVehicle.value.trim || 'Standard Spec',
      plate: newVehicle.value.plate || 'Custom Plate'
    })

    await fetchVehicles()
    successMessage.value = 'Vehicle added to your garage in database!'
    setTimeout(() => { successMessage.value = '' }, 3500)

    // Reset form
    newVehicle.value = {
      make: '',
      model: '',
      year: 2024,
      type: 'motorcycle',
      trim: '',
      plate: ''
    }
    isAddingNew.value = false
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Failed to save vehicle.'
  }
}

const shopForVehicle = (v: Vehicle) => {
  router.push({
    name: 'shop',
    query: {
      make: v.make,
      model: v.model,
      year: v.year,
      type: v.type
    }
  })
}
</script>

<template>
  <div class="garage-page">
    <div class="container">
      <!-- Header -->
      <div class="garage-top">
        <div>
          <span class="sub-badge">FITMENT GUARANTEE ENGINE</span>
          <h1 class="page-title">My Garage Vehicles</h1>
          <p class="page-subtitle">Add your motorcycles and cars to unlock 100% certified bolt-on compatibility filtering</p>
        </div>
        <button 
          v-if="!isAddingNew" 
          class="add-vehicle-btn" 
          @click="isAddingNew = true"
        >
          <Plus :size="16" />
          <span>Add New Vehicle</span>
        </button>
      </div>

      <!-- Alerts -->
      <div v-if="successMessage" class="alert-box success-box" style="margin-bottom: 1.5rem; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; padding: 0.75rem 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
        <CheckCircle :size="16" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="alert-box error-box" style="margin-bottom: 1.5rem; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 0.75rem 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Add New Vehicle Modal / Drawer Panel -->
      <div v-if="isAddingNew" class="add-panel glass-panel">
        <div class="panel-header">
          <h3>
            <Sparkles :size="18" class="text-orange" />
            <span>Add Vehicle to My Garage</span>
          </h3>
          <button class="close-btn" @click="isAddingNew = false">✕</button>
        </div>

        <form @submit.prevent="handleAddVehicle" class="new-vehicle-form">
          <div class="form-grid">
            <div class="form-group">
              <label>Vehicle Type</label>
              <select v-model="newVehicle.type" class="custom-select">
                <option value="motorcycle">🏍️ Motorcycle / Superbike</option>
                <option value="car">🚗 Performance Car / 4x4 Offroad</option>
              </select>
            </div>

            <div class="form-group">
              <label>Brand / Make</label>
              <input 
                v-model="newVehicle.make" 
                type="text" 
                placeholder="e.g. Honda, Ducati, Ford, Porsche" 
                class="custom-input"
                required
              />
            </div>

            <div class="form-group">
              <label>Model Name</label>
              <input 
                v-model="newVehicle.model" 
                type="text" 
                placeholder="e.g. CB650R, Panigale V4, Raptor" 
                class="custom-input"
                required
              />
            </div>

            <div class="form-group">
              <label>Production Year</label>
              <input 
                v-model="newVehicle.year" 
                type="number" 
                min="1990" 
                max="2026" 
                class="custom-input"
                required
              />
            </div>

            <div class="form-group">
              <label>Sub-Model / Engine Trim</label>
              <input 
                v-model="newVehicle.trim" 
                type="text" 
                placeholder="e.g. Track Spec, Twin-Turbo V6" 
                class="custom-input"
              />
            </div>

            <div class="form-group">
              <label>License Plate / VIN (Optional)</label>
              <input 
                v-model="newVehicle.plate" 
                type="text" 
                placeholder="e.g. Phnom Penh 2AB-1234" 
                class="custom-input"
              />
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn-cancel" @click="isAddingNew = false">Cancel</button>
            <button type="submit" class="btn-save">
              <Plus :size="16" />
              <span>Save Vehicle to Garage</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state glass-panel" style="text-align: center; padding: 4rem 2rem; color: #94a3b8;">
        <Loader2 :size="32" class="spin-icon text-orange" style="animation: spin 1s linear infinite; margin-bottom: 1rem;" />
        <p>Loading your garage vehicles from database...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="savedVehicles.length === 0" class="empty-garage glass-panel" style="text-align: center; padding: 4.5rem 2rem; border-radius: 16px;">
        <div style="width: 80px; height: 80px; background: rgba(255, 85, 0, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; border: 1px solid rgba(255, 85, 0, 0.25);">
          <Car :size="42" class="text-orange" />
        </div>
        <h3 style="font-size: 1.5rem; font-weight: 800; color: #ffffff; margin-bottom: 0.5rem;">Your Garage is Currently Empty</h3>
        <p style="max-width: 500px; margin: 0 auto 1.75rem; color: #94a3b8; font-size: 0.95rem; line-height: 1.6;">
          Add your motorcycle or car to unlock 100% certified bolt-on compatibility filtering for exhausts, brakes, and performance parts!
        </p>
        <button class="add-vehicle-btn" style="display: inline-flex;" @click="isAddingNew = true">
          <Plus :size="16" />
          <span>Add Your First Vehicle</span>
        </button>
      </div>

      <!-- Vehicle Cards Grid -->
      <div v-else class="vehicles-grid">
        <div 
          v-for="v in savedVehicles" 
          :key="v.id"
          class="vehicle-card glass-panel"
          :class="{ 'is-active': v.active }"
        >
          <!-- Card Top Bar -->
          <div class="card-head">
            <div class="type-pill">
              <span>{{ v.type === 'motorcycle' ? '🏍️ SUPERBIKE' : '🏎️ PERFORMANCE CAR' }}</span>
            </div>
            <div v-if="v.active" class="active-badge">
              <CheckCircle :size="13" />
              <span>ACTIVE FITMENT</span>
            </div>
          </div>

          <!-- Vehicle Info -->
          <div class="card-body">
            <div class="vehicle-icon-circle">
              <Car :size="28" class="text-orange" />
            </div>
            <div class="vehicle-texts">
              <span class="vehicle-year">{{ v.year }}</span>
              <h3 class="vehicle-name">{{ v.make }} {{ v.model }}</h3>
              <p class="vehicle-sub">{{ v.trim }}</p>
              <span class="plate-tag" v-if="v.plate">🔖 {{ v.plate }}</span>
            </div>
          </div>

          <!-- Fitment Guarantee Banner -->
          <div class="fitment-guarantee">
            <ShieldCheck :size="14" class="text-green" />
            <span>100% Bolt-On Guaranteed Certified</span>
          </div>

          <!-- Card Actions -->
          <div class="card-footer">
            <button 
              v-if="!v.active" 
              class="btn-action btn-set-active"
              @click="setActiveVehicle(v.id)"
            >
              Set as Active
            </button>
            <button 
              class="btn-action btn-shop" 
              @click="shopForVehicle(v)"
            >
              <ShoppingBag :size="14" />
              <span>Shop Parts</span>
            </button>
            <button 
              class="btn-icon-delete" 
              title="Remove Vehicle"
              @click="removeVehicle(v.id)"
            >
              <Trash2 :size="15" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.garage-page {
  padding: 3rem 0 5rem;
  min-height: 80vh;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.25rem;
}

.garage-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.sub-badge {
  display: inline-block;
  background: rgba(255, 85, 0, 0.12);
  color: #ff7834;
  border: 1px solid rgba(255, 85, 0, 0.3);
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

.add-vehicle-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: #ffffff;
  border: none;
  padding: 0.75rem 1.4rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.add-vehicle-btn:hover {
  background: #ff6a1f;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(255, 85, 0, 0.35);
}

.glass-panel {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

/* Add Panel Form */
.add-panel {
  padding: 2rem;
  margin-bottom: 2.5rem;
  border-color: rgba(255, 85, 0, 0.3);
  box-shadow: 0 0 25px rgba(255, 85, 0, 0.1);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.panel-header h3 {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #ffffff;
  margin: 0;
  font-size: 1.15rem;
}

.close-btn {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.25rem;
  cursor: pointer;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group label {
  font-size: 0.8rem;
  color: #cbd5e1;
  font-weight: 600;
}

.custom-input,
.custom-select {
  background: rgba(2, 6, 23, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #ffffff;
  padding: 0.75rem 0.9rem;
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
}

.custom-input:focus,
.custom-select:focus {
  border-color: #ff5500;
  box-shadow: 0 0 0 3px rgba(255, 85, 0, 0.15);
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-cancel {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #cbd5e1;
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-save {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.65rem 1.4rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}

/* Vehicles Grid */
.vehicles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 1.5rem;
}

.vehicle-card {
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}

.vehicle-card:hover {
  transform: translateY(-4px);
  border-color: rgba(255, 85, 0, 0.35);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
}

.vehicle-card.is-active {
  border-color: #ff5500;
  box-shadow: 0 0 25px rgba(255, 85, 0, 0.2);
}

.vehicle-card.is-active::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #ff5500, #ff8c00);
}

.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.type-pill {
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
}

.active-badge {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.72rem;
  font-weight: 800;
  color: #ff7834;
  background: rgba(255, 85, 0, 0.15);
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  border: 1px solid rgba(255, 85, 0, 0.3);
}

.card-body {
  display: flex;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}

.vehicle-icon-circle {
  width: 58px;
  height: 58px;
  border-radius: 12px;
  background: rgba(255, 85, 0, 0.1);
  border: 1px solid rgba(255, 85, 0, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.vehicle-year {
  font-size: 0.8rem;
  font-weight: 700;
  color: #ff7834;
}

.vehicle-name {
  color: #ffffff;
  font-size: 1.15rem;
  font-weight: 800;
  margin: 0.15rem 0 0.3rem;
}

.vehicle-sub {
  color: #94a3b8;
  font-size: 0.82rem;
  margin: 0 0 0.5rem;
}

.plate-tag {
  display: inline-block;
  background: rgba(2, 6, 23, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  color: #cbd5e1;
}

.fitment-guarantee {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(34, 197, 94, 0.08);
  border: 1px solid rgba(34, 197, 94, 0.2);
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-size: 0.78rem;
  color: #4ade80;
  font-weight: 600;
  margin-bottom: 1.25rem;
}

.card-footer {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 1rem;
}

.btn-action {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.6rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-set-active {
  background: rgba(255, 255, 255, 0.08);
  color: #e2e8f0;
}

.btn-set-active:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
}

.btn-shop {
  background: #ff5500;
  color: white;
}

.btn-shop:hover {
  background: #ff6a1f;
  transform: translateY(-1px);
}

.btn-icon-delete {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #f87171;
  padding: 0.6rem;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon-delete:hover {
  background: #ef4444;
  color: white;
}

@media (max-width: 900px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
