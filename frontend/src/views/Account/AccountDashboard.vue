<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import {
  User as UserIcon,
  Car,
  ShoppingBag,
  Building2,
  Heart,
  Wrench,
  ShieldCheck,
  LogOut,
  Sparkles,
  ArrowRight,
  Camera,
  Mail,
  Phone
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const summary = ref({
  vehicles_count: 0,
  orders_count: 0,
  wishlists_count: 0
})

const fetchSummary = async () => {
  try {
    const res = await axios.get('/api/v1/account/dashboard-summary')
    if (res.data.data) {
      summary.value = res.data.data
    }
  } catch (err) {
    console.error('Failed to load dashboard summary:', err)
  }
}

onMounted(() => {
  fetchSummary()
})

const handleLogout = async () => {
  await authStore.logout()
  router.push('/')
}
</script>

<template>
  <div class="dashboard-page">
    <div class="container">
      <!-- Profile Header Banner -->
      <div class="profile-hero-card glass-panel">
        <div class="profile-left">
          <div class="avatar-relative" style="position: relative;">
            <img 
              :src="authStore.user?.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&h=200&q=80'" 
              class="user-main-avatar" 
            />
            <router-link to="/account/profile" class="avatar-edit-badge" title="Change Profile Picture" style="position: absolute; bottom: 0; right: 0; width: 26px; height: 26px; background: var(--orange-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 2px solid #080c16;">
              <Camera :size="13" />
            </router-link>
          </div>
          <div class="profile-meta">
            <div class="name-badge-row">
              <h2>{{ authStore.user?.name || 'Member' }}</h2>
              <span class="role-chip" :class="authStore.isB2B ? 'chip-b2b' : 'chip-b2c'">
                {{ authStore.isAdmin ? 'SUPER ADMIN' : (authStore.isB2B ? 'B2B WHOLESALE GARAGE' : 'VIP DRIVER') }}
              </span>
            </div>
            <p class="user-subtext" style="display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap;">
              <span style="display: flex; align-items: center; gap: 0.35rem;"><Mail :size="13" /> {{ authStore.user?.email }}</span>
              <span v-if="authStore.user?.phone" style="display: flex; align-items: center; gap: 0.35rem;"><Phone :size="13" /> {{ authStore.user.phone }}</span>
            </p>
          </div>
        </div>

        <div class="profile-actions">
          <button 
            class="mode-switch-btn"
            :class="{ active: authStore.isB2B }"
            @click="authStore.setMode(authStore.isB2B ? 'b2c' : 'b2b')"
          >
            <span>Active Mode:</span>
            <strong>{{ authStore.isB2B ? 'B2B Wholesale' : 'Retail Consumer' }}</strong>
            <span class="switch-small">Switch</span>
          </button>
          <button class="btn-logout" @click="handleLogout">
            <LogOut :size="15" />
            <span>Sign Out</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Grid (100% Real Database Values) -->
      <div class="metrics-grid">
        <div class="metric-card glass-card">
          <div class="metric-icon bg-orange">
            <Car :size="22" />
          </div>
          <div class="metric-info">
            <span class="metric-lbl">My Garage Vehicles</span>
            <strong class="metric-val">{{ summary.vehicles_count }} Active</strong>
          </div>
          <router-link to="/account/garage" class="card-arrow">
            <ArrowRight :size="16" />
          </router-link>
        </div>

        <div class="metric-card glass-card">
          <div class="metric-icon bg-blue">
            <ShoppingBag :size="22" />
          </div>
          <div class="metric-info">
            <span class="metric-lbl">Total Orders</span>
            <strong class="metric-val">{{ summary.orders_count }} Placed</strong>
          </div>
          <router-link to="/account/orders" class="card-arrow">
            <ArrowRight :size="16" />
          </router-link>
        </div>

        <div class="metric-card glass-card">
          <div class="metric-icon bg-cyan">
            <Building2 :size="22" />
          </div>
          <div class="metric-info">
            <span class="metric-lbl">Wholesale Tier Status</span>
            <strong class="metric-val">{{ authStore.isB2B ? 'Tier 1 (-25%)' : 'Retail MSRP' }}</strong>
          </div>
          <router-link to="/shop?b2b=true" class="card-arrow">
            <ArrowRight :size="16" />
          </router-link>
        </div>

        <div class="metric-card glass-card">
          <div class="metric-icon bg-red">
            <Heart :size="22" />
          </div>
          <div class="metric-info">
            <span class="metric-lbl">Saved Wishlist</span>
            <strong class="metric-val">{{ summary.wishlists_count }} Items</strong>
          </div>
          <router-link to="/wishlist" class="card-arrow">
            <ArrowRight :size="16" />
          </router-link>
        </div>
      </div>

      <!-- Quick Navigation Tiles -->
      <div class="dashboard-tiles-grid">
        <div class="dash-tile glass-card">
          <div class="tile-top">
            <Wrench :size="20" class="text-orange" />
            <span class="tag">INSTALLATION</span>
          </div>
          <h3>Book Garage Installation</h3>
          <p>Schedule certified mechanics at partner garages in Phnom Penh for your parts fitting</p>
          <router-link to="/garages" class="tile-action-link">
            <span>Find Certified Garages</span>
            <ArrowRight :size="15" />
          </router-link>
        </div>

        <div class="dash-tile glass-card">
          <div class="tile-top">
            <Sparkles :size="20" class="text-cyan" />
            <span class="tag cyan">FITMENT VERIFIER</span>
          </div>
          <h3>Compatible Parts Finder</h3>
          <p>Search 5,000+ parts guaranteed to match your motorcycle or car specifications</p>
          <router-link to="/shop" class="tile-action-link">
            <span>Search Parts Catalog</span>
            <ArrowRight :size="15" />
          </router-link>
        </div>

        <div v-if="authStore.isAdmin" class="dash-tile glass-card admin-highlight">
          <div class="tile-top">
            <ShieldCheck :size="20" class="text-orange" />
            <span class="tag admin">DIRECTOR ACCESS</span>
          </div>
          <h3>Administration Portal</h3>
          <p>Manage products inventory, customer orders, partner garages, and wholesale pricing</p>
          <router-link to="/admin/dashboard" class="tile-action-link admin">
            <span>Enter Admin Dashboard</span>
            <ArrowRight :size="15" />
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-page {
  padding: 3rem 0 5rem 0;
  min-height: 80vh;
}

.profile-hero-card {
  background: rgba(13, 18, 29, 0.9);
  border: 1px solid rgba(249, 115, 22, 0.35);
  border-radius: 16px;
  padding: 2rem 2.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6), 0 0 25px rgba(249, 115, 22, 0.12);
}

.profile-left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.user-main-avatar {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  object-fit: cover;
  border: 2.5px solid var(--orange-500);
  box-shadow: 0 0 20px rgba(249, 115, 22, 0.35);
}

.name-badge-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.name-badge-row h2 {
  font-size: 1.6rem;
  color: #ffffff;
  margin: 0;
}

.role-chip {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
}

.chip-b2c {
  background: rgba(249, 115, 22, 0.2);
  border: 1px solid rgba(249, 115, 22, 0.5);
  color: #ff7834;
}

.chip-b2b {
  background: rgba(0, 210, 255, 0.2);
  border: 1px solid rgba(0, 210, 255, 0.5);
  color: #00d2ff;
}

.user-subtext {
  color: #94a3b8;
  font-size: 0.88rem;
  margin-top: 0.35rem;
}

.profile-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.mode-switch-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.14);
  color: #e2e8f0;
  padding: 0.55rem 1rem;
  border-radius: 10px;
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s;
}

.mode-switch-btn.active {
  background: rgba(2, 132, 199, 0.2);
  border-color: #38bdf8;
  color: #38bdf8;
}

.switch-small {
  background: rgba(255, 255, 255, 0.12);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  font-size: 0.68rem;
  color: white;
}

.btn-logout {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #fca5a5;
  padding: 0.55rem 1rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-logout:hover {
  background: #ef4444;
  color: white;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.metric-card {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  position: relative;
}

.metric-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-orange { background: rgba(249, 115, 22, 0.15); color: var(--orange-500); }
.bg-blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.bg-cyan { background: rgba(0, 210, 255, 0.15); color: #00d2ff; }
.bg-red { background: rgba(239, 68, 68, 0.15); color: #f87171; }

.metric-info {
  display: flex;
  flex-direction: column;
}

.metric-lbl {
  font-size: 0.78rem;
  color: #94a3b8;
  font-weight: 600;
}

.metric-val {
  font-size: 1.35rem;
  font-weight: 900;
  color: #ffffff;
}

.card-arrow {
  margin-left: auto;
  color: #64748b;
  transition: transform 0.2s, color 0.2s;
}

.metric-card:hover .card-arrow {
  transform: translateX(4px);
  color: var(--orange-500);
}

/* Dashboard Tiles */
.dashboard-tiles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.75rem;
}

.dash-tile {
  padding: 2rem;
  display: flex;
  flex-direction: column;
}

.tile-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.tag {
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  background: rgba(249, 115, 22, 0.15);
  color: #ff7834;
}

.tag.cyan {
  background: rgba(0, 210, 255, 0.15);
  color: #00d2ff;
}

.tag.admin {
  background: rgba(168, 85, 247, 0.2);
  color: #c084fc;
}

.dash-tile h3 {
  font-size: 1.25rem;
  color: #ffffff;
  margin-bottom: 0.5rem;
}

.dash-tile p {
  color: #94a3b8;
  font-size: 0.88rem;
  line-height: 1.5;
  margin-bottom: 1.5rem;
  flex: 1;
}

.tile-action-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: var(--orange-500);
  font-weight: 700;
  font-size: 0.88rem;
  transition: gap 0.2s;
}

.tile-action-link:hover {
  gap: 0.65rem;
}

.tile-action-link.admin {
  color: #c084fc;
}

.admin-highlight {
  border-color: rgba(168, 85, 247, 0.4);
}
</style>
