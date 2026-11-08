<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import {
  DollarSign,
  Package,
  Users,
  Building2,
  TrendingUp,
  Plus,
  ExternalLink,
  ShieldCheck,
  CheckCircle2,
  Calendar,
  Sparkles,
  ShoppingBag,
  ArrowRight
} from 'lucide-vue-next'
import { subscribeToOrderEvents } from '@/utils/orderRealtime'
import { useLanguageStore } from '@/stores/language'

const router = useRouter()
const authStore = useAuthStore()
const langStore = useLanguageStore()

const stats = ref<any>({
  total_revenue: 0,
  products_count: 0,
  users_count: 0,
  b2c_count: 0,
  b2b_count: 0,
  garages_count: 0,
  orders_count: 0
})

const recentUsers = ref<any[]>([])
const recentProducts = ref<any[]>([])
const recentGarages = ref<any[]>([])
const isLoading = ref(true)

let unsubscribeOrderEvents: (() => void) | null = null
let dashPollingTimer: any = null

const fetchDashboardData = async (silent = false) => {
  // Try loading from session cache for instant render
  if (!stats.value.products_count) {
    try {
      const cached = sessionStorage.getItem('admin_cached_stats')
      if (cached) {
        const parsed = JSON.parse(cached)
        stats.value = parsed.stats || stats.value
        recentUsers.value = parsed.recent_users || []
        recentProducts.value = parsed.recent_products || []
        recentGarages.value = parsed.recent_garages || []
        isLoading.value = false
      }
    } catch (e) {}
  }

  if (!silent && !stats.value.products_count) isLoading.value = true
  try {
    const res = await axios.get('/api/v1/admin/dashboard-stats')
    stats.value = res.data.stats || {}
    recentUsers.value = res.data.recent_users || []
    recentProducts.value = res.data.recent_products || []
    recentGarages.value = res.data.recent_garages || []
    try {
      sessionStorage.setItem('admin_cached_stats', JSON.stringify(res.data))
    } catch (e) {}
  } catch (err) {
    console.error('Failed to load admin stats:', err)
  } finally {
    if (!silent) isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()

  // Real-time update when users make purchases
  unsubscribeOrderEvents = subscribeToOrderEvents((event) => {
    if (event.action === 'ORDER_CREATED' || event.action === 'ORDER_DELETED') {
      fetchDashboardData(true)
    }
  })

  // Periodic background check every 3.5s
  dashPollingTimer = setInterval(() => {
    fetchDashboardData(true)
  }, 3500)
})

onUnmounted(() => {
  if (unsubscribeOrderEvents) {
    unsubscribeOrderEvents()
    unsubscribeOrderEvents = null
  }
  if (dashPollingTimer) {
    clearInterval(dashPollingTimer)
    dashPollingTimer = null
  }
})
</script>

<template>
  <div class="admin-dashboard-page">
    <div class="dashboard-inner">
      <!-- Top Welcome Bar -->
      <div class="dash-top-bar">
        <div>
          <div class="tag-row">
            <span class="live-dot"></span>
            <span class="sys-badge">{{ langStore.t.admin?.liveDatabase || 'LIVE DATABASE CONNECTED' }}</span>
          </div>
          <h1 class="dash-title">{{ langStore.t.admin?.commandDashboard || 'Command Center Dashboard' }}</h1>
          <p class="dash-sub">
            {{ langStore.t.admin?.commandSub || 'Real-time telemetry, registered B2C/B2B accounts, and live catalog performance' }}
          </p>
        </div>

        <div class="dash-actions">
          <router-link to="/admin/products/create" class="btn-action-primary">
            <Plus :size="16" />
            <span>{{ langStore.t.admin?.addNewProduct || 'Add New Product' }}</span>
          </router-link>
          <router-link to="/shop" class="btn-action-outline" target="_blank">
            <ExternalLink :size="15" />
            <span>{{ langStore.t.admin?.viewStorefront || 'Open Public Store' }}</span>
          </router-link>
        </div>
      </div>

      <!-- Quick Metrics Cards -->
      <div class="metrics-grid">
        <div class="metric-card glass-panel">
          <div class="card-icon-box bg-orange-glow">
            <DollarSign :size="22" class="text-orange" />
          </div>
          <div class="metric-content">
            <span class="metric-lbl">{{ langStore.t.admin?.totalRevenue || 'Total Revenue' }}</span>
            <strong class="metric-num">${{ Number(stats.total_revenue || 0).toLocaleString() }}</strong>
            <span class="metric-trend text-green">
              <TrendingUp :size="12" />
              <span>Real-time database aggregated</span>
            </span>
          </div>
        </div>

        <div class="metric-card glass-panel">
          <div class="card-icon-box bg-blue-glow">
            <Package :size="22" class="text-blue" />
          </div>
          <div class="metric-content">
            <span class="metric-lbl">{{ langStore.t.admin?.productsInventory || 'Catalog Products' }}</span>
            <strong class="metric-num">{{ stats.products_count ?? 0 }} Items</strong>
            <span class="metric-sub">Across database categories</span>
          </div>
        </div>

        <div class="metric-card glass-panel">
          <div class="card-icon-box bg-purple-glow">
            <Users :size="22" class="text-purple" />
          </div>
          <div class="metric-content">
            <span class="metric-lbl">{{ langStore.t.admin?.registeredUsers || 'Registered Accounts' }}</span>
            <strong class="metric-num">{{ stats.users_count ?? 0 }} Users</strong>
            <span class="metric-sub">
              {{ stats.b2c_count ?? 0 }} B2C • {{ stats.b2b_count ?? 0 }} B2B Garages
            </span>
          </div>
        </div>

        <div class="metric-card glass-panel">
          <div class="card-icon-box bg-cyan-glow">
            <Building2 :size="22" class="text-cyan" />
          </div>
          <div class="metric-content">
            <span class="metric-lbl">{{ langStore.t.admin?.partnerGaragesCount || 'Partner Garages' }}</span>
            <strong class="metric-num">{{ stats.garages_count ?? 0 }} Partner Garages</strong>
            <span class="metric-sub">Phnom Penh & Provinces</span>
          </div>
        </div>
      </div>

      <!-- Main Tables Grid -->
      <div class="dash-tables-grid">
        <!-- Left Table: Registered Users (Real DB Records) -->
        <div class="table-card glass-panel">
          <div class="card-head">
            <div class="head-left">
              <Users :size="18" class="text-orange" />
              <h3>{{ langStore.t.admin?.recentUsers || 'Registered Database Accounts' }}</h3>
            </div>
            <span class="count-pill">{{ recentUsers.length }} Active</span>
          </div>

          <div class="table-responsive">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>User / Name</th>
                  <th>Email & Phone</th>
                  <th>Role / Mode</th>
                  <th>Joined Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="u in recentUsers" :key="u.id">
                  <td>
                    <div class="user-cell">
                      <div class="avatar-letter">{{ u.name?.charAt(0) || 'U' }}</div>
                      <div>
                        <strong>{{ u.name }}</strong>
                        <small class="user-id-tag">ID: #{{ u.id }}</small>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="contact-meta">
                      <span>{{ u.email }}</span>
                      <small v-if="u.phone" class="phone-text">{{ u.phone }}</small>
                    </div>
                  </td>
                  <td>
                    <span 
                      class="role-badge"
                      :class="{
                        'badge-admin': u.role === 'admin' || u.role === 'super_admin',
                        'badge-b2b': u.role === 'b2b_client' || u.user_mode === 'b2b',
                        'badge-b2c': u.role === 'customer'
                      }"
                    >
                      {{ u.role === 'super_admin' ? '👑 SUPER ADMIN' : (u.role === 'b2b_client' ? '🏢 B2B GARAGE' : '👤 B2C BUYER') }}
                    </span>
                  </td>
                  <td>
                    <span class="date-text">{{ u.created_at?.split(' ')[0] || 'Recently' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right Table: Live Catalog Products -->
        <div class="table-card glass-panel">
          <div class="card-head">
            <div class="head-left">
              <Package :size="18" class="text-orange" />
              <h3>Live Store Products</h3>
            </div>
            <router-link to="/admin/products" class="view-all-link">
              <span>View All</span>
              <ArrowRight :size="13" />
            </router-link>
          </div>

          <div class="table-responsive">
            <table class="dash-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Retail Price</th>
                  <th>B2B Wholesale</th>
                  <th>Stock</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in recentProducts" :key="p.id">
                  <td>
                    <div class="prod-cell">
                      <img :src="p.thumbnail" class="prod-thumb" />
                      <div>
                        <strong class="prod-name">{{ p.name }}</strong>
                        <small class="prod-sku">{{ p.sku }}</small>
                      </div>
                    </div>
                  </td>
                  <td><span class="cat-pill">{{ p.category_name || 'Aftermarket' }}</span></td>
                  <td><strong class="text-orange">${{ Number(p.b2c_price).toLocaleString() }}</strong></td>
                  <td><span class="text-cyan">${{ Number(p.b2b_price || p.b2c_price * 0.8).toLocaleString() }}</span></td>
                  <td>
                    <span class="stock-num">{{ p.stock_quantity }} pcs</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-dashboard-page {
  padding: 1.5rem 0 4rem;
  width: 100%;
}

.dashboard-inner {
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 2rem;
}

.dash-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.tag-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}

.live-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 10px #22c55e;
}

.sys-badge {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #4ade80;
}

.dash-title {
  color: #ffffff;
  font-size: 1.85rem;
  font-weight: 800;
  margin: 0 0 0.35rem;
}

.dash-sub {
  color: #94a3b8;
  font-size: 0.9rem;
  margin: 0;
}

.dash-actions {
  display: flex;
  gap: 0.85rem;
}

.btn-action-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  padding: 0.7rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.88rem;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-action-primary:hover {
  background: #ff6a1f;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(255, 85, 0, 0.35);
}

.btn-action-outline {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #e2e8f0;
  padding: 0.7rem 1.15rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.88rem;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-action-outline:hover {
  background: rgba(255, 255, 255, 0.12);
}

.glass-panel {
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.25rem;
  margin-bottom: 2rem;
}

.metric-card {
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  transition: transform 0.2s;
}

.metric-card:hover {
  transform: translateY(-3px);
  border-color: rgba(255, 85, 0, 0.3);
}

.card-icon-box {
  width: 54px;
  height: 54px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-orange-glow { background: rgba(255, 85, 0, 0.12); border: 1px solid rgba(255, 85, 0, 0.3); }
.bg-blue-glow { background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.3); }
.bg-purple-glow { background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.3); }
.bg-cyan-glow { background: rgba(6, 182, 212, 0.12); border: 1px solid rgba(6, 182, 212, 0.3); }

.text-orange { color: #ff5500; }
.text-blue { color: #60a5fa; }
.text-purple { color: #c084fc; }
.text-cyan { color: #38bdf8; }
.text-green { color: #4ade80; }

.metric-content {
  display: flex;
  flex-direction: column;
}

.metric-lbl {
  font-size: 0.78rem;
  color: #94a3b8;
  font-weight: 600;
}

.metric-num {
  font-size: 1.5rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0.1rem 0;
}

.metric-trend {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
  font-weight: 700;
}

.metric-sub {
  font-size: 0.75rem;
  color: #64748b;
}

/* Tables Grid */
.dash-tables-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.75rem;
}

.table-card {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
}

.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.head-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.head-left h3 {
  color: #ffffff;
  font-size: 1.05rem;
  font-weight: 700;
  margin: 0;
}

.count-pill {
  font-size: 0.72rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.08);
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  color: #cbd5e1;
}

.view-all-link {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  color: #ff7834;
  font-size: 0.8rem;
  font-weight: 600;
  text-decoration: none;
}

.view-all-link:hover {
  text-decoration: underline;
}

.table-responsive {
  overflow-x: auto;
}

.dash-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
  text-align: left;
}

.dash-table th {
  padding: 0.65rem 0.75rem;
  color: #64748b;
  font-weight: 700;
  font-size: 0.72rem;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.dash-table td {
  padding: 0.85rem 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  vertical-align: middle;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.avatar-letter {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 85, 0, 0.15);
  color: #ff7834;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
}

.user-id-tag {
  display: block;
  font-size: 0.7rem;
  color: #64748b;
}

.contact-meta {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  color: #cbd5e1;
}

.phone-text {
  color: #64748b;
  font-size: 0.72rem;
}

.role-badge {
  font-size: 0.68rem;
  font-weight: 800;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  white-space: nowrap;
}

.badge-admin {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.badge-b2b {
  background: rgba(2, 132, 199, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.badge-b2c {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.date-text {
  color: #64748b;
  font-size: 0.75rem;
}

.prod-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.prod-thumb {
  width: 38px;
  height: 38px;
  border-radius: 6px;
  object-fit: cover;
  background: rgba(2, 6, 23, 0.6);
}

.prod-name {
  color: #ffffff;
  display: block;
  max-width: 170px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.prod-sku {
  color: #64748b;
  font-size: 0.7rem;
  font-family: monospace;
}

.cat-pill {
  font-size: 0.72rem;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.05);
  padding: 0.2rem 0.45rem;
  border-radius: 4px;
}

.stock-num {
  color: #4ade80;
  font-weight: 600;
}

@media (max-width: 1024px) {
  .dash-tables-grid {
    grid-template-columns: 1fr;
  }
}
</style>
