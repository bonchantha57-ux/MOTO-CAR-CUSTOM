<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useLanguageStore } from '@/stores/language'
import axios from 'axios'
import {
  Building2,
  Search,
  CheckCircle2,
  Clock,
  Phone,
  Mail,
  MapPin,
  ShieldCheck,
  TrendingUp,
  CreditCard
} from 'lucide-vue-next'

const langStore = useLanguageStore()
const garages = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')

const fetchGarages = async () => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/admin/garages')
    garages.value = res.data.data || []
  } catch (err) {
    console.error('Failed to load garages:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchGarages()
})

const updateStatus = async (garage: any, newStatus: string) => {
  try {
    await axios.put(`/api/v1/admin/garages/${garage.id}/status`, { status: newStatus })
    garage.status = newStatus
  } catch (err) {
    console.error('Failed to update status:', err)
  }
}
</script>

<template>
  <div class="b2b-accounts-page">
    <div class="container">
      <div class="page-top-bar">
        <div>
          <span class="sub-badge">{{ langStore.t.admin?.b2bIntelligence || 'WHOLESALE NETWORK' }}</span>
          <h1 class="page-title">{{ langStore.t.admin?.b2bGaragesRfq || 'B2B Partner Garages & Accounts' }}</h1>
          <p class="page-sub">Certified Dyno tuning shops, aftermarket garages, and trade wholesale partners in Cambodia</p>
        </div>
      </div>

      <!-- Garage Cards Grid -->
      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <span>Loading B2B accounts...</span>
      </div>

      <div v-else class="garages-list">
        <div v-for="g in garages" :key="g.id" class="garage-card glass-panel">
          <div class="garage-top">
            <div class="garage-brand-info">
              <div class="garage-icon-box">
                <Building2 :size="24" class="text-orange" />
              </div>
              <div>
                <h3 class="garage-name">{{ g.name }}</h3>
                <span class="tier-pill">Tier 1 Wholesale Garage</span>
              </div>
            </div>

            <div class="garage-status-box">
              <span class="status-badge" :class="g.status === 'active' ? 'status-active' : 'status-pending'">
                {{ g.status?.toUpperCase() || 'ACTIVE' }}
              </span>
            </div>
          </div>

          <p class="garage-desc">{{ g.description || 'Certified fitting and tuning partner.' }}</p>

          <div class="garage-meta-grid">
            <div class="meta-item">
              <MapPin :size="15" class="meta-icon" />
              <span>{{ g.address }}, {{ g.city || 'Phnom Penh' }}</span>
            </div>
            <div class="meta-item">
              <Phone :size="15" class="meta-icon" />
              <span>{{ g.phone }}</span>
            </div>
            <div class="meta-item">
              <Mail :size="15" class="meta-icon" />
              <span>{{ g.email || g.owner_email }}</span>
            </div>
            <div class="meta-item">
              <CreditCard :size="15" class="meta-icon text-cyan" />
              <span>Wholesale Net 30 • $5,000 Credit Limit</span>
            </div>
          </div>

          <div class="garage-actions">
            <button 
              v-if="g.status !== 'active'" 
              class="btn-approve" 
              @click="updateStatus(g, 'active')"
            >
              Approve Account
            </button>
            <button 
              v-else 
              class="btn-suspend" 
              @click="updateStatus(g, 'suspended')"
            >
              Suspend Trade Account
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.b2b-accounts-page {
  padding: 2rem 0 5rem;
  width: 100%;
}

.admin-page-container,
.container {
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 2rem;
}

.page-top-bar {
  margin-bottom: 2rem;
}

.sub-badge {
  display: inline-block;
  background: rgba(255, 85, 0, 0.12);
  color: #ff7834;
  border: 1px solid rgba(255, 85, 0, 0.3);
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  margin-bottom: 0.4rem;
}

.page-title {
  font-size: 1.85rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.35rem;
}

.page-sub {
  color: #94a3b8;
  font-size: 0.92rem;
  margin: 0;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

.garages-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
  gap: 1.5rem;
}

.garage-card {
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
}

.garage-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.garage-brand-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.garage-icon-box {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: rgba(255, 85, 0, 0.12);
  border: 1px solid rgba(255, 85, 0, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
}

.text-orange { color: #ff5500; }
.text-cyan { color: #38bdf8; }

.garage-name {
  color: #ffffff;
  font-size: 1.15rem;
  margin: 0 0 0.25rem;
}

.tier-pill {
  font-size: 0.72rem;
  font-weight: 700;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.15);
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.status-badge {
  font-size: 0.7rem;
  font-weight: 800;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.status-active {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-pending {
  background: rgba(251, 191, 36, 0.15);
  color: #fbbf24;
  border: 1px solid rgba(251, 191, 36, 0.3);
}

.garage-desc {
  color: #94a3b8;
  font-size: 0.85rem;
  margin: 0 0 1.25rem;
  line-height: 1.45;
}

.garage-meta-grid {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  padding: 1rem;
  background: rgba(2, 6, 23, 0.5);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  margin-bottom: 1.25rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.82rem;
  color: #cbd5e1;
}

.meta-icon {
  color: #64748b;
}

.garage-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: auto;
}

.btn-approve {
  background: #22c55e;
  color: white;
  border: none;
  padding: 0.55rem 1.2rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-suspend {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
  padding: 0.55rem 1.2rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-suspend:hover {
  background: #ef4444;
  color: white;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  gap: 1rem;
  color: #94a3b8;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(255, 85, 0, 0.2);
  border-top-color: #ff5500;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
