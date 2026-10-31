<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import {
  ShoppingBag,
  Search,
  Truck,
  CheckCircle2,
  Clock,
  ExternalLink,
  Package,
  Calendar,
  DollarSign,
  Eye,
  X,
  MapPin,
  Phone,
  Mail,
  ShieldCheck,
  ChevronRight,
  Trash2
} from 'lucide-vue-next'

import { formatImageUrl, DEFAULT_PRODUCT_THUMB } from '@/utils/image'
import { 
  broadcastOrderUpdate, 
  broadcastOrderDelete, 
  subscribeToOrderEvents 
} from '@/utils/orderRealtime'
import { useLanguageStore } from '@/stores/language'

const langStore = useLanguageStore()
const orders = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedOrder = ref<any>(null)
const isModalOpen = ref(false)
const updateSuccess = ref('')
const newOrderAlert = ref<{ orderNumber: string; customer: string; amount: number; itemsCount?: number } | null>(null)
const highlightedOrderIds = ref<Set<number>>(new Set())

let unsubscribeRealtime: (() => void) | null = null
let pollingTimer: any = null

/**
 * Play a modern two-tone chime via Web Audio API without requiring external audio files
 */
const playOrderNotificationSound = () => {
  try {
    const AudioCtx = window.AudioContext || (window as any).webkitAudioContext
    if (!AudioCtx) return
    const ctx = new AudioCtx()
    const now = ctx.currentTime

    // Tone 1 (High note)
    const osc1 = ctx.createOscillator()
    const gain1 = ctx.createGain()
    osc1.type = 'sine'
    osc1.frequency.setValueAtTime(587.33, now) // D5
    gain1.gain.setValueAtTime(0.16, now)
    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35)
    osc1.connect(gain1)
    gain1.connect(ctx.destination)
    osc1.start(now)
    osc1.stop(now + 0.35)

    // Tone 2 (Resolving chord)
    const osc2 = ctx.createOscillator()
    const gain2 = ctx.createGain()
    osc2.type = 'sine'
    osc2.frequency.setValueAtTime(880.00, now + 0.16) // A5
    gain2.gain.setValueAtTime(0.2, now + 0.16)
    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.6)
    osc2.connect(gain2)
    gain2.connect(ctx.destination)
    osc2.start(now + 0.16)
    osc2.stop(now + 0.6)
  } catch (e) {
    // Ignore audio permission/context errors
  }
}

const triggerNewOrderBanner = (order: any) => {
  playOrderNotificationSound()
  newOrderAlert.value = {
    orderNumber: order.order_number || 'MCC-2026-NEW',
    customer: order.customer_name || 'Customer',
    amount: Number(order.total_amount || 0),
    itemsCount: order.items_count || order.items?.length || 1
  }
  if (order.id) {
    highlightedOrderIds.value.add(order.id)
  }
  setTimeout(() => {
    if (newOrderAlert.value?.orderNumber === order.order_number) {
      newOrderAlert.value = null
    }
  }, 10000)
}

const fetchOrders = async (silent = false) => {
  if (!silent) isLoading.value = true
  try {
    const res = await axios.get('/api/v1/admin/orders')
    const freshOrders = res.data.data || []

    // Detect if new orders were placed from another tab or device
    if (silent && orders.value.length > 0 && freshOrders.length > orders.value.length) {
      const knownIds = new Set(orders.value.map(o => o.id))
      const brandNew = freshOrders.filter((o: any) => !knownIds.has(o.id))
      if (brandNew.length > 0) {
        brandNew.forEach((o: any) => triggerNewOrderBanner(o))
      }
    }

    orders.value = freshOrders
  } catch (err) {
    console.error('Failed to load orders:', err)
  } finally {
    if (!silent) isLoading.value = false
  }
}

onMounted(() => {
  fetchOrders()

  // 1. Instant cross-tab real-time event listener
  unsubscribeRealtime = subscribeToOrderEvents((event) => {
    if (event.action === 'ORDER_CREATED') {
      playOrderNotificationSound()
      newOrderAlert.value = {
        orderNumber: event.orderNumber || 'MCC-2026-NEW',
        customer: event.customerName || 'Customer',
        amount: event.totalAmount || 0,
        itemsCount: event.itemsCount || 1
      }
      fetchOrders(true)
      setTimeout(() => {
        newOrderAlert.value = null
      }, 10000)
    } else if (event.action === 'STATUS_UPDATED') {
      const target = orders.value.find(o => o.order_number === event.orderNumber || o.id === event.orderId)
      if (target && event.status) {
        target.status = event.status
        target.order_status = event.status
      }
    } else if (event.action === 'ORDER_DELETED') {
      orders.value = orders.value.filter(o => o.order_number !== event.orderNumber && o.id !== event.orderId)
    }
  })

  // 2. Background polling every 2.5 seconds (for cross-browser / mobile purchases)
  pollingTimer = setInterval(() => {
    fetchOrders(true)
  }, 2500)
})

onUnmounted(() => {
  if (unsubscribeRealtime) {
    unsubscribeRealtime()
    unsubscribeRealtime = null
  }
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
})

const updateOrderStatus = async (order: any, newStatus: string) => {
  try {
    await axios.put(`/api/v1/admin/orders/${order.id}/status`, { status: newStatus })
    order.status = newStatus
    order.order_status = newStatus
    updateSuccess.value = `Order ${order.order_number} status updated to ${newStatus.toUpperCase()}`
    
    // Broadcast real-time status update to all user screens
    broadcastOrderUpdate(order.order_number, newStatus, order.id)

    setTimeout(() => { updateSuccess.value = '' }, 3500)
  } catch (err) {
    console.error('Failed to update status:', err)
  }
}

const openOrderModal = (order: any) => {
  selectedOrder.value = order
  isModalOpen.value = true
}

const filteredOrders = () => {
  if (!searchQuery.value.trim()) return orders.value
  const q = searchQuery.value.toLowerCase()
  return orders.value.filter(o => 
    o.order_number?.toLowerCase().includes(q) ||
    o.customer_name?.toLowerCase().includes(q) ||
    o.customer_email?.toLowerCase().includes(q) ||
    o.tracking_number?.toLowerCase().includes(q)
  )
}

const isDeleting = ref(false)

const deleteOrder = async (order: any) => {
  if (!order || !order.id) return
  const confirmText = `Are you sure you want to permanently delete Order #${order.order_number}?\nThis will remove the order and all items from the database.`
  if (!confirm(confirmText)) return

  isDeleting.value = true
  const orderNum = order.order_number
  const orderId = order.id
  try {
    const res = await axios.delete(`/api/v1/admin/orders/${orderId}`)
    updateSuccess.value = res.data?.message || `Order ${orderNum} deleted permanently!`
    orders.value = orders.value.filter(o => o.id !== orderId)
    if (selectedOrder.value?.id === orderId) {
      isModalOpen.value = false
      selectedOrder.value = null
    }

    // Broadcast real-time deletion to user tracking and history screens
    broadcastOrderDelete(orderNum, orderId)

    setTimeout(() => { updateSuccess.value = '' }, 4000)
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to delete order.')
  } finally {
    isDeleting.value = false
  }
}
</script>


<template>
  <div class="admin-orders-page">
    <div class="admin-page-container">
      <!-- Page Header -->
      <div class="page-top-bar">
        <div>
          <span class="sub-badge">{{ langStore.t.admin?.fulfillmentLogistics || 'FULFILLMENT & LOGISTICS CONTROL' }}</span>
          <h1 class="page-title">{{ langStore.t.admin?.ordersShipments || 'Orders & Shipments Management' }}</h1>
          <p class="page-sub">{{ langStore.t.admin?.ordersSub || 'Monitor live customer purchases, Bakong KHQR payments, and air-freight dispatch' }}</p>
        </div>

        <div class="header-right-badges">
          <div class="live-purchases-badge">
            <span class="live-radar-dot"></span>
            <span>{{ langStore.t.admin?.liveRealtimeSync || 'LIVE REAL-TIME SYNC ACTIVE' }}</span>
          </div>
          <div class="header-stat-pill">
            <Package :size="16" class="text-orange" />
            <span>{{ langStore.t.admin?.totalOrdersCount || 'Total Orders:' }} <strong>{{ orders.length }}</strong></span>
          </div>
        </div>
      </div>

      <!-- Real-Time New Purchase Alert Banner -->
      <transition name="slide-alert">
        <div v-if="newOrderAlert" class="realtime-new-order-toast">
          <div class="alert-left">
            <div class="alert-icon-ring">
              <ShoppingBag :size="20" class="text-green" />
            </div>
            <div class="alert-texts">
              <div class="alert-title-row">
                <span class="pulse-tag">🎉 {{ langStore.t.admin?.newPurchaseAlert || 'NEW CUSTOMER PURCHASE!' }}</span>
                <span class="alert-timestamp">Just now</span>
              </div>
              <p class="alert-desc">
                Customer <strong>{{ newOrderAlert.customer }}</strong> has just completed payment for Order 
                <strong class="text-cyan">#{{ newOrderAlert.orderNumber }}</strong> 
                amounting to <strong class="text-orange">${{ Number(newOrderAlert.amount || 0).toLocaleString() }} USD</strong>!
              </p>
            </div>
          </div>
          <div class="alert-actions">
            <button class="btn-dismiss-toast" @click="newOrderAlert = null">&times;</button>
          </div>
        </div>
      </transition>

      <!-- Update Alert -->
      <div v-if="updateSuccess" class="alert-success-banner">
        <CheckCircle2 :size="16" />
        <span>{{ updateSuccess }}</span>
      </div>

      <!-- Filter / Search Box -->
      <div class="filters-card glass-panel">
        <div class="search-input-box">
          <Search :size="16" class="search-icon" />
          <input 
            v-model="searchQuery" 
            type="text" 
            :placeholder="langStore.t.admin?.searchOrderPlaceholder || 'Search by order # (MCC-2026-...), customer name, email, or tracking #...'" 
            class="search-input"
          />
        </div>
        <button class="btn-refresh" @click="fetchOrders">
          {{ langStore.t.admin?.refreshList || 'Refresh List' }}
        </button>
      </div>

      <!-- Orders Table -->
      <div class="table-container glass-panel">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <span>Loading orders from database...</span>
        </div>

        <table v-else class="orders-table">
          <thead>
            <tr>
              <th>{{ langStore.t.admin?.thOrderNumber || 'Order Number' }}</th>
              <th>{{ langStore.t.admin?.thCustomer || 'Customer' }}</th>
              <th>{{ langStore.t.admin?.thItems || 'Items & Parts' }}</th>
              <th>{{ langStore.t.admin?.thTotal || 'Total Amount' }}</th>
              <th>{{ langStore.t.admin?.thPayment || 'Payment' }}</th>
              <th>{{ langStore.t.admin?.thStatus || 'Status' }}</th>
              <th>{{ langStore.t.admin?.thDate || 'Order Date' }}</th>
              <th>{{ langStore.t.admin?.thActions || 'Actions' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredOrders().length === 0">
              <td colspan="8" class="empty-orders-cell">
                <ShoppingBag :size="40" class="text-orange" style="margin-bottom: 0.5rem;" />
                <p>No customer orders found in the database.</p>
              </td>
            </tr>
            <tr 
              v-for="order in filteredOrders()" 
              :key="order.id"
              :class="{ 'row-new-highlight': highlightedOrderIds.has(order.id) }"
            >
              <td>
                <span class="order-id-chip">{{ order.order_number }}</span>
                <small v-if="order.tracking_number" class="tracking-sub">{{ order.tracking_number }}</small>
              </td>
              <td>
                <div class="cust-info">
                  <strong>{{ order.customer_name || 'Customer' }}</strong>
                  <small>{{ order.customer_email || order.customer_phone || '—' }}</small>
                </div>
              </td>
              <td class="items-cell">
                <div v-if="order.items && order.items.length > 0" class="items-preview">
                  <div class="thumbs-stack">
                    <img 
                      v-for="(itm, idx) in order.items.slice(0, 3)" 
                      :key="idx" 
                      :src="formatImageUrl(itm.product_image, DEFAULT_PRODUCT_THUMB)" 
                      class="mini-thumb" 
                      :title="itm.product_name"
                    />
                  </div>
                  <span class="items-count-text">{{ order.items_count || order.items.length }} Part(s)</span>
                </div>
                <span v-else class="text-muted">1 Part</span>
              </td>
              <td>
                <strong class="total-price">${{ Number(order.total_amount).toLocaleString() }}</strong>
                <small class="currency-tag">USD</small>
              </td>
              <td>
                <span class="pay-chip">
                  <span class="dot-green"></span>
                  {{ (order.payment_method || 'Bakong KHQR').toUpperCase() }}
                </span>
              </td>
              <td>
                <select 
                  :value="order.status || order.order_status" 
                  class="status-select"
                  :class="`select-${order.status || order.order_status}`"
                  @change="updateOrderStatus(order, ($event.target as HTMLSelectElement).value)"
                >
                  <option value="confirmed">{{ langStore.t.admin?.statusConfirmed || 'Confirmed (Paid)' }}</option>
                  <option value="processing">{{ langStore.t.admin?.statusProcessing || 'Processing (Inspection)' }}</option>
                  <option value="shipped">{{ langStore.t.admin?.statusShipped || 'Shipped (Air-Freight)' }}</option>
                  <option value="delivered">{{ langStore.t.admin?.statusDelivered || 'Delivered' }}</option>
                  <option value="cancelled">{{ langStore.t.admin?.statusCancelled || 'Cancelled' }}</option>
                </select>
              </td>
              <td>
                <span class="date-text">{{ new Date(order.created_at).toLocaleDateString() }}</span>
              </td>
              <td class="actions-col">
                <button class="btn-inspect-order" :title="langStore.t.admin?.inspect || 'Inspect'" @click="openOrderModal(order)">
                  <Eye :size="15" />
                  <span>{{ langStore.t.admin?.inspect || 'Inspect' }}</span>
                </button>
                <button class="btn-delete-order" :title="langStore.t.admin?.delete || 'Delete'" @click="deleteOrder(order)">
                  <Trash2 :size="14" />
                  <span>{{ langStore.t.admin?.delete || 'Delete' }}</span>
                </button>
                <router-link :to="`/track/${order.order_number}`" class="btn-public-track" title="Public Tracking" target="_blank">
                  <ExternalLink :size="15" />
                </router-link>
              </td>

            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Order Inspection Modal -->
    <div v-if="isModalOpen && selectedOrder" class="modal-backdrop" @click.self="isModalOpen = false">
      <div class="modal-box glass-panel">
        <div class="modal-header">
          <div>
            <span class="modal-badge">{{ langStore.t.admin?.orderInspection || 'ORDER INSPECTION' }}</span>
            <h2 class="modal-order-num">{{ selectedOrder.order_number }}</h2>
          </div>
          <button class="btn-close" @click="isModalOpen = false">&times;</button>
        </div>

        <div class="modal-body">
          <!-- Customer & Logistics Row -->
          <div class="info-grid-2">
            <div class="info-card">
              <span class="info-lbl">{{ langStore.t.admin?.customerDetails || 'Customer Details' }}</span>
              <strong>{{ selectedOrder.customer_name || 'Member' }}</strong>
              <small>{{ selectedOrder.customer_email }}</small>
              <small>{{ selectedOrder.customer_phone || '+855 23 888 999' }}</small>
            </div>
            <div class="info-card">
              <span class="info-lbl">{{ langStore.t.admin?.logisticsDispatch || 'Logistics Dispatch' }}</span>
              <strong>{{ selectedOrder.courier_name || 'Apex Express Air-Freight' }}</strong>
              <small>Tracking: <span class="text-cyan">{{ selectedOrder.tracking_number || 'APX-782192' }}</span></small>
              <small>Destination: Phnom Penh, Cambodia</small>
            </div>
          </div>

          <!-- Items List -->
          <div class="modal-items-section">
            <h4 class="sec-title">Purchased Items Breakdown</h4>
            <div class="modal-items-list">
              <div v-for="itm in selectedOrder.items || []" :key="itm.id" class="modal-item-row">
                <img 
                  :src="formatImageUrl(itm.product_image, DEFAULT_PRODUCT_THUMB)" 
                  class="modal-item-img" 
                />
                <div class="modal-item-info">
                  <strong class="modal-item-name">{{ itm.product_name }}</strong>
                  <div class="modal-item-tags">
                    <span class="sku-tag">{{ itm.product_sku }}</span>
                    <span class="qty-tag">Qty: {{ itm.quantity }}</span>
                  </div>
                </div>
                <div class="modal-item-price">
                  <strong>${{ Number(itm.total_price || itm.unit_price * itm.quantity).toLocaleString() }}</strong>
                  <small>${{ Number(itm.unit_price).toLocaleString() }} each</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Breakdown -->
          <div class="modal-totals">
            <div class="m-row">
              <span>Subtotal:</span>
              <strong>${{ Number(selectedOrder.subtotal).toLocaleString() }}</strong>
            </div>
            <div class="m-row">
              <span>Air-Freight Delivery:</span>
              <strong>${{ Number(selectedOrder.shipping_fee || 15).toLocaleString() }}</strong>
            </div>
            <div class="m-row m-total">
              <span>Total Paid via Bakong KHQR:</span>
              <strong class="text-orange">${{ Number(selectedOrder.total_amount).toLocaleString() }} USD</strong>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-modal-delete" title="Permanently delete this order" @click="deleteOrder(selectedOrder)">
            <Trash2 :size="14" />
            <span>Delete Order</span>
          </button>
          <div class="modal-footer-right">
            <router-link :to="`/track/${selectedOrder.order_number}`" class="btn-live-track-link" target="_blank">
              <Truck :size="15" />
              <span>Flight Tracking</span>
              <ExternalLink :size="13" />
            </router-link>
            <button class="btn-done" @click="isModalOpen = false">Done</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-orders-page {
  padding: 1.75rem 0 5rem;
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
}

.admin-page-container {
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 1.75rem !important;
  box-sizing: border-box !important;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

.page-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.25rem;
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
  margin-bottom: 0.35rem;
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

.header-right-badges {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.live-purchases-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.35);
  color: #4ade80;
  padding: 0.5rem 0.9rem;
  border-radius: 8px;
  font-size: 0.76rem;
  font-weight: 800;
  letter-spacing: 0.06em;
}

.live-radar-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 10px #22c55e;
  animation: radar-blink 1.5s infinite;
}

@keyframes radar-blink {
  0% { transform: scale(0.9); opacity: 0.5; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
  50% { transform: scale(1.3); opacity: 1; box-shadow: 0 0 10px 4px rgba(34, 197, 94, 0.3); }
  100% { transform: scale(0.9); opacity: 0.5; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

.header-stat-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.55rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #cbd5e1;
}

.header-stat-pill strong {
  color: white;
}

/* Real-Time New Purchase Toast */
.realtime-new-order-toast {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.18) 0%, rgba(14, 165, 233, 0.18) 100%);
  border: 1.5px solid rgba(34, 197, 94, 0.5);
  box-shadow: 0 8px 30px rgba(34, 197, 94, 0.25);
  backdrop-filter: blur(14px);
  padding: 1rem 1.4rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  animation: toast-pulse 2s infinite alternate;
}

@keyframes toast-pulse {
  0% { box-shadow: 0 0 15px rgba(34, 197, 94, 0.2); }
  100% { box-shadow: 0 0 30px rgba(14, 165, 233, 0.35); }
}

.alert-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.alert-icon-ring {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(34, 197, 94, 0.18);
  border: 1px solid rgba(34, 197, 94, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 0 16px rgba(34, 197, 94, 0.3);
}

.alert-texts {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.alert-title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.pulse-tag {
  font-size: 0.75rem;
  font-weight: 800;
  color: #4ade80;
  letter-spacing: 0.06em;
}

.alert-timestamp {
  font-size: 0.72rem;
  color: #94a3b8;
}

.alert-desc {
  margin: 0;
  color: #ffffff;
  font-size: 0.92rem;
  line-height: 1.4;
}

.alert-desc strong {
  color: #ffffff;
}

.btn-dismiss-toast {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0 0.5rem;
  transition: color 0.2s;
}

.btn-dismiss-toast:hover {
  color: #ffffff;
}

.slide-alert-enter-active,
.slide-alert-leave-active {
  transition: all 0.35s ease;
}

.slide-alert-enter-from,
.slide-alert-leave-to {
  opacity: 0;
  transform: translateY(-16px);
}

/* Row Flash Highlight for New Orders */
.orders-table tbody tr.row-new-highlight {
  background: rgba(34, 197, 94, 0.12) !important;
  animation: row-flash 2.5s infinite alternate;
}

@keyframes row-flash {
  0% { background: rgba(34, 197, 94, 0.08); }
  100% { background: rgba(34, 197, 94, 0.22); }
}

.alert-success-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(34, 197, 94, 0.15);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #4ade80;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  margin-bottom: 1.5rem;
}

/* Filters */
.filters-card {
  padding: 0.85rem 1.25rem;
  display: flex;
  gap: 1rem;
  margin-bottom: 1.75rem;
}

.search-input-box {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.9rem;
  color: #64748b;
}

.search-input {
  width: 100%;
  background: rgba(2, 6, 23, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #ffffff;
  padding: 0.65rem 0.9rem 0.65rem 2.4rem;
  font-size: 0.88rem;
  outline: none;
}

.search-input:focus {
  border-color: #ff5500;
}

.btn-refresh {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #cbd5e1;
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}

/* Table */
.table-container {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  box-sizing: border-box;
}

.orders-table {
  width: 100%;
  min-width: 1000px;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.88rem;
}

.orders-table th {
  background: rgba(2, 6, 23, 0.5);
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  white-space: nowrap;
}

.orders-table td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  vertical-align: middle;
}

.order-id-chip {
  font-family: monospace;
  font-weight: 700;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.1);
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
  font-size: 0.82rem;
  display: inline-block;
  white-space: nowrap;
}

.tracking-sub {
  display: block;
  font-family: monospace;
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.cust-info strong {
  display: block;
  color: white;
  font-size: 0.9rem;
}

.cust-info small {
  color: #64748b;
  font-size: 0.75rem;
}

.items-preview {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.thumbs-stack {
  display: flex;
  margin-right: 0.2rem;
}

.mini-thumb {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  object-fit: cover;
  border: 1.5px solid #0f172a;
  margin-left: -8px;
  background: #020617;
}

.mini-thumb:first-child {
  margin-left: 0;
}

.items-count-text {
  font-size: 0.8rem;
  color: #cbd5e1;
  font-weight: 600;
}

.total-price {
  color: #ff5500;
  font-size: 1rem;
}

.currency-tag {
  color: #64748b;
  font-size: 0.72rem;
  margin-left: 0.25rem;
}

.pay-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  font-weight: 700;
  background: rgba(34, 197, 94, 0.12);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.25);
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
  white-space: nowrap;
}

.dot-green {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #22c55e;
}

.status-select {
  background: #080c16;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 6px;
  color: white;
  padding: 0.35rem 0.65rem;
  font-size: 0.8rem;
  font-weight: 700;
  outline: none;
  cursor: pointer;
}

.select-confirmed { border-color: rgba(56, 189, 248, 0.4); color: #38bdf8; }
.select-processing { border-color: rgba(251, 191, 36, 0.4); color: #fbbf24; }
.select-shipped { border-color: rgba(168, 85, 247, 0.4); color: #c084fc; }
.select-delivered { border-color: rgba(34, 197, 94, 0.4); color: #4ade80; }

.date-text {
  color: #94a3b8;
  font-size: 0.8rem;
  white-space: nowrap;
}

.actions-col {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-inspect-order {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(255, 85, 0, 0.15);
  border: 1px solid rgba(255, 85, 0, 0.3);
  color: #ff5500;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-inspect-order:hover {
  background: #ff5500;
  color: white;
}

.btn-delete-order {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.35);
  color: #ef4444;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-delete-order:hover {
  background: #ef4444;
  color: white;
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
}


.btn-public-track {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-public-track:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
}

.empty-orders-cell {
  text-align: center;
  padding: 3.5rem 1rem !important;
  color: #94a3b8;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.5rem;
  backdrop-filter: blur(4px);
}

.modal-box {
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  overflow-y: auto;
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.7);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 1.5rem;
}

.modal-badge {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #ff7834;
}

.modal-order-num {
  font-size: 1.6rem;
  color: white;
  margin: 0.2rem 0 0;
  font-weight: 800;
}

.btn-close {
  background: none;
  border: none;
  color: #64748b;
  font-size: 1.5rem;
  cursor: pointer;
}

.info-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.info-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.info-lbl {
  font-size: 0.72rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 700;
  margin-bottom: 0.2rem;
}

.info-card strong {
  color: white;
  font-size: 0.95rem;
}

.info-card small {
  color: #94a3b8;
  font-size: 0.8rem;
}

.sec-title {
  color: white;
  font-size: 0.95rem;
  margin: 0 0 0.85rem;
  font-weight: 700;
}

.modal-items-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.modal-item-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(2, 6, 23, 0.4);
  padding: 0.75rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.04);
}

.modal-item-img {
  width: 48px;
  height: 48px;
  border-radius: 6px;
  object-fit: cover;
  background: #020617;
}

.modal-item-info {
  flex: 1;
}

.modal-item-name {
  color: white;
  font-size: 0.88rem;
  display: block;
}

.modal-item-tags {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.2rem;
}

.sku-tag {
  font-family: monospace;
  font-size: 0.72rem;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
}

.qty-tag {
  font-size: 0.72rem;
  color: #38bdf8;
  font-weight: 700;
}

.modal-item-price {
  text-align: right;
}

.modal-item-price strong {
  color: #ff5500;
  font-size: 0.95rem;
  display: block;
}

.modal-item-price small {
  color: #64748b;
  font-size: 0.72rem;
}

.modal-totals {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.m-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #94a3b8;
}

.m-row strong {
  color: white;
}

.m-total {
  margin-top: 0.4rem;
  padding-top: 0.6rem;
  border-top: 1px dashed rgba(255, 255, 255, 0.1);
  font-size: 1rem;
  font-weight: 800;
  color: white;
}

.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.modal-footer-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-modal-delete {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.4);
  color: #ef4444;
  padding: 0.55rem 1rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-modal-delete:hover {
  background: #ef4444;
  color: white;
  box-shadow: 0 0 14px rgba(239, 68, 68, 0.4);
}


.btn-live-track-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #38bdf8;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
}

.btn-done {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: white;
  padding: 0.55rem 1.4rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}

.text-orange { color: #ff5500; }
.text-cyan { color: #38bdf8; }
</style>
