<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import {
  CheckCircle2,
  Package,
  Truck,
  ShieldCheck,
  ArrowRight,
  ExternalLink,
  ShoppingBag,
  QrCode,
  MapPin,
  Calendar,
  Sparkles
} from 'lucide-vue-next'
import { formatImageUrl, DEFAULT_PRODUCT_THUMB } from '@/utils/image'
import { broadcastOrderCreated } from '@/utils/orderRealtime'

const route = useRoute()
const router = useRouter()

const orderNumber = ref<string>((route.params.orderNumber as string) || '')
const orderData = ref<any>(null)
const items = ref<any[]>([])
const isLoading = ref(true)

const fetchOrderDetails = async () => {
  if (!orderNumber.value) {
    isLoading.value = false
    return
  }

  isLoading.value = true
  try {
    const res = await axios.get(`/api/v1/orders/track/${orderNumber.value}`)
    if (res.data?.data) {
      orderData.value = res.data.data.order
      items.value = res.data.data.items || []

      // Broadcast order creation to ensure Admin Command Center updates immediately
      if (orderData.value) {
        broadcastOrderCreated(orderData.value.order_number, orderData.value.id, {
          customerName: orderData.value.customer_name,
          totalAmount: orderData.value.total_amount,
          itemsCount: items.value.length,
          trackingNumber: orderData.value.tracking_number
        })
      }
    }
  } catch (err) {
    console.warn('Could not load order details:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchOrderDetails()
})
</script>

<template>
  <div class="confirmation-page">
    <div class="container">
      <!-- Success Banner -->
      <div class="success-card glass-panel">
        <div class="success-icon-wrap">
          <CheckCircle2 :size="54" class="text-green" />
        </div>
        <span class="badge-tag">AUTHENTIC PAYMENT CONFIRMED</span>
        <h1 class="success-title">Thank You For Your Order!</h1>
        <p class="success-desc">
          Your Bakong KHQR payment has been verified instantly. Genuine performance motorsport parts are now entering quality inspection and priority air-freight fulfillment.
        </p>

        <!-- Order Reference Card -->
        <div class="ref-pill-card">
          <div class="ref-item">
            <small>Order Number</small>
            <strong class="text-orange">{{ orderNumber }}</strong>
          </div>
          <div class="ref-divider"></div>
          <div class="ref-item">
            <small>Payment Status</small>
            <span class="paid-badge">
              <span class="dot-green"></span>
              PAID VIA BAKONG KHQR
            </span>
          </div>
          <div class="ref-divider"></div>
          <div class="ref-item">
            <small>Fulfillment Carrier</small>
            <strong>Apex Express Air-Freight</strong>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="actions-row">
          <router-link :to="`/track/${orderNumber}`" class="btn-primary-track">
            <Truck :size="18" />
            <span>Track Order & Live Flight Logistics</span>
            <ArrowRight :size="16" />
          </router-link>
          <router-link to="/account/orders" class="btn-outline-orders">
            <Package :size="18" />
            <span>View All My Orders</span>
          </router-link>
        </div>
      </div>

      <!-- Order Summary Card -->
      <div v-if="items.length > 0" class="items-card glass-panel">
        <h3 class="items-card-title">
          <Package :size="20" class="text-orange" />
          <span>Purchased Performance Items ({{ items.length }})</span>
        </h3>

        <div class="items-list">
          <div v-for="item in items" :key="item.id" class="order-item-row">
            <img 
              :src="formatImageUrl(item.product_image, DEFAULT_PRODUCT_THUMB)" 
              class="item-img" 
              :alt="item.product_name" 
            />
            <div class="item-info">
              <strong class="item-name">{{ item.product_name }}</strong>
              <div class="item-tags">
                <span class="sku-tag">{{ item.product_sku }}</span>
                <span class="qty-tag">Qty: {{ item.quantity }}</span>
              </div>
            </div>
            <div class="item-price-col">
              <strong class="item-price">${{ Number(item.total_price || item.unit_price * item.quantity).toLocaleString() }}</strong>
              <small class="item-unit">${{ Number(item.unit_price).toLocaleString() }} each</small>
            </div>
          </div>
        </div>

        <!-- Total Breakdown -->
        <div v-if="orderData" class="totals-breakdown">
          <div class="tot-row">
            <span>Subtotal</span>
            <strong>${{ Number(orderData.subtotal).toLocaleString() }}</strong>
          </div>
          <div class="tot-row">
            <span>Air-Freight Delivery</span>
            <strong>${{ Number(orderData.shipping_fee || 15).toLocaleString() }}</strong>
          </div>
          <div v-if="Number(orderData.discount_amount) > 0" class="tot-row text-green">
            <span>Promotional Discount</span>
            <strong>-${{ Number(orderData.discount_amount).toLocaleString() }}</strong>
          </div>
          <div class="tot-row tot-final">
            <span>Total Paid (USD)</span>
            <strong class="text-orange">${{ Number(orderData.total_amount).toLocaleString() }}</strong>
          </div>
        </div>
      </div>

      <!-- Logistics Promise Card -->
      <div class="logistics-info-card glass-panel">
        <div class="info-col">
          <div class="icon-circle"><ShieldCheck :size="24" class="text-orange" /></div>
          <h4>100% Genuine Certified</h4>
          <p>Factory sealed with QR security fitment label direct from European & Japanese manufacturers.</p>
        </div>
        <div class="info-col">
          <div class="icon-circle"><Truck :size="24" class="text-cyan" /></div>
          <h4>Fast Air-Freight Delivery</h4>
          <p>Air cargo dispatch within 24h. Certified hub collection or home delivery across Cambodia.</p>
        </div>
        <div class="info-col">
          <div class="icon-circle"><MapPin :size="24" class="text-green" /></div>
          <h4>Partner Garage Network</h4>
          <p>Over 12 certified partner workshops in Phnom Penh ready for professional dyno & mounting.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.confirmation-page {
  padding: 3rem 0 6rem;
  min-height: 85vh;
  background: #050811;
  color: #f1f5f9;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
}

/* Success Card */
.success-card {
  text-align: center;
  padding: 3.5rem 2rem 3rem;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
}

.success-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 320px;
  height: 120px;
  background: radial-gradient(ellipse, rgba(34, 197, 94, 0.25) 0%, transparent 70%);
  pointer-events: none;
}

.success-icon-wrap {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: rgba(34, 197, 94, 0.12);
  border: 2px solid rgba(34, 197, 94, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  box-shadow: 0 0 35px rgba(34, 197, 94, 0.25);
}

.badge-tag {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: #22c55e;
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.25);
  padding: 0.3rem 0.85rem;
  border-radius: 9999px;
  display: inline-block;
  margin-bottom: 1rem;
}

.success-title {
  font-size: 2.2rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.75rem;
  letter-spacing: -0.02em;
}

.success-desc {
  color: #94a3b8;
  font-size: 1rem;
  line-height: 1.6;
  max-width: 620px;
  margin: 0 auto 2rem;
}

/* Ref Pill */
.ref-pill-card {
  display: flex;
  align-items: center;
  justify-content: space-around;
  background: rgba(2, 6, 23, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.25rem;
  max-width: 650px;
  margin: 0 auto 2.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.ref-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
}

.ref-item small {
  font-size: 0.72rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.05em;
}

.ref-item strong {
  font-size: 1.05rem;
  color: white;
}

.ref-divider {
  width: 1px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
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
}

.dot-green {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 8px #22c55e;
}

.text-orange { color: var(--orange-500); }
.text-cyan { color: #00d2ff; }
.text-green { color: #22c55e; }

/* Buttons */
.actions-row {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-primary-track {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  background: linear-gradient(135deg, var(--orange-500), #ff7700);
  color: white;
  padding: 0.9rem 1.8rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.95rem;
  text-decoration: none;
  box-shadow: 0 4px 20px rgba(249, 115, 22, 0.35);
  transition: all 0.2s;
}

.btn-primary-track:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 26px rgba(249, 115, 22, 0.5);
}

.btn-outline-orders {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #e2e8f0;
  padding: 0.9rem 1.6rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.95rem;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-outline-orders:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

/* Items Card */
.items-card {
  padding: 2rem;
  margin-bottom: 2rem;
}

.items-card-title {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 1.15rem;
  color: white;
  margin: 0 0 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.order-item-row {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.item-img {
  width: 64px;
  height: 64px;
  border-radius: 10px;
  object-fit: cover;
  background: #020617;
  border: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
}

.item-info {
  flex: 1;
}

.item-name {
  color: white;
  font-size: 0.98rem;
  display: block;
  margin-bottom: 0.35rem;
}

.item-tags {
  display: flex;
  gap: 0.5rem;
}

.sku-tag {
  font-family: monospace;
  font-size: 0.75rem;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  color: #94a3b8;
}

.qty-tag {
  font-size: 0.75rem;
  color: #38bdf8;
  font-weight: 700;
}

.item-price-col {
  text-align: right;
}

.item-price {
  color: var(--orange-500);
  font-size: 1.1rem;
  display: block;
}

.item-unit {
  color: #64748b;
  font-size: 0.75rem;
}

/* Totals */
.totals-breakdown {
  margin-top: 1.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.tot-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  color: #94a3b8;
}

.tot-row strong {
  color: white;
}

.tot-final {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px dashed rgba(255, 255, 255, 0.1);
  font-size: 1.15rem;
  color: white;
  font-weight: 800;
}

/* Logistics Card */
.logistics-info-card {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  padding: 2rem;
}

.info-col {
  text-align: center;
}

.icon-circle {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.04);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.info-col h4 {
  color: white;
  font-size: 0.95rem;
  margin: 0 0 0.4rem;
}

.info-col p {
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.5;
  margin: 0;
}
</style>
