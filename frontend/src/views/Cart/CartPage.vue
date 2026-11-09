<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useLanguageStore } from '@/stores/language'
import axios from 'axios'
import {
  ShoppingBag,
  Trash2,
  Plus,
  Minus,
  Truck,
  ShieldCheck,
  Wrench,
  ArrowRight,
  ArrowLeft,
  QrCode,
  CheckCircle2,
  Sparkles,
  Building2
} from 'lucide-vue-next'
import { broadcastOrderCreated } from '@/utils/orderRealtime'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()
const langStore = useLanguageStore()

const promoCode = ref('')
const promoApplied = ref(false)
const promoError = ref('')
const isCheckingOut = ref(false)
const showKhqrModal = ref(false)
const checkoutError = ref('')

const applyPromo = () => {
  if (promoCode.value.toUpperCase() === 'MOTOCAR2026') {
    promoApplied.value = true
    promoError.value = ''
  } else {
    promoError.value = 'Invalid coupon code. Try MOTOCAR2026'
  }
}

const handleProceedCheckout = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login?redirect=/checkout')
    return
  }
  router.push('/checkout')
}

const confirmPayment = async () => {
  isCheckingOut.value = true
  try {
    const subtotal = cartStore.subtotal || 180
    const shippingFee = 15
    const discount = promoApplied.value ? (subtotal * 0.1) : 0
    const totalAmount = subtotal + shippingFee - discount

    const payload = {
      user_id: authStore.user?.id,
      items: cartStore.items.map(item => ({
        id: item.id,
        product_id: item.id,
        name: item.name,
        sku: item.sku,
        thumbnail: item.thumbnail,
        price: item.price,
        quantity: item.quantity,
        vehicle_type: item.vehicle_type
      })),
      subtotal: subtotal,
      shipping_fee: shippingFee,
      discount_amount: discount,
      total_amount: totalAmount,
      payment_method: 'khqr',
      payment_status: 'paid',
      coupon_code: promoApplied.value ? promoCode.value : null,
      notes: 'Customer completed payment via Bakong KHQR scan.'
    }

    const headers: Record<string, string> = {
      'X-User-Id': String(authStore.user?.id || 1)
    }
    const token = localStorage.getItem('auth_token')
    if (token) {
      headers['Authorization'] = `Bearer ${token}`
    }

    const res = await axios.post('/api/v1/orders', payload, { headers })
    const orderData = res.data.data
    const orderNumber = orderData?.order_number || ('MCC-2026-' + Math.floor(10000 + Math.random() * 90000))

    // Broadcast instant Real-Time purchase event to Admin Orders & Command Center!
    broadcastOrderCreated(orderNumber, orderData?.id, {
      customerName: authStore.user?.name || 'Customer',
      totalAmount: totalAmount,
      itemsCount: cartStore.items.length,
      trackingNumber: orderData?.tracking_number
    })

    showKhqrModal.value = false
    cartStore.clearCart()

    // Redirect to Order Confirmation page with real order number
    router.push(`/order-confirmation/${orderNumber}`)
  } catch (err: any) {
    console.error('Failed to create order:', err)
    checkoutError.value = err.response?.data?.message || 'Failed to submit order. Please try again.'
  } finally {
    isCheckingOut.value = false
  }
}
</script>

<template>
  <div class="cart-page">
    <div class="container">
      <div class="page-top-bar">
        <div>
          <span class="sub-badge">AUTHENTIC MOTORSPORT LOGISTICS</span>
          <h1 class="page-title">Your Performance Parts Cart</h1>
          <p class="page-sub">Direct air-freight fulfillment and certified garage installation in Cambodia</p>
        </div>
      </div>

      <!-- Not Logged In State (Account Required) -->
      <div v-if="!authStore.isAuthenticated" class="empty-cart-card glass-panel text-center">
        <div class="empty-icon-box">
          <ShoppingBag :size="56" class="text-orange" />
        </div>
        <h2>{{ langStore.t.loginRequiredTitle }}</h2>
        <p>{{ langStore.t.loginRequiredMsg }}</p>
        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap;">
          <router-link to="/auth/login?redirect=/cart" class="btn-browse-catalog" style="background: var(--orange-500);">
            <span>{{ langStore.t.signIn }}</span>
          </router-link>
          <router-link to="/auth/register" class="btn-browse-catalog" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.2);">
            <span>{{ langStore.t.register }}</span>
          </router-link>
        </div>
      </div>

      <!-- Empty Cart State -->
      <div v-else-if="cartStore.items.length === 0" class="empty-cart-card glass-panel">
        <div class="empty-icon-box">
          <ShoppingBag :size="56" class="text-orange" />
        </div>
        <h2>{{ langStore.t.cartEmptyTitle }}</h2>
        <p>{{ langStore.t.cartEmptySub }}</p>
        <router-link to="/shop" class="btn-browse-catalog">
          <ArrowLeft :size="16" />
          <span>{{ langStore.t.browseCatalog }}</span>
        </router-link>
      </div>

      <!-- Cart Content Grid -->
      <div v-else class="cart-grid">
        <!-- Items Table Column -->
        <div class="items-column">
          <div class="cart-items-card glass-panel">
            <div class="items-card-header">
              <span class="header-col">Part Component</span>
              <span class="header-col text-center">Unit Price</span>
              <span class="header-col text-center">Quantity</span>
              <span class="header-col text-right">Total</span>
            </div>

            <div class="items-list">
              <div v-for="item in cartStore.items" :key="item.id" class="cart-item-row">
                <!-- Image & Title -->
                <div class="item-main-info">
                  <div class="item-thumb-box">
                    <img :src="item.thumbnail" :alt="item.name" />
                  </div>
                  <div class="item-text-info">
                    <span class="item-brand">{{ item.brand_name || 'Genuine' }}</span>
                    <router-link :to="`/product/${item.slug}`" class="item-title">
                      {{ item.name }}
                    </router-link>
                    <span class="item-sku">SKU: {{ item.sku }}</span>

                    <!-- Installation Option Badge / Toggle -->
                    <div class="item-install-badge" @click="cartStore.toggleInstallation(item.id)">
                      <Wrench :size="13" :class="item.installation_selected ? 'text-green' : 'text-orange'" />
                      <span>
                        {{ item.installation_selected ? 'Certified Garage Installation Added (+$25)' : '+ Add Garage Installation ($25)' }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Unit Price -->
                <div class="item-unit-price">
                  <strong>${{ Number(item.price).toLocaleString() }}</strong>
                  <span v-if="authStore.isB2B" class="b2b-tier-lbl">B2B Tier</span>
                </div>

                <!-- Qty Picker -->
                <div class="item-qty-cell">
                  <div class="qty-pill">
                    <button class="qty-btn" @click="cartStore.updateQuantity(item.id, item.quantity - 1)">
                      <Minus :size="13" />
                    </button>
                    <span class="qty-num">{{ item.quantity }}</span>
                    <button class="qty-btn" @click="cartStore.updateQuantity(item.id, item.quantity + 1)">
                      <Plus :size="13" />
                    </button>
                  </div>
                  <button class="btn-remove" @click="cartStore.removeItem(item.id)" title="Remove item">
                    <Trash2 :size="14" />
                    <span>Remove</span>
                  </button>
                </div>

                <!-- Total -->
                <div class="item-total-cell">
                  <strong>${{ ((item.price * item.quantity) + (item.installation_selected ? 25 : 0)).toLocaleString() }}</strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Free Shipping & Quality Banner -->
          <div class="air-freight-banner glass-panel">
            <Truck :size="24" class="text-orange" />
            <div>
              <strong>Express Priority Air-Freight Included</strong>
              <p>Direct from European and Japanese factories. Guaranteed 24h delivery within Phnom Penh.</p>
            </div>
          </div>
        </div>

        <!-- Summary Column -->
        <div class="summary-column">
          <div class="summary-card glass-panel">
            <h3 class="summary-title">Order Summary</h3>

            <!-- Promo Input -->
            <div class="promo-box">
              <div class="promo-input-row">
                <input 
                  v-model="promoCode" 
                  type="text" 
                  placeholder="Promo Code (e.g. MOTOCAR2026)"
                  class="promo-input"
                  :disabled="promoApplied"
                />
                <button class="btn-apply-promo" @click="applyPromo" :disabled="promoApplied">
                  {{ promoApplied ? 'Applied' : 'Apply' }}
                </button>
              </div>
              <span v-if="promoApplied" class="promo-success">10% Motorsport VIP discount applied!</span>
              <span v-if="promoError" class="promo-err">{{ promoError }}</span>
            </div>

            <!-- Pricing lines -->
            <div class="pricing-breakdown">
              <div class="price-line">
                <span>Subtotal ({{ cartStore.totalItems }} items)</span>
                <strong>${{ cartStore.subtotal.toLocaleString() }}</strong>
              </div>
              <div class="price-line">
                <span>Air-Freight Delivery</span>
                <span class="text-green">{{ cartStore.shippingFee === 0 ? 'FREE' : `$${cartStore.shippingFee}` }}</span>
              </div>
              <div v-if="promoApplied" class="price-line text-green">
                <span>VIP Discount (10%)</span>
                <strong>-${{ (cartStore.subtotal * 0.1).toFixed(2) }}</strong>
              </div>
              <div class="divider"></div>
              <div class="price-line total-line">
                <span>Total Amount</span>
                <strong class="grand-total">
                  ${{ (promoApplied ? cartStore.totalAmount - (cartStore.subtotal * 0.1) : cartStore.totalAmount).toLocaleString() }}
                </strong>
              </div>
            </div>

            <!-- Checkout Action Button -->
            <button class="btn-checkout" @click="handleProceedCheckout">
              <ShieldCheck :size="18" />
              <span>បន្តទៅកាន់ការទូទាត់ប្រាក់ (Checkout / Payout)</span>
            </button>

            <!-- Mode Notice -->
            <div v-if="authStore.isB2B" class="b2b-notice">
              <Building2 :size="15" class="text-cyan" />
              <span>Wholesale account: Net 30 billing eligible</span>
            </div>

            <div class="security-guarantee">
              <ShieldCheck :size="16" class="text-green" />
              <span>NBC Bakong KHQR Instant & Secure Payment</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- KHQR Checkout Modal -->
    <div v-if="showKhqrModal" class="modal-overlay" @click.self="showKhqrModal = false">
      <div class="khqr-card glass-panel">
        <div class="khqr-head">
          <div class="khqr-logo-box">
            <QrCode :size="18" />
            <strong>BAKONG KHQR</strong>
          </div>
          <button class="btn-close-modal" @click="showKhqrModal = false">&times;</button>
        </div>

        <div class="khqr-qr-box">
          <div class="qr-frame">
            <QrCode :size="180" class="qr-graphic" />
          </div>
          <span class="scan-instructions">Scan with ABA Mobile, ACLEDA, or any Bakong App</span>
        </div>

        <div class="khqr-details">
          <div class="khqr-row">
            <span>Pay To:</span>
            <strong>MOTOCAR CUSTOM TRADING CO., LTD</strong>
          </div>
          <div class="khqr-row">
            <span>Amount:</span>
            <strong class="khqr-amount">${{ (promoApplied ? cartStore.totalAmount - (cartStore.subtotal * 0.1) : cartStore.totalAmount).toLocaleString() }} USD</strong>
          </div>
        </div>

        <button class="btn-confirm-pay" :disabled="isCheckingOut" @click="confirmPayment">
          <CheckCircle2 :size="18" />
          <span>{{ isCheckingOut ? 'Saving Order to Database...' : 'I Have Completed Payment' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cart-page {
  padding: 2rem 0 6rem;
  min-height: 80vh;
}

.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1.25rem;
}

.page-top-bar {
  margin-bottom: 2rem;
}

.sub-badge {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #ff7834;
  display: block;
  margin-bottom: 0.35rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.5rem;
}

.page-sub {
  color: #94a3b8;
  font-size: 0.95rem;
  margin: 0;
}

/* Empty State */
.empty-cart-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 2rem;
  text-align: center;
  border-radius: 16px;
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.empty-icon-box {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: rgba(249, 115, 22, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.empty-cart-card h2 {
  color: #ffffff;
  margin: 0 0 0.5rem;
}

.empty-cart-card p {
  color: #94a3b8;
  margin-bottom: 1.5rem;
}

.btn-browse-catalog {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--orange-500);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 700;
}

/* Cart Grid */
.cart-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 2.5rem;
}

/* Items Card */
.cart-items-card {
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.items-card-header {
  display: grid;
  grid-template-columns: 2.5fr 1fr 1.2fr 1fr;
  padding: 1rem 1.5rem;
  background: rgba(0, 0, 0, 0.25);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 0.8rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.text-center { text-align: center; }
.text-right { text-align: right; }

.items-list {
  display: flex;
  flex-direction: column;
}

.cart-item-row {
  display: grid;
  grid-template-columns: 2.5fr 1fr 1.2fr 1fr;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.cart-item-row:last-child {
  border-bottom: none;
}

.item-main-info {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.item-thumb-box {
  width: 72px;
  height: 72px;
  border-radius: 8px;
  overflow: hidden;
  background: #04070e;
  flex-shrink: 0;
}

.item-thumb-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-text-info {
  display: flex;
  flex-direction: column;
}

.item-brand {
  font-size: 0.7rem;
  font-weight: 700;
  color: #ff7834;
  text-transform: uppercase;
}

.item-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0.15rem 0;
  line-height: 1.35;
  transition: color 0.2s;
}

.item-title:hover {
  color: var(--orange-500);
}

.item-sku {
  font-size: 0.72rem;
  color: #64748b;
  font-family: monospace;
}

.item-install-badge {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.72rem;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-top: 0.4rem;
  cursor: pointer;
  width: fit-content;
}

.item-unit-price {
  text-align: center;
  font-size: 1rem;
  color: #cbd5e1;
}

.b2b-tier-lbl {
  display: block;
  font-size: 0.65rem;
  color: #38bdf8;
  font-weight: 700;
}

.item-qty-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.45rem;
}

.qty-pill {
  display: flex;
  align-items: center;
  background: #04070e;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 6px;
  padding: 0.15rem;
}

.qty-btn {
  width: 26px;
  height: 26px;
  background: transparent;
  border: none;
  color: #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.qty-num {
  width: 28px;
  text-align: center;
  font-weight: 700;
  color: white;
  font-size: 0.85rem;
}

.btn-remove {
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 0.72rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  cursor: pointer;
  transition: color 0.2s;
}

.btn-remove:hover {
  color: #ef4444;
}

.item-total-cell {
  text-align: right;
  font-size: 1.1rem;
  color: #ffffff;
}

.air-freight-banner {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
}

.air-freight-banner strong {
  display: block;
  color: #ffffff;
  font-size: 0.9rem;
}

.air-freight-banner p {
  color: #94a3b8;
  font-size: 0.8rem;
  margin: 0.15rem 0 0;
}

/* Summary Card */
.summary-card {
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 1.75rem;
  position: sticky;
  top: 100px;
}

.summary-title {
  font-size: 1.2rem;
  color: #ffffff;
  margin: 0 0 1.25rem;
  font-weight: 800;
}

.promo-box {
  margin-bottom: 1.5rem;
}

.promo-input-row {
  display: flex;
  gap: 0.5rem;
}

.promo-input {
  flex: 1;
  background: #04070e;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 0.55rem 0.85rem;
  color: white;
  font-size: 0.8rem;
  outline: none;
}

.btn-apply-promo {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  padding: 0.55rem 1rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.8rem;
  cursor: pointer;
}

.promo-success {
  display: block;
  font-size: 0.75rem;
  color: #10b981;
  margin-top: 0.4rem;
}

.promo-err {
  display: block;
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.4rem;
}

.pricing-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  margin-bottom: 1.75rem;
}

.price-line {
  display: flex;
  justify-content: space-between;
  font-size: 0.88rem;
  color: #94a3b8;
}

.price-line strong {
  color: #ffffff;
}

.divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 0.5rem 0;
}

.total-line {
  font-size: 1.1rem;
  font-weight: 800;
  color: #ffffff;
}

.grand-total {
  font-size: 1.5rem;
  color: var(--orange-500);
}

.btn-checkout {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: linear-gradient(135deg, var(--orange-500) 0%, #ff7700 100%);
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(249, 115, 22, 0.35);
  margin-bottom: 1.25rem;
  transition: all 0.25s;
}

.btn-checkout:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(249, 115, 22, 0.5);
}

.b2b-notice {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #38bdf8;
  background: rgba(2, 132, 199, 0.12);
  border: 1px solid rgba(56, 189, 248, 0.3);
  padding: 0.5rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.security-guarantee {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: #64748b;
  justify-content: center;
}

/* KHQR Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
  padding: 1.5rem;
}

.khqr-card {
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 16px;
  width: 100%;
  max-width: 420px;
  padding: 2rem;
  text-align: center;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.8);
}

.khqr-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}

.khqr-logo-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.1rem;
  color: #ffffff;
}

.btn-close-modal {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.5rem;
  cursor: pointer;
}

.khqr-qr-box {
  background: #ffffff;
  padding: 1.5rem;
  border-radius: 14px;
  margin-bottom: 1.5rem;
  color: #000;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.qr-frame {
  margin-bottom: 0.5rem;
}

.scan-instructions {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
}

.khqr-details {
  background: #04070e;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  text-align: left;
}

.khqr-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.82rem;
  color: #94a3b8;
}

.khqr-row strong {
  color: #ffffff;
}

.khqr-amount {
  color: var(--orange-500) !important;
  font-size: 1.1rem;
}

.btn-confirm-pay {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: #10b981;
  color: white;
  border: none;
  padding: 0.85rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-confirm-pay:hover {
  background: #059669;
}

/* =========================================================
   RESPONSIVE DESIGN FOR TABLETS & PHONES
   ========================================================= */
@media (max-width: 1024px) {
  .cart-grid {
    grid-template-columns: 1fr;
  }
  .summary-card {
    position: static;
  }
}

@media (max-width: 768px) {
  .cart-page {
    padding-top: 1rem;
    padding-bottom: 5rem;
  }

  .items-card-header {
    display: none;
  }

  .cart-item-row {
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1.25rem;
  }

  .item-main-info {
    align-items: flex-start;
  }

  .item-unit-price, .item-qty-cell, .item-total-cell {
    text-align: left;
  }

  .item-qty-cell {
    flex-direction: row;
    justify-content: space-between;
    width: 100%;
  }

  .item-total-cell {
    display: flex;
    justify-content: space-between;
    width: 100%;
    padding-top: 0.75rem;
    border-top: 1px dashed rgba(255, 255, 255, 0.08);
  }

  .item-total-cell::before {
    content: "Item Total:";
    font-size: 0.85rem;
    color: #94a3b8;
  }
}
</style>
