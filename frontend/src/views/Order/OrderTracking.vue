<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import {
  Search,
  Truck,
  Package,
  CheckCircle2,
  Clock,
  ShieldCheck,
  MapPin,
  Calendar,
  AlertCircle,
  ExternalLink,
  ChevronRight,
  Phone,
  Plane,
  Wrench,
  Sparkles,
  ArrowRight,
  Trash2,
  Radio,
  ShoppingBag
} from 'lucide-vue-next'
import { formatImageUrl, DEFAULT_PRODUCT_THUMB } from '@/utils/image'
import { subscribeToOrderEvents } from '@/utils/orderRealtime'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const searchInput = ref('')
const isLoading = ref(false)
const orderData = ref<any>(null)
const items = ref<any[]>([])
const steps = ref<any[]>([])
const courier = ref<any>(null)
const errorMessage = ref('')

// Real-Time reactivity states
const isFlashing = ref(false)
const realtimeNotice = ref('')
const orderDeletedInfo = ref({ isDeleted: false, orderNumber: '' })
let pollInterval: any = null
let unsubscribeEvents: (() => void) | null = null

const resetDeletedState = () => {
  orderDeletedInfo.value = { isDeleted: false, orderNumber: '' }
  searchInput.value = ''
  errorMessage.value = ''
}

const showRealtimeToast = (msg: string) => {
  realtimeNotice.value = msg
  isFlashing.value = true
  setTimeout(() => { isFlashing.value = false }, 2000)
  setTimeout(() => {
    if (realtimeNotice.value === msg) {
      realtimeNotice.value = ''
    }
  }, 6500)
}

/**
 * Background silent refresh: Updates UI in real-time without showing loading screen
 */
const silentRefresh = async (code: string) => {
  if (!code || orderDeletedInfo.value.isDeleted) return
  try {
    const res = await axios.get(`/api/v1/orders/track/${code}`)
    if (res.data?.data?.order) {
      const newOrder = res.data.data.order
      const oldStatus = orderData.value?.status
      if (oldStatus && oldStatus !== newOrder.status) {
        showRealtimeToast(`Order status updated to ${newOrder.status.toUpperCase()} by Admin!`)
      }
      orderData.value = newOrder
      items.value = res.data.data.items || []
      steps.value = res.data.data.steps || []
      courier.value = res.data.data.courier || {}
    }
  } catch (err: any) {
    if (err.response?.status === 404) {
      // Order was deleted in database by Admin!
      orderDeletedInfo.value = { isDeleted: true, orderNumber: code }
      orderData.value = null
      items.value = []
      steps.value = []
      courier.value = null
      stopPolling()
    }
  }
}

const startPolling = (code: string) => {
  stopPolling()
  // Check every 2.5 seconds silently for external or cross-browser admin changes
  pollInterval = setInterval(() => {
    if (orderData.value && !orderDeletedInfo.value.isDeleted) {
      silentRefresh(code)
    }
  }, 2500)
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

const trackOrder = async (queryCode?: string) => {
  const code = (queryCode || searchInput.value || '').trim()
  if (!code) {
    errorMessage.value = 'Please enter an Order Number (e.g. MCC-2026-12345) or Tracking Code.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  orderDeletedInfo.value = { isDeleted: false, orderNumber: '' }

  try {
    const res = await axios.get(`/api/v1/orders/track/${code}`)
    if (res.data?.data) {
      orderData.value = res.data.data.order
      items.value = res.data.data.items || []
      steps.value = res.data.data.steps || []
      courier.value = res.data.data.courier || {}
      searchInput.value = orderData.value.order_number
      startPolling(orderData.value.order_number)
    }
  } catch (err: any) {
    orderData.value = null
    items.value = []
    steps.value = []
    courier.value = null
    stopPolling()
    errorMessage.value = err.response?.data?.message || `No active shipment found matching '${code}'. Please check your order reference.`
  } finally {
    isLoading.value = false
  }
}

// Auto-track on load & subscribe to live cross-tab events
onMounted(async () => {
  // 1. Subscribe to instant cross-tab real-time sync
  unsubscribeEvents = subscribeToOrderEvents((event) => {
    const currentCode = (orderData.value?.order_number || searchInput.value || '').trim().toLowerCase()
    const targetCode = (event.orderNumber || '').trim().toLowerCase()

    if (currentCode && targetCode && currentCode === targetCode) {
      if (event.action === 'STATUS_UPDATED') {
        showRealtimeToast(`Order status updated to ${event.status?.toUpperCase()} by Admin!`)
        silentRefresh(orderData.value?.order_number || searchInput.value)
      } else if (event.action === 'ORDER_DELETED') {
        orderDeletedInfo.value = { isDeleted: true, orderNumber: event.orderNumber || '' }
        orderData.value = null
        items.value = []
        steps.value = []
        courier.value = null
        stopPolling()
      }
    }
  })

  // 2. Initial load
  const paramCode = (route.params.orderNumber as string) || (route.query.code as string)
  if (paramCode) {
    searchInput.value = paramCode
    await trackOrder(paramCode)
  } else if (authStore.isAuthenticated) {
    try {
      const headers: Record<string, string> = {
        'X-User-Id': String(authStore.user?.id || 1)
      }
      const token = localStorage.getItem('auth_token')
      if (token) headers['Authorization'] = `Bearer ${token}`

      const res = await axios.get('/api/v1/account/orders', { headers })
      const userOrders = res.data?.data || []
      if (userOrders.length > 0) {
        const latest = userOrders[0]
        searchInput.value = latest.order_number
        await trackOrder(latest.order_number)
      }
    } catch (e) {
      // Ignore fallback
    }
  }
})

onUnmounted(() => {
  stopPolling()
  if (unsubscribeEvents) {
    unsubscribeEvents()
    unsubscribeEvents = null
  }
})

watch(() => route.params.orderNumber, (newCode) => {
  if (newCode) {
    searchInput.value = newCode as string
    trackOrder(newCode as string)
  }
})
</script>

<template>
  <div class="tracking-page">
    <div class="container">
      <!-- Page Hero Header -->
      <div class="tracking-hero">
        <span class="sub-badge">
          <Plane :size="13" class="text-orange" />
          <span>REAL-TIME AIR-FREIGHT TELEMETRY</span>
        </span>
        <h1 class="page-title">Live Order & Shipment Tracking</h1>
        <p class="page-sub">
          Track genuine performance motorcycle and car parts from European & Japanese manufacturers straight to Phnom Penh
        </p>

        <!-- Search Bar -->
        <div class="search-wrap">
          <div class="search-box glass-panel">
            <Search :size="18" class="search-icon text-orange" />
            <input 
              v-model="searchInput" 
              type="text" 
              placeholder="Enter Order # (e.g. MCC-2026-48291) or Tracking # (APX-782192)..."
              class="track-input"
              @keyup.enter="trackOrder()"
            />
            <button class="btn-track" :disabled="isLoading" @click="trackOrder()">
              <Truck :size="16" />
              <span>{{ isLoading ? 'Tracking...' : 'Track Package' }}</span>
            </button>
          </div>

          <div v-if="errorMessage" class="error-banner">
            <AlertCircle :size="16" />
            <span>{{ errorMessage }}</span>
          </div>
        </div>
      </div>

      <!-- Real-Time Live Update Toast Banner -->
      <transition name="slide-down">
        <div v-if="realtimeNotice" class="realtime-live-toast">
          <div class="toast-left">
            <span class="live-flash-dot"></span>
            <div class="toast-text-wrap">
              <strong>⚡ LIVE SYNC UPDATE:</strong>
              <span>{{ realtimeNotice }}</span>
            </div>
          </div>
          <button class="toast-dismiss" @click="realtimeNotice = ''">&times;</button>
        </div>
      </transition>

      <!-- Order Deleted by Admin State -->
      <div v-if="orderDeletedInfo.isDeleted" class="deleted-order-card glass-panel">
        <div class="deleted-icon-wrap">
          <Trash2 :size="48" class="text-red" />
        </div>
        <span class="deleted-badge">ADMIN ACTION: ORDER PERMANENTLY REMOVED</span>
        <h2 class="deleted-title">Shipment #{{ orderDeletedInfo.orderNumber }} Was Deleted</h2>
        <p class="deleted-desc">
          This order was permanently deleted or cancelled by an administrator from the backend database. Real-time telemetry monitoring for this shipment has stopped.
        </p>
        <div class="deleted-actions">
          <button class="btn-clear-track" @click="resetDeletedState">
            <Search :size="16" />
            <span>Track Another Order</span>
          </button>
          <router-link to="/shop" class="btn-deleted-shop">
            <ShoppingBag :size="16" />
            <span>Return to Catalog</span>
          </router-link>
        </div>
      </div>

      <!-- Tracking Results Section -->
      <div v-if="orderData" class="results-container">
        <!-- Status Top Header Card -->
        <div class="order-status-card glass-panel" :class="{ 'card-flashing': isFlashing }">
          <div class="order-id-row">
            <div>
              <div class="top-tag-row">
                <span class="status-lbl">TRACKING SHIPMENT</span>
                <span class="live-telemetry-badge">
                  <span class="telemetry-pulse-dot"></span>
                  <span>REAL-TIME TELEMETRY CONNECTED</span>
                </span>
              </div>
              <h2 class="order-number-title">{{ orderData.order_number }}</h2>
              <small class="created-time">Ordered on {{ new Date(orderData.created_at).toLocaleString() }}</small>
            </div>

            <div class="order-status-pill" :class="`status-${orderData.status?.toLowerCase()}`">
              <span class="status-dot"></span>
              <span>{{ orderData.status?.toUpperCase() }}</span>
            </div>
          </div>

          <!-- Progress Stepper Pipeline -->
          <div class="stepper-pipeline">
            <div 
              v-for="(st, idx) in steps" 
              :key="st.step" 
              class="step-node"
              :class="{ 
                'step-done': st.done, 
                'step-active': st.active,
                'step-cancelled': st.cancelled
              }"
            >
              <div class="step-circle">
                <CheckCircle2 v-if="st.done" :size="16" />
                <AlertCircle v-else-if="st.cancelled" :size="16" />
                <span v-else>{{ st.step }}</span>
              </div>
              <div class="step-text">
                <strong class="step-name">{{ st.title }}</strong>
                <span class="step-desc">{{ st.desc }}</span>
                <small class="step-timestamp">{{ st.time }}</small>
              </div>
              <div v-if="idx < steps.length - 1" class="step-connector" :class="{ 'connector-done': st.done, 'connector-cancelled': st.cancelled }"></div>
            </div>
          </div>
        </div>

        <!-- Two Column Breakdown -->
        <div class="tracking-grid">
          <!-- Left: Items in this order -->
          <div class="items-breakdown-card glass-panel">
            <h3 class="card-title">
              <Package :size="18" class="text-orange" />
              <span>Shipment Contents ({{ items.length }} Items)</span>
            </h3>

            <div class="items-list">
              <div v-for="item in items" :key="item.id" class="tracking-item-row">
                <img 
                  :src="formatImageUrl(item.product_image, DEFAULT_PRODUCT_THUMB)" 
                  class="item-thumb" 
                  :alt="item.product_name" 
                />
                <div class="item-details">
                  <strong class="item-title">{{ item.product_name }}</strong>
                  <div class="item-chips">
                    <span class="sku-chip">{{ item.product_sku }}</span>
                    <span class="qty-chip">Qty: {{ item.quantity }}</span>
                  </div>
                </div>
                <div class="item-pricing">
                  <strong class="price-val">${{ Number(item.total_price || item.unit_price * item.quantity).toLocaleString() }}</strong>
                  <small>${{ Number(item.unit_price).toLocaleString() }} each</small>
                </div>
              </div>
            </div>

            <!-- Price Summary -->
            <div class="order-totals-box">
              <div class="sum-row">
                <span>Subtotal</span>
                <strong>${{ Number(orderData.subtotal).toLocaleString() }}</strong>
              </div>
              <div class="sum-row">
                <span>Express Air-Freight Shipping</span>
                <strong>${{ Number(orderData.shipping_fee || 15).toLocaleString() }}</strong>
              </div>
              <div v-if="Number(orderData.discount_amount) > 0" class="sum-row text-green">
                <span>Discount Applied</span>
                <strong>-${{ Number(orderData.discount_amount).toLocaleString() }}</strong>
              </div>
              <div class="sum-row total-paid">
                <span>Total Amount Paid</span>
                <strong class="text-orange">${{ Number(orderData.total_amount).toLocaleString() }} USD</strong>
              </div>
            </div>
          </div>

          <!-- Right: Carrier & Delivery Destination -->
          <div class="logistics-sidebar">
            <!-- Carrier Card -->
            <div class="carrier-card glass-panel">
              <h3 class="card-title">
                <Truck :size="18" class="text-cyan" />
                <span>Logistics & Courier Telemetry</span>
              </h3>

              <div class="carrier-info-row">
                <div class="carrier-icon-box">
                  <Plane :size="22" class="text-orange" />
                </div>
                <div>
                  <strong>{{ courier?.name || 'Apex Express Motorsport Logistics' }}</strong>
                  <small>{{ courier?.service_type || 'Priority Flight Cargo' }}</small>
                </div>
              </div>

              <div class="meta-data-list">
                <div class="meta-item">
                  <span class="meta-label">Tracking Number</span>
                  <strong class="meta-val tracking-code">{{ courier?.tracking_code || orderData.tracking_number }}</strong>
                </div>
                <div class="meta-item">
                  <span class="meta-label">Payment Verification</span>
                  <span class="paid-badge">
                    <span class="dot-green"></span>
                    BAKONG KHQR PAID
                  </span>
                </div>
                <div class="meta-item">
                  <span class="meta-label">Estimated Delivery</span>
                  <strong class="meta-val text-green">Within 24-48 Hours</strong>
                </div>
                <div class="meta-item">
                  <span class="meta-label">Customer Contact</span>
                  <strong class="meta-val">{{ orderData.customer_name || 'Member' }} ({{ orderData.customer_phone || '+855 23 888 999' }})</strong>
                </div>
              </div>
            </div>

            <!-- Partner Garage Mount Card -->
            <div class="garage-card glass-panel">
              <h3 class="card-title">
                <Wrench :size="18" class="text-orange" />
                <span>Certified Workshop Fitment</span>
              </h3>
              <p class="garage-desc">
                Need professional dyno tuning or mechanic installation for these performance parts?
              </p>
              <router-link to="/garages" class="btn-garage-link">
                <MapPin :size="15" />
                <span>View Partner Garages in Phnom Penh</span>
                <ChevronRight :size="15" />
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- No Active Search / Initial State -->
      <div v-else-if="!isLoading" class="empty-track-state glass-panel">
        <div class="radar-box">
          <Truck :size="50" class="text-orange" />
        </div>
        <h3>No Order Selected</h3>
        <p>Enter your <strong>MCC-2026-XXXXX</strong> Order Number or <strong>APX-XXXXXX</strong> Tracking Code above to monitor live flight logistics and dispatch status.</p>
        <div v-if="authStore.isAuthenticated" style="margin-top: 1.5rem;">
          <router-link to="/account/orders" class="btn-check-orders">
            <Package :size="16" />
            <span>Go to My Orders List</span>
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tracking-page {
  padding: 3rem 0 6rem;
  min-height: 85vh;
  background: #050811;
  color: #f1f5f9;
}

.container {
  max-width: 1180px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
}

/* Hero Section */
.tracking-hero {
  text-align: center;
  max-width: 820px;
  margin: 0 auto 3rem;
}

.sub-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #ff7834;
  background: rgba(255, 85, 0, 0.12);
  border: 1px solid rgba(255, 85, 0, 0.3);
  padding: 0.3rem 0.8rem;
  border-radius: 9999px;
  margin-bottom: 0.8rem;
}

.page-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.6rem;
  letter-spacing: -0.02em;
}

.page-sub {
  color: #94a3b8;
  font-size: 0.95rem;
  line-height: 1.6;
  margin: 0 auto 2rem;
}

/* Search Box */
.search-wrap {
  max-width: 680px;
  margin: 0 auto;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.6rem 0.5rem 1.25rem;
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.14);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
  border-radius: 14px;
}

.search-box:focus-within {
  border-color: #ff5500;
  box-shadow: 0 0 25px rgba(255, 85, 0, 0.3);
}

.track-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: white;
  font-size: 0.95rem;
}

.btn-track {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-track:hover {
  background: #ff6a1f;
  box-shadow: 0 4px 15px rgba(255, 85, 0, 0.4);
}

.error-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  color: #f87171;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.25);
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.88rem;
  margin-top: 1rem;
}

/* Real-Time Live Toast */
.realtime-live-toast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(90deg, rgba(14, 165, 233, 0.22) 0%, rgba(255, 85, 0, 0.18) 100%);
  border: 1px solid rgba(14, 165, 233, 0.4);
  box-shadow: 0 8px 32px rgba(14, 165, 233, 0.25);
  backdrop-filter: blur(12px);
  padding: 0.9rem 1.4rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  color: #ffffff;
  animation: toast-glow 2s infinite alternate;
}

@keyframes toast-glow {
  0% { box-shadow: 0 0 15px rgba(14, 165, 233, 0.2); }
  100% { box-shadow: 0 0 25px rgba(255, 85, 0, 0.35); }
}

.toast-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.toast-text-wrap {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.toast-text-wrap strong {
  color: #38bdf8;
  font-size: 0.88rem;
  letter-spacing: 0.04em;
}

.toast-text-wrap span {
  font-size: 0.92rem;
  color: #ffffff;
  font-weight: 600;
}

.live-flash-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #00d2ff;
  box-shadow: 0 0 12px #00d2ff;
  animation: live-blink 1s infinite alternate;
}

@keyframes live-blink {
  0% { opacity: 0.3; transform: scale(0.8); }
  100% { opacity: 1; transform: scale(1.3); }
}

.toast-dismiss {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.4rem;
  cursor: pointer;
  padding: 0 0.5rem;
}

.toast-dismiss:hover {
  color: #ffffff;
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.35s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Deleted Order Card */
.deleted-order-card {
  text-align: center;
  padding: 4.5rem 2rem;
  max-width: 680px;
  margin: 0 auto 3rem;
  border: 1px solid rgba(239, 68, 68, 0.35);
  box-shadow: 0 0 40px rgba(239, 68, 68, 0.15);
  background: rgba(15, 23, 42, 0.85);
}

.deleted-icon-wrap {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  box-shadow: 0 0 30px rgba(239, 68, 68, 0.3);
}

.text-red {
  color: #ef4444;
}

.deleted-badge {
  display: inline-block;
  background: rgba(239, 68, 68, 0.18);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.35);
  padding: 0.3rem 0.8rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  margin-bottom: 0.8rem;
}

.deleted-title {
  color: #ffffff;
  font-size: 1.8rem;
  margin: 0 0 0.8rem;
  font-weight: 800;
}

.deleted-desc {
  color: #94a3b8;
  font-size: 0.95rem;
  line-height: 1.6;
  max-width: 520px;
  margin: 0 auto 2rem;
}

.deleted-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-clear-track {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.75rem 1.4rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-clear-track:hover {
  background: #ff6a1f;
  box-shadow: 0 4px 15px rgba(255, 85, 0, 0.4);
}

.btn-deleted-shop {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: white;
  padding: 0.75rem 1.4rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.88rem;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-deleted-shop:hover {
  background: rgba(255, 255, 255, 0.15);
}

/* Status Card */
.order-status-card {
  padding: 2.25rem;
  margin-bottom: 2rem;
  transition: all 0.5s ease;
}

.order-status-card.card-flashing {
  border-color: #38bdf8 !important;
  box-shadow: 0 0 35px rgba(56, 189, 248, 0.4) !important;
  transform: scale(1.008);
}

.order-id-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2.5rem;
  flex-wrap: wrap;
  gap: 1.25rem;
}

.top-tag-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 0.2rem;
}

.status-lbl {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #38bdf8;
}

.live-telemetry-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  color: #4ade80;
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.3);
  padding: 0.15rem 0.55rem;
  border-radius: 9999px;
}

.telemetry-pulse-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 8px #22c55e;
  animation: pulse-dot 1.5s infinite;
}

@keyframes pulse-dot {
  0% { transform: scale(0.9); opacity: 0.6; }
  50% { transform: scale(1.4); opacity: 1; }
  100% { transform: scale(0.9); opacity: 0.6; }
}

.order-number-title {
  font-size: 1.8rem;
  color: white;
  margin: 0.2rem 0;
  font-weight: 800;
}

.created-time {
  color: #64748b;
  font-size: 0.82rem;
}

/* Status Pill Variations */
.order-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1.15rem;
  border-radius: 9999px;
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  transition: all 0.3s;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.order-status-pill.status-confirmed {
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.35);
}
.order-status-pill.status-confirmed .status-dot {
  background: #38bdf8;
  box-shadow: 0 0 10px #38bdf8;
}

.order-status-pill.status-processing {
  background: rgba(251, 191, 36, 0.15);
  color: #fbbf24;
  border: 1px solid rgba(251, 191, 36, 0.35);
}
.order-status-pill.status-processing .status-dot {
  background: #fbbf24;
  box-shadow: 0 0 10px #fbbf24;
}

.order-status-pill.status-shipped {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
  border: 1px solid rgba(168, 85, 247, 0.35);
}
.order-status-pill.status-shipped .status-dot {
  background: #c084fc;
  box-shadow: 0 0 10px #c084fc;
}

.order-status-pill.status-delivered {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.35);
}
.order-status-pill.status-delivered .status-dot {
  background: #22c55e;
  box-shadow: 0 0 10px #22c55e;
}

.order-status-pill.status-cancelled {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.35);
}
.order-status-pill.status-cancelled .status-dot {
  background: #ef4444;
  box-shadow: 0 0 10px #ef4444;
}

/* Stepper Pipeline */
.stepper-pipeline {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 1rem;
  position: relative;
  padding-top: 1rem;
}

@media (max-width: 900px) {
  .stepper-pipeline {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
}

.step-node {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.step-circle {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #0f172a;
  border: 2px solid #334155;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.85rem;
  margin-bottom: 0.75rem;
  z-index: 2;
  transition: all 0.3s;
}

.step-done .step-circle {
  background: #22c55e;
  border-color: #22c55e;
  color: #020617;
  box-shadow: 0 0 16px rgba(34, 197, 94, 0.5);
}

.step-active .step-circle {
  background: rgba(255, 85, 0, 0.2);
  border-color: #ff5500;
  color: #ff7834;
  box-shadow: 0 0 16px rgba(255, 85, 0, 0.6);
  animation: pulse-ring 2s infinite ease-in-out;
}

@keyframes pulse-ring {
  0% { box-shadow: 0 0 0 0 rgba(255, 85, 0, 0.7); }
  70% { box-shadow: 0 0 0 10px rgba(255, 85, 0, 0); }
  100% { box-shadow: 0 0 0 0 rgba(255, 85, 0, 0); }
}

.step-cancelled .step-circle {
  background: rgba(239, 68, 68, 0.2);
  border-color: #ef4444;
  color: #f87171;
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
}

.step-connector {
  position: absolute;
  top: 19px;
  left: 50%;
  width: 100%;
  height: 3px;
  background: #1e293b;
  z-index: 1;
}

.connector-done {
  background: #22c55e;
}

.connector-cancelled {
  background: rgba(239, 68, 68, 0.4);
}

@media (max-width: 900px) {
  .step-connector { display: none; }
  .step-node { flex-direction: row; text-align: left; gap: 1rem; }
}

.step-text {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.step-name {
  color: white;
  font-size: 0.85rem;
}

.step-desc {
  color: #94a3b8;
  font-size: 0.75rem;
  line-height: 1.3;
}

.step-timestamp {
  color: #38bdf8;
  font-size: 0.72rem;
  font-weight: 700;
  margin-top: 0.2rem;
}

/* Two Column Layout */
.tracking-grid {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 2rem;
}

@media (max-width: 850px) {
  .tracking-grid {
    grid-template-columns: 1fr;
  }
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1.1rem;
  color: white;
  margin: 0 0 1.25rem;
  padding-bottom: 0.8rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

/* Items Card */
.items-breakdown-card {
  padding: 1.75rem;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.tracking-item-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.item-thumb {
  width: 58px;
  height: 58px;
  border-radius: 8px;
  object-fit: cover;
  background: #020617;
  border: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
}

.item-details {
  flex: 1;
}

.item-title {
  color: white;
  font-size: 0.92rem;
  display: block;
  margin-bottom: 0.25rem;
}

.item-chips {
  display: flex;
  gap: 0.5rem;
}

.sku-chip {
  font-family: monospace;
  font-size: 0.72rem;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  color: #94a3b8;
}

.qty-chip {
  font-size: 0.75rem;
  color: #38bdf8;
  font-weight: 700;
}

.item-pricing {
  text-align: right;
}

.price-val {
  color: #ff5500;
  font-size: 1.05rem;
  display: block;
}

.item-pricing small {
  color: #64748b;
  font-size: 0.72rem;
}

/* Totals */
.order-totals-box {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.sum-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.88rem;
  color: #94a3b8;
}

.sum-row strong {
  color: white;
}

.total-paid {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px dashed rgba(255, 255, 255, 0.1);
  font-size: 1.05rem;
  color: white;
  font-weight: 800;
}

/* Logistics Sidebar */
.logistics-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.carrier-card, .garage-card {
  padding: 1.75rem;
}

.carrier-info-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  background: rgba(2, 6, 23, 0.4);
  padding: 0.75rem 1rem;
  border-radius: 10px;
}

.carrier-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: rgba(255, 85, 0, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
}

.carrier-info-row strong {
  color: white;
  font-size: 0.95rem;
  display: block;
}

.carrier-info-row small {
  color: #64748b;
  font-size: 0.75rem;
}

.meta-data-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.meta-label {
  font-size: 0.72rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.meta-val {
  color: white;
  font-size: 0.92rem;
}

.tracking-code {
  font-family: monospace;
  color: #38bdf8;
  font-size: 1rem;
}

.paid-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  font-size: 0.75rem;
  font-weight: 800;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  width: fit-content;
}

.dot-green {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 8px #22c55e;
}

/* Garage Card */
.garage-desc {
  color: #94a3b8;
  font-size: 0.85rem;
  line-height: 1.5;
  margin: 0 0 1.25rem;
}

.btn-garage-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 85, 0, 0.12);
  border: 1px solid rgba(255, 85, 0, 0.3);
  color: #ff5500;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 700;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-garage-link:hover {
  background: rgba(255, 85, 0, 0.22);
}

/* Empty State */
.empty-track-state {
  text-align: center;
  padding: 4.5rem 2rem;
  max-width: 600px;
  margin: 0 auto;
}

.radar-box {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  background: rgba(255, 85, 0, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  box-shadow: 0 0 30px rgba(255, 85, 0, 0.2);
}

.empty-track-state h3 {
  color: white;
  font-size: 1.4rem;
  margin: 0 0 0.5rem;
}

.empty-track-state p {
  color: #94a3b8;
  font-size: 0.95rem;
  line-height: 1.6;
}

.btn-check-orders {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: white;
  padding: 0.75rem 1.4rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.88rem;
  text-decoration: none;
}

.text-orange { color: #ff5500; }
.text-cyan { color: #00d2ff; }
.text-green { color: #22c55e; }
</style>
