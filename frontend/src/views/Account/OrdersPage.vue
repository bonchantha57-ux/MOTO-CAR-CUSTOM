<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import { formatImageUrl, handleProductImageError, DEFAULT_PRODUCT_THUMB } from '@/utils/image'
import {
  Package,
  Truck,
  CheckCircle2,
  Clock,
  ArrowRight,
  ExternalLink,
  ShoppingBag,
  Loader2
} from 'lucide-vue-next'
import { subscribeToOrderEvents } from '@/utils/orderRealtime'

const router = useRouter()
const authStore = useAuthStore()

const orders = ref<any[]>([])
const isLoading = ref(true)
let unsubscribeEvents: (() => void) | null = null

const fetchOrders = async (silent = false) => {
  if (!silent) isLoading.value = true
  try {
    const headers: Record<string, string> = {
      'X-User-Id': String(authStore.user?.id || 1)
    }
    const token = localStorage.getItem('auth_token')
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    const res = await axios.get('/api/v1/account/orders', { headers })
    orders.value = res.data.data || []
  } catch (err) {
    console.error('Failed to fetch orders:', err)
  } finally {
    if (!silent) isLoading.value = false
  }
}

onMounted(() => {
  fetchOrders()

  // Real-time synchronization when Admin updates status or deletes orders
  unsubscribeEvents = subscribeToOrderEvents((event) => {
    if (event.action === 'STATUS_UPDATED') {
      const match = orders.value.find(o => o.order_number === event.orderNumber || o.id === event.orderId)
      if (match && event.status) {
        match.status = event.status
        match.order_status = event.status
      }
    } else if (event.action === 'ORDER_DELETED') {
      orders.value = orders.value.filter(o => o.order_number !== event.orderNumber && o.id !== event.orderId)
    } else if (event.action === 'ORDER_CREATED') {
      fetchOrders(true)
    }
  })
})

onUnmounted(() => {
  if (unsubscribeEvents) {
    unsubscribeEvents()
    unsubscribeEvents = null
  }
})

const trackOrder = (orderNum: string) => {
  router.push(`/track/${orderNum}`)
}
</script>

<template>
  <div class="orders-page">
    <div class="container">
      <div class="page-head">
        <div>
          <span class="sub-badge">ORDER HISTORY</span>
          <h1 class="page-title">My Orders & Shipments</h1>
          <p class="page-subtitle">Track genuine performance deliveries and certified installation appointments</p>
        </div>
        <router-link to="/shop" class="btn-shop-new">
          <ShoppingBag :size="16" />
          <span>Browse Catalog</span>
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-box glass-panel">
        <Loader2 :size="32" class="spin-icon text-orange" />
        <p>Loading your orders from database...</p>
      </div>

      <!-- Empty State (No orders yet) -->
      <div v-else-if="orders.length === 0" class="empty-state glass-panel">
        <div class="empty-icon-wrap">
          <Package :size="48" class="text-orange" />
        </div>
        <h3 class="empty-title">No Orders Placed Yet</h3>
        <p class="empty-desc">
          Your order history is completely clean. Explore our catalog of 100% genuine titanium exhausts, racing brakes, and performance suspension parts!
        </p>
        <router-link to="/shop" class="btn-start-shopping">
          <ShoppingBag :size="16" />
          <span>Start Shopping Now</span>
        </router-link>
      </div>

      <!-- Real Orders List -->
      <div v-else class="orders-list">
        <div v-for="order in orders" :key="order.order_number || order.id" class="order-card glass-panel">
          <!-- Order Card Header -->
          <div class="order-card-header">
            <div class="header-left">
              <Package :size="20" class="text-orange" />
              <div>
                <strong class="order-id">{{ order.order_number }}</strong>
                <span class="order-date">Placed on {{ new Date(order.created_at).toLocaleDateString() }}</span>
              </div>
            </div>

            <div class="header-right">
              <span 
                class="status-chip"
                :class="order.status === 'delivered' ? 'status-delivered' : 'status-transit'"
              >
                <CheckCircle2 v-if="order.status === 'delivered'" :size="13" />
                <Truck v-else :size="13" />
                <span style="text-transform: capitalize;">{{ order.status }}</span>
              </span>
            </div>
          </div>

          <!-- Order Items Preview -->
          <div class="order-items-list" v-if="order.items && order.items.length > 0">
            <div v-for="(item, idx) in order.items" :key="idx" class="item-row">
              <img 
                :src="formatImageUrl(item.product_image, DEFAULT_PRODUCT_THUMB)" 
                class="item-thumb" 
                :alt="item.product_name"
                @error="handleProductImageError($event)"
              />
              <div class="item-details">
                <h4 class="item-name">{{ item.product_name }}</h4>
                <p class="item-fitment">SKU: <span>{{ item.product_sku }}</span></p>
                <div class="item-pricing">
                  <span>Qty: {{ item.quantity }}</span>
                  <span class="item-price">${{ Number(item.total_price || item.unit_price).toLocaleString() }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Card Footer -->
          <div class="order-card-footer">
            <div class="footer-meta">
              <div class="meta-entry">
                <span class="meta-label">Total Amount:</span>
                <strong class="total-val">${{ Number(order.total_amount).toLocaleString() }}</strong>
              </div>
              <div class="meta-entry">
                <span class="meta-label">Payment:</span>
                <span class="payment-val" style="text-transform: uppercase;">{{ order.payment_method }} ({{ order.payment_status }})</span>
              </div>
            </div>

            <div class="footer-actions">
              <button class="track-btn" @click="trackOrder(order.order_number)">
                <Truck :size="15" />
                <span>Live Tracking</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.orders-page {
  padding: 3rem 0 5rem;
  min-height: 80vh;
}

.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 1.25rem;
}

.page-head {
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

.btn-shop-new {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #f1f5f9;
  padding: 0.7rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.88rem;
  transition: all 0.2s;
}

.btn-shop-new:hover {
  background: #ff5500;
  border-color: #ff5500;
  color: white;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

.order-card {
  padding: 1.75rem;
}

.order-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  padding-bottom: 1.25rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.text-orange { color: #ff5500; }

.order-id {
  display: block;
  font-size: 1.05rem;
  color: #ffffff;
}

.order-date {
  font-size: 0.8rem;
  color: #64748b;
}

.status-chip {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.3rem 0.75rem;
  border-radius: 9999px;
}

.status-transit {
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.status-delivered {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.order-items-list {
  padding: 1.25rem 0;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.item-row {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.item-thumb {
  width: 65px;
  height: 65px;
  border-radius: 8px;
  object-fit: cover;
  background: rgba(2, 6, 23, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.item-details {
  flex: 1;
}

.item-name {
  color: #f1f5f9;
  font-size: 0.95rem;
  margin: 0 0 0.2rem;
}

.item-fitment {
  color: #94a3b8;
  font-size: 0.8rem;
  margin: 0 0 0.4rem;
}

.item-fitment span {
  color: #ff7834;
}

.item-pricing {
  display: flex;
  gap: 1.25rem;
  font-size: 0.82rem;
  color: #64748b;
}

.item-price {
  color: #ffffff;
  font-weight: 700;
}

.order-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 1.25rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-meta {
  display: flex;
  gap: 2rem;
}

.meta-entry {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.meta-label {
  font-size: 0.75rem;
  color: #64748b;
}

.total-val {
  color: #ff5500;
  font-size: 1.2rem;
  font-weight: 800;
}

.payment-val {
  color: #94a3b8;
  font-size: 0.85rem;
}

.track-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
}

.track-btn:hover {
  background: #ff6a1f;
  transform: translateY(-1px);
}

/* Loading & Empty States */
.loading-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 4rem 2rem;
  border-radius: 16px;
  color: #94a3b8;
}

.spin-icon {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 4.5rem 2rem;
  border-radius: 16px;
}

.empty-icon-wrap {
  width: 80px;
  height: 80px;
  background: rgba(255, 85, 0, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  border: 1px solid rgba(255, 85, 0, 0.25);
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 0.5rem;
}

.empty-desc {
  max-width: 520px;
  margin: 0 auto 2rem;
  color: #94a3b8;
  font-size: 0.95rem;
  line-height: 1.6;
}

.btn-start-shopping {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #ff5500 0%, #ff7700 100%);
  color: white;
  padding: 0.85rem 1.85rem;
  border-radius: 10px;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 4px 20px rgba(255, 85, 0, 0.35);
  transition: all 0.25s ease;
}

.btn-start-shopping:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(255, 85, 0, 0.5);
}
</style>
