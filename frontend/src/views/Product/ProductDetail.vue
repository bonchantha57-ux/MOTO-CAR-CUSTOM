<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useWishlistStore } from '@/stores/wishlist'
import {
  ShieldCheck,
  Truck,
  Wrench,
  CheckCircle2,
  Building2,
  Flame,
  Star,
  Plus,
  Minus,
  ShoppingBag,
  ArrowLeft,
  Share2,
  Heart,
  ChevronRight,
  Package,
  Layers,
  Sparkles,
  ExternalLink
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()

const product = ref<any>(null)
const galleryImages = ref<any[]>([])
const activeImage = ref<string>('')
const isLoading = ref<boolean>(true)
const errorMessage = ref<string>('')
const quantity = ref<number>(1)
const addInstallation = ref<boolean>(false)
const selectedGarage = ref<string>('Apex Speed Garage Phnom Penh')
const isAddedSuccess = ref<boolean>(false)
const activeTab = ref<'desc' | 'specs' | 'shipping'>('desc')
const relatedProducts = ref<any[]>([])

// Garages available for installation
const garages = ref<any[]>([
  { id: 1, name: 'Apex Speed Garage Phnom Penh', address: '#12 Toul Kork, Phnom Penh' },
  { id: 2, name: 'Speed Motor Workshop', address: '#44 St 271, Phnom Penh' }
])

const fetchProductData = async () => {
  isLoading.value = true
  errorMessage.value = ''
  const slug = route.params.slug as string

  try {
    const res = await axios.get(`/api/v1/products/${slug}`)
    if (res.data && res.data.product) {
      product.value = res.data.product
      activeImage.value = product.value.thumbnail || 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=800&q=80'
      galleryImages.value = res.data.images || []
      
      // Fetch related products from same category or catalog
      fetchRelatedProducts(product.value.category_id, product.value.id)
    } else {
      errorMessage.value = 'Product details could not be found.'
    }
  } catch (err: any) {
    console.error('Failed to load product:', err)
    errorMessage.value = err.response?.data?.message || 'Product not found or unavailable.'
  } finally {
    isLoading.value = false
  }
}

const fetchRelatedProducts = async (categoryId: number, currentId: number) => {
  try {
    const res = await axios.get('/api/v1/products')
    const all = res.data.data || []
    relatedProducts.value = all
      .filter((p: any) => p.id !== currentId)
      .slice(0, 4)
  } catch (err) {
    console.error('Failed to load related products:', err)
  }
}

watch(() => route.params.slug, () => {
  quantity.value = 1
  addInstallation.value = false
  fetchProductData()
})

onMounted(() => {
  fetchProductData()
})

const effectivePrice = computed(() => {
  if (!product.value) return 0
  if (authStore.isB2B && product.value.b2b_price) {
    return Number(product.value.b2b_price)
  }
  return Number(product.value.b2c_price)
})

const wholesaleSavings = computed(() => {
  if (!product.value || !product.value.b2b_price) return 0
  const retail = Number(product.value.b2c_price)
  const b2b = Number(product.value.b2b_price)
  return Math.max(0, retail - b2b)
})

const incrementQty = () => {
  if (product.value && quantity.value < (product.value.stock_quantity || 99)) {
    quantity.value++
  }
}

const decrementQty = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

const handleAddToCart = () => {
  if (!product.value) return
  if (!authStore.isAuthenticated) {
    router.push({ path: '/auth/login', query: { redirect: route.fullPath } })
    return
  }
  cartStore.addItem(product.value, quantity.value, authStore.isB2B, addInstallation.value)
  isAddedSuccess.value = true
  setTimeout(() => {
    isAddedSuccess.value = false
  }, 2500)
}

const handleBuyNow = () => {
  if (!product.value) return
  if (!authStore.isAuthenticated) {
    router.push({ path: '/auth/login', query: { redirect: route.fullPath } })
    return
  }
  cartStore.addItem(product.value, quantity.value, authStore.isB2B, addInstallation.value)
  router.push('/cart')
}
</script>

<template>
  <div class="product-detail-page">
    <div class="container">
      <!-- Breadcrumb Navigation -->
      <nav class="breadcrumb-nav">
        <router-link to="/" class="bc-link">Home</router-link>
        <ChevronRight :size="13" class="bc-sep" />
        <router-link to="/shop" class="bc-link">Store Catalog</router-link>
        <ChevronRight :size="13" class="bc-sep" />
        <span v-if="product" class="bc-link active-bc">{{ product.category_name || 'Tuning Parts' }}</span>
        <ChevronRight v-if="product" :size="13" class="bc-sep" />
        <span v-if="product" class="bc-current">{{ product.name }}</span>
      </nav>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state glass-panel">
        <div class="spinner"></div>
        <p>Loading authentic part engineering data from database...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="errorMessage || !product" class="error-state glass-panel">
        <Package :size="54" class="text-orange" />
        <h2>Product Not Found</h2>
        <p>{{ errorMessage || 'The requested performance component is not in the active catalog.' }}</p>
        <router-link to="/shop" class="btn-return">
          <ArrowLeft :size="16" />
          <span>Back to Parts Catalog</span>
        </router-link>
      </div>

      <!-- Main Product Content -->
      <div v-else class="product-layout">
        <!-- Left Column: Gallery & Images -->
        <div class="gallery-col">
          <div class="main-preview-card glass-panel">
            <img :src="activeImage" :alt="product.name" class="main-preview-img" />
            
            <div class="floating-badges">
              <span class="badge-genuine">
                <ShieldCheck :size="14" />
                <span>100% GENUINE</span>
              </span>
              <span v-if="product.badge" class="badge-custom-pill" :class="`badge-${product.badge.toLowerCase().replace(/[^a-z0-9]/g, '-')}`">
                {{ product.badge }}
              </span>
              <span v-else-if="product.is_new_arrival" class="badge-new">NEW ARRIVAL</span>
              <span v-if="product.compare_at_price && Number(product.compare_at_price) > Number(product.b2c_price)" class="badge-discount-tag">
                -{{ Math.round((1 - Number(product.b2c_price) / Number(product.compare_at_price)) * 100) }}% OFF
              </span>
            </div>

            <!-- Floating Wishlist Button on Main Preview Image -->
            <button 
              type="button" 
              class="btn-image-wishlist"
              :class="{ active: wishlistStore.isInWishlist(product.id) }"
              @click.stop="wishlistStore.toggleWishlist(product)"
              :title="wishlistStore.isInWishlist(product.id) ? 'Remove from Wishlist' : 'Add to Wishlist'"
            >
              <Heart :size="20" :fill="wishlistStore.isInWishlist(product.id) ? '#ef4444' : 'none'" :class="{ 'text-red': wishlistStore.isInWishlist(product.id) }" />
            </button>
          </div>

          <!-- Thumbnail Selector Strip -->
          <div class="thumbnails-strip" v-if="galleryImages.length > 0 || product.thumbnail">
            <button 
              class="thumb-btn" 
              :class="{ active: activeImage === product.thumbnail }"
              @click="activeImage = product.thumbnail"
            >
              <img :src="product.thumbnail" :alt="product.name" />
            </button>
            <button 
              v-for="img in galleryImages" 
              :key="img.id"
              class="thumb-btn"
              :class="{ active: activeImage === img.image_url }"
              @click="activeImage = img.image_url"
            >
              <img :src="img.image_url" :alt="product.name" />
            </button>
          </div>

          <!-- Genuine Guarantee Box -->
          <div class="assurance-card glass-panel">
            <div class="assurance-item">
              <ShieldCheck :size="20" class="text-green" />
              <div>
                <strong>Factory Certified Authentic</strong>
                <p>Guaranteed authentic with manufacturer serial code & batch seal.</p>
              </div>
            </div>
            <div class="assurance-item">
              <Truck :size="20" class="text-orange" />
              <div>
                <strong>Air-Freight Express from Italy & Japan</strong>
                <p>Direct priority flight shipment. In-stock Phnom Penh delivery within 24h.</p>
              </div>
            </div>
            <div class="assurance-item">
              <Wrench :size="20" class="text-cyan" />
              <div>
                <strong>Direct Certified Installation Option</strong>
                <p>Available at certified partner dyno tuning garages in Phnom Penh.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Specs, Pricing, Actions -->
        <div class="details-col">
          <!-- Header Meta -->
          <div class="part-header-meta">
            <div class="brand-pill">
              <Sparkles :size="13" class="text-orange" />
              <span>{{ product.brand_name || 'MotoCar Custom' }}</span>
            </div>
            <span class="sku-tag">SKU: {{ product.sku || 'MCC-PART-001' }}</span>
            <div class="rating-box">
              <Star :size="14" class="text-yellow fill-yellow" />
              <span>{{ product.rating_average || '5.0' }}</span>
              <small>({{ product.rating_count || 38 }} reviews)</small>
            </div>
          </div>

          <h1 class="product-title">{{ product.name }}</h1>
          <p class="product-short-desc">{{ product.short_description }}</p>

          <!-- Price Display Section -->
          <div class="price-box glass-panel" :class="{ 'price-b2b-active': authStore.isB2B }">
            <div class="price-row">
              <div class="price-numbers">
                <span class="price-currency">$</span>
                <span class="price-amount">{{ effectivePrice.toLocaleString() }}</span>
                <span class="price-usd-label">USD</span>

                <!-- Strikethrough compare at price -->
                <span v-if="product.compare_at_price && !authStore.isB2B" class="compare-price">
                  ${{ Number(product.compare_at_price).toLocaleString() }}
                </span>
              </div>

              <!-- B2B Wholesale Indicator Badge -->
              <div v-if="authStore.isB2B && product.b2b_price" class="b2b-tier-badge">
                <Building2 :size="16" />
                <div>
                  <strong>B2B WHOLESALE TIER</strong>
                  <small>Save ${{ wholesaleSavings }} vs Retail</small>
                </div>
              </div>

              <div v-else-if="product.b2b_price" class="b2b-teaser-pill" @click="router.push('/b2b/register')">
                <Building2 :size="13" />
                <span>Garage Wholesale: ${{ Number(product.b2b_price).toLocaleString() }} (Apply B2B)</span>
              </div>
            </div>

            <!-- Stock status indicator -->
            <div class="stock-status-row">
              <span class="stock-dot" :class="product.stock_quantity > 0 ? 'dot-in-stock' : 'dot-out'"></span>
              <span class="stock-label">
                {{ product.stock_quantity > 0 ? `In Stock: ${product.stock_quantity} Units Ready to Ship` : 'Made-to-Order / In Transit' }}
              </span>
            </div>
          </div>

          <!-- Fitment Assurance Widget -->
          <div class="fitment-engine-box glass-panel">
            <div class="fitment-icon-wrapper">
              <CheckCircle2 :size="20" class="text-green" />
            </div>
            <div class="fitment-text">
              <strong>Certified Direct Bolt-On Fitment</strong>
              <p>100% Guaranteed OEM bolt pattern matching. No chassis or fairing modifications required.</p>
            </div>
          </div>

          <!-- Garage Installation Checkbox Service -->
          <div class="installation-option-card glass-panel" :class="{ selected: addInstallation }">
            <label class="install-check-label">
              <input type="checkbox" v-model="addInstallation" class="custom-checkbox" />
              <div class="install-label-body">
                <div class="install-header">
                  <div class="install-title">
                    <Wrench :size="16" class="text-orange" />
                    <strong>Add Certified Professional Installation (+$25)</strong>
                  </div>
                  <span class="install-chip">PHNOM PENH</span>
                </div>
                <p class="install-desc">
                  Have this component professionally installed, torqued, and safety-inspected by certified partner mechanics.
                </p>
                <div v-if="addInstallation" class="garage-select-wrapper">
                  <span class="garage-select-label">Select Partner Garage:</span>
                  <select v-model="selectedGarage" class="garage-dropdown">
                    <option v-for="g in garages" :key="g.id" :value="g.name">
                      {{ g.name }} ({{ g.address }})
                    </option>
                  </select>
                </div>
              </div>
            </label>
          </div>

          <!-- Quantity & Purchase Buttons -->
          <div class="purchase-actions-box">
            <div class="qty-picker">
              <button class="btn-qty" @click="decrementQty" :disabled="quantity <= 1">
                <Minus :size="15" />
              </button>
              <span class="qty-display">{{ quantity }}</span>
              <button class="btn-qty" @click="incrementQty" :disabled="quantity >= (product.stock_quantity || 99)">
                <Plus :size="15" />
              </button>
            </div>

            <button class="btn-add-cart" @click="handleAddToCart">
              <ShoppingBag :size="18" />
              <span>{{ isAddedSuccess ? '✓ Added to Cart!' : 'Add to Shopping Cart' }}</span>
            </button>

            <button class="btn-buy-now" @click="handleBuyNow">
              <span>Buy Now</span>
            </button>

            <!-- Wishlist Toggle Button -->
            <button 
              type="button" 
              class="btn-detail-wishlist"
              :class="{ active: wishlistStore.isInWishlist(product.id) }"
              @click="wishlistStore.toggleWishlist(product)"
              :title="wishlistStore.isInWishlist(product.id) ? 'Saved in Wishlist' : 'Save to Wishlist'"
            >
              <Heart :size="18" :fill="wishlistStore.isInWishlist(product.id) ? '#ef4444' : 'none'" :class="{ 'text-red': wishlistStore.isInWishlist(product.id) }" />
              <span>{{ wishlistStore.isInWishlist(product.id) ? 'Saved' : 'Wishlist' }}</span>
            </button>
          </div>

          <!-- Added Notification Alert -->
          <transition name="fade">
            <div v-if="isAddedSuccess" class="added-alert-banner">
              <CheckCircle2 :size="18" class="text-green" />
              <span><strong>{{ product.name }}</strong> ({{ quantity }}x) added to your cart!</span>
              <router-link to="/cart" class="link-view-cart">View Cart & Checkout &rarr;</router-link>
            </div>
          </transition>
        </div>
      </div>

      <!-- Informational Tabs Section -->
      <div v-if="product" class="info-tabs-section glass-panel">
        <div class="tabs-header">
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'desc' }"
            @click="activeTab = 'desc'"
          >
            Engineering & Description
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'specs' }"
            @click="activeTab = 'specs'"
          >
            Technical Specifications
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: activeTab === 'shipping' }"
            @click="activeTab = 'shipping'"
          >
            Air-Freight & Installation
          </button>
        </div>

        <div class="tab-content-body">
          <!-- Description Tab -->
          <div v-if="activeTab === 'desc'" class="tab-pane">
            <div class="desc-text-formatted">
              <p v-for="(paragraph, idx) in (product.description || product.short_description || '').split('\n\n')" :key="idx">
                {{ paragraph }}
              </p>
            </div>
          </div>

          <!-- Specs Tab -->
          <div v-else-if="activeTab === 'specs'" class="tab-pane">
            <table class="specs-table">
              <tbody>
                <tr>
                  <td>Brand / Manufacturer</td>
                  <td><strong>{{ product.brand_name || 'MotoCar Custom' }}</strong></td>
                </tr>
                <tr>
                  <td>Category</td>
                  <td>{{ product.category_name || 'Performance Aftermarket' }}</td>
                </tr>
                <tr>
                  <td>Part SKU Code</td>
                  <td><code>{{ product.sku }}</code></td>
                </tr>
                <tr>
                  <td>Fitment Type</td>
                  <td>Direct OEM Replacement / Certified Bolt-On</td>
                </tr>
                <tr>
                  <td>Origin Quality</td>
                  <td>100% Genuine Imported Tier (Italy / Japan / Slovenia)</td>
                </tr>
                <tr>
                  <td>Warranty</td>
                  <td>1 Year Manufacturer Warranty against structural defects</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Shipping Tab -->
          <div v-else-if="activeTab === 'shipping'" class="tab-pane">
            <div class="shipping-info-grid">
              <div class="shipping-point">
                <Truck :size="24" class="text-orange" />
                <h4>Phnom Penh Delivery</h4>
                <p>Within 24 hours via MotoCar Custom VIP Courier. Free for orders over $500.</p>
              </div>
              <div class="shipping-point">
                <Layers :size="24" class="text-cyan" />
                <h4>Provinces Delivery</h4>
                <p>1-2 business days to Siem Reap, Battambang, Sihanoukville, Kampot via express partners with tracking.</p>
              </div>
              <div class="shipping-point">
                <Wrench :size="24" class="text-green" />
                <h4>Partner Garage Fitting</h4>
                <p>Select professional installation at checkout to have parts delivered directly to your selected garage.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Tuning Parts Grid -->
      <div v-if="relatedProducts.length > 0" class="related-products-section">
        <div class="section-top">
          <div>
            <span class="sub-badge">SIMILAR PERFORMANCE UPGRADES</span>
            <h2 class="section-title">Compatible Tuning Parts</h2>
          </div>
          <router-link to="/shop" class="btn-view-all">View Full Catalog &rarr;</router-link>
        </div>

        <div class="related-grid">
          <div 
            v-for="rel in relatedProducts" 
            :key="rel.id" 
            class="rel-card glass-panel"
            @click="router.push(`/product/${rel.slug}`)"
          >
            <div class="rel-img-box">
              <img :src="rel.thumbnail" :alt="rel.name" />
              <span class="rel-brand">{{ rel.brand_name }}</span>
            </div>
            <div class="rel-content">
              <span class="rel-cat">{{ rel.category_name }}</span>
              <h4 class="rel-name">{{ rel.name }}</h4>
              <div class="rel-price-row">
                <strong class="rel-price">${{ Number(authStore.isB2B && rel.b2b_price ? rel.b2b_price : rel.b2c_price).toLocaleString() }}</strong>
                <span class="rel-action">View Part &rarr;</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sticky Mobile Bottom Bar (Optimized for Phone) -->
    <div v-if="product" class="mobile-sticky-bar">
      <div class="mobile-bar-price">
        <span class="mobile-price-lbl">{{ authStore.isB2B ? 'B2B Wholesale' : 'Total Price' }}</span>
        <strong class="mobile-price-num">${{ (effectivePrice * quantity).toLocaleString() }}</strong>
      </div>
      <button class="mobile-add-btn" @click="handleAddToCart">
        <ShoppingBag :size="16" />
        <span>{{ isAddedSuccess ? '✓ Added!' : 'Add to Cart' }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.product-detail-page {
  padding: 1.5rem 0 6rem;
  min-height: 80vh;
}

.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1.25rem;
}

/* Breadcrumb */
.breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  margin-bottom: 2rem;
  color: #64748b;
  flex-wrap: wrap;
}

.bc-link {
  color: #94a3b8;
  transition: color 0.2s;
}

.bc-link:hover {
  color: #ff5500;
}

.active-bc {
  color: #cbd5e1;
}

.bc-sep {
  color: #475569;
}

.bc-current {
  color: #ffffff;
  font-weight: 600;
  max-width: 320px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Loading & Error */
.loading-state, .error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  gap: 1.25rem;
  border-radius: 14px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(255, 85, 0, 0.2);
  border-top-color: #ff5500;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-return {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 700;
  margin-top: 1rem;
}

/* Product Main Layout */
.product-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  margin-bottom: 3.5rem;
}

/* Left Column */
.gallery-col {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.main-preview-card {
  position: relative;
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  overflow: hidden;
  aspect-ratio: 4 / 3;
  display: flex;
  align-items: center;
  justify-content: center;
}

.main-preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.main-preview-card:hover .main-preview-img {
  transform: scale(1.04);
}

.floating-badges {
  position: absolute;
  top: 1rem;
  left: 1rem;
  display: flex;
  gap: 0.5rem;
}

.badge-genuine {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(16, 185, 129, 0.9);
  color: white;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.3rem 0.65rem;
  border-radius: 6px;
  letter-spacing: 0.05em;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
}

.badge-new {
  background: #ff5500;
  color: white;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.3rem 0.65rem;
  border-radius: 6px;
}

.thumbnails-strip {
  display: flex;
  gap: 0.75rem;
  overflow-x: auto;
  padding-bottom: 0.5rem;
}

.thumb-btn {
  width: 72px;
  height: 72px;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.1);
  background: #090e18;
  cursor: pointer;
  padding: 0;
  flex-shrink: 0;
  transition: all 0.2s;
}

.thumb-btn.active {
  border-color: #ff5500;
  box-shadow: 0 0 12px rgba(255, 85, 0, 0.4);
}

.thumb-btn img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.assurance-card {
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.assurance-item {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
}

.assurance-item strong {
  display: block;
  font-size: 0.85rem;
  color: #f1f5f9;
}

.assurance-item p {
  font-size: 0.78rem;
  color: #94a3b8;
  margin: 0.15rem 0 0;
}

/* Right Column */
.details-col {
  display: flex;
  flex-direction: column;
}

.part-header-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

.brand-pill {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(255, 85, 0, 0.15);
  border: 1px solid rgba(255, 85, 0, 0.3);
  color: #ff7834;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
}

.sku-tag {
  font-family: monospace;
  font-size: 0.78rem;
  color: #64748b;
  background: rgba(255, 255, 255, 0.05);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.rating-box {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.82rem;
  color: #f8fafc;
  font-weight: 600;
}

.fill-yellow {
  fill: #eab308;
  color: #eab308;
}

.rating-box small {
  color: #64748b;
}

.product-title {
  font-family: var(--font-display, sans-serif);
  font-size: 2rem;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.25;
  margin: 0 0 0.85rem;
  letter-spacing: -0.02em;
}

.product-short-desc {
  font-size: 0.95rem;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

/* Price Box */
.price-box {
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 14px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  transition: all 0.3s;
}

.price-box.price-b2b-active {
  background: linear-gradient(135deg, rgba(2, 132, 199, 0.12) 0%, #090e18 100%);
  border-color: rgba(56, 189, 248, 0.4);
  box-shadow: 0 0 20px rgba(2, 132, 199, 0.15);
}

.price-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 0.75rem;
}

.price-numbers {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.price-currency {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ff5500;
}

.price-amount {
  font-size: 2.5rem;
  font-weight: 900;
  color: #ffffff;
  letter-spacing: -0.03em;
}

.price-usd-label {
  font-size: 0.85rem;
  color: #64748b;
  font-weight: 600;
  margin-left: 0.25rem;
}

.compare-price {
  font-size: 1.2rem;
  color: #64748b;
  text-decoration: line-through;
  margin-left: 0.75rem;
}

.b2b-tier-badge {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(2, 132, 199, 0.2);
  border: 1px solid rgba(56, 189, 248, 0.5);
  color: #38bdf8;
  padding: 0.4rem 0.85rem;
  border-radius: 8px;
}

.b2b-tier-badge strong {
  display: block;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.06em;
}

.b2b-tier-badge small {
  font-size: 0.7rem;
  color: #7dd3fc;
}

.b2b-teaser-pill {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(2, 132, 199, 0.12);
  border: 1px dashed rgba(56, 189, 248, 0.4);
  color: #38bdf8;
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.b2b-teaser-pill:hover {
  background: rgba(2, 132, 199, 0.25);
  border-style: solid;
}

.stock-status-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
}

.stock-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.dot-in-stock {
  background: #10b981;
  box-shadow: 0 0 10px #10b981;
}

.dot-out {
  background: #f59e0b;
}

.stock-label {
  color: #cbd5e1;
  font-weight: 600;
}

/* Fitment engine box */
.fitment-engine-box {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.5rem;
}

.fitment-text strong {
  display: block;
  font-size: 0.85rem;
  color: #10b981;
}

.fitment-text p {
  font-size: 0.78rem;
  color: #94a3b8;
  margin: 0.15rem 0 0;
}

/* Installation option */
.installation-option-card {
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1.75rem;
  transition: all 0.2s;
}

.installation-option-card.selected {
  border-color: #ff5500;
  background: rgba(255, 85, 0, 0.05);
  box-shadow: 0 0 15px rgba(255, 85, 0, 0.15);
}

.install-check-label {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  cursor: pointer;
  user-select: none;
}

.custom-checkbox {
  width: 18px;
  height: 18px;
  accent-color: #ff5500;
  margin-top: 0.2rem;
  cursor: pointer;
}

.install-label-body {
  flex: 1;
}

.install-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.35rem;
}

.install-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.88rem;
  color: #ffffff;
}

.install-chip {
  font-size: 0.65rem;
  font-weight: 800;
  background: rgba(255, 85, 0, 0.2);
  color: #ff7834;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.install-desc {
  font-size: 0.78rem;
  color: #94a3b8;
  margin: 0;
  line-height: 1.4;
}

.garage-select-wrapper {
  margin-top: 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.garage-select-label {
  font-size: 0.75rem;
  color: #ff7834;
  font-weight: 600;
}

.garage-dropdown {
  background: #04070e;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 0.55rem 0.85rem;
  color: #ffffff;
  font-size: 0.82rem;
  outline: none;
}

/* Purchase Actions */
.purchase-actions-box {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1.25rem;
}

.qty-picker {
  display: flex;
  align-items: center;
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  padding: 0.25rem;
}

.btn-qty {
  width: 36px;
  height: 36px;
  background: transparent;
  border: none;
  color: #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-qty:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.btn-qty:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.qty-display {
  width: 36px;
  text-align: center;
  font-weight: 800;
  color: white;
  font-size: 0.95rem;
}

.btn-add-cart {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: linear-gradient(135deg, #ff5500 0%, #ff7700 100%);
  color: white;
  border: none;
  padding: 0.85rem 1.5rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.92rem;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(255, 85, 0, 0.35);
  transition: all 0.25s;
}

.btn-add-cart:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(255, 85, 0, 0.5);
}

.btn-buy-now {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ffffff;
  padding: 0.85rem 1.75rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.92rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-buy-now:hover {
  background: white;
  color: black;
}

/* Detail & Floating Image Wishlist Buttons */
.btn-detail-wishlist {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #cbd5e1;
  padding: 0.85rem 1.25rem;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-detail-wishlist:hover {
  background: rgba(239, 68, 68, 0.15);
  border-color: #ef4444;
  color: #ef4444;
}

.btn-detail-wishlist.active {
  background: rgba(239, 68, 68, 0.2);
  border-color: #ef4444;
  color: #ef4444;
}

.btn-image-wishlist {
  position: absolute;
  top: 1.25rem;
  right: 1.25rem;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
  cursor: pointer;
  z-index: 10;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-image-wishlist:hover {
  background: rgba(0, 0, 0, 0.9);
  border-color: #ef4444;
  color: #ef4444;
  transform: scale(1.12);
}

.btn-image-wishlist.active {
  background: rgba(239, 68, 68, 0.2);
  border-color: #ef4444;
  color: #ef4444;
}

.badge-custom-pill,
.badge-discount-tag {
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  color: white;
  letter-spacing: 0.04em;
}

.badge-discount-tag {
  background: #dc2626;
  box-shadow: 0 0 10px rgba(220, 38, 38, 0.4);
}

.badge-viral,
.badge-viral- {
  background: linear-gradient(135deg, #ef4444, #f97316);
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.5);
}

.badge-best-seller,
.badge-best-seller- {
  background: linear-gradient(135deg, #eab308, #ca8a04);
  color: #000;
}

.text-red {
  color: #ef4444 !important;
}

.added-alert-banner {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.4);
  padding: 0.85rem 1.25rem;
  border-radius: 10px;
  color: #f1f5f9;
  font-size: 0.85rem;
  margin-top: 0.5rem;
}

.link-view-cart {
  margin-left: auto;
  color: #10b981;
  font-weight: 700;
  text-decoration: underline;
}

/* Informational Tabs */
.info-tabs-section {
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  overflow: hidden;
  margin-bottom: 4rem;
}

.tabs-header {
  display: flex;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(0, 0, 0, 0.2);
  overflow-x: auto;
}

.tab-btn {
  padding: 1.1rem 1.75rem;
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  color: #94a3b8;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.tab-btn.active {
  color: #ff5500;
  border-bottom-color: #ff5500;
  background: rgba(255, 85, 0, 0.05);
}

.tab-content-body {
  padding: 2rem;
}

.desc-text-formatted p {
  color: #cbd5e1;
  font-size: 0.95rem;
  line-height: 1.75;
  margin-bottom: 1.25rem;
}

.specs-table {
  width: 100%;
  border-collapse: collapse;
}

.specs-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 0.9rem;
  color: #cbd5e1;
}

.specs-table td:first-child {
  width: 30%;
  color: #94a3b8;
  font-weight: 600;
}

.shipping-info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}

.shipping-point h4 {
  color: #ffffff;
  font-size: 1rem;
  margin: 0.85rem 0 0.4rem;
}

.shipping-point p {
  color: #94a3b8;
  font-size: 0.85rem;
  line-height: 1.5;
  margin: 0;
}

/* Related section */
.related-products-section {
  margin-bottom: 3rem;
}

.section-top {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 1.75rem;
}

.sub-badge {
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  color: #ff7834;
  margin-bottom: 0.35rem;
  display: block;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}

.btn-view-all {
  color: #ff5500;
  font-size: 0.88rem;
  font-weight: 700;
  transition: color 0.2s;
}

.related-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
}

.rel-card {
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s;
}

.rel-card:hover {
  transform: translateY(-4px);
  border-color: rgba(255, 85, 0, 0.4);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
}

.rel-img-box {
  position: relative;
  aspect-ratio: 16 / 10;
  overflow: hidden;
}

.rel-img-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.rel-brand {
  position: absolute;
  bottom: 0.5rem;
  left: 0.5rem;
  font-size: 0.65rem;
  font-weight: 800;
  background: rgba(0, 0, 0, 0.75);
  color: white;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
}

.rel-content {
  padding: 1rem;
}

.rel-cat {
  font-size: 0.7rem;
  color: #ff7834;
  text-transform: uppercase;
  font-weight: 700;
}

.rel-name {
  font-size: 0.88rem;
  color: #ffffff;
  margin: 0.35rem 0 0.75rem;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.rel-price-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.rel-price {
  font-size: 1.05rem;
  font-weight: 800;
  color: #ffffff;
}

.rel-action {
  font-size: 0.75rem;
  color: #38bdf8;
  font-weight: 700;
}

/* Mobile Sticky Bar - Hidden on Desktop */
.mobile-sticky-bar {
  display: none;
}

/* =========================================================
   RESPONSIVE DESIGN FOR TABLETS & PHONES
   ========================================================= */
@media (max-width: 1024px) {
  .product-layout {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  .related-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .shipping-info-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }
}

@media (max-width: 768px) {
  .product-detail-page {
    padding-top: 1rem;
    padding-bottom: 7rem; /* Space for mobile bottom bar */
  }

  .product-title {
    font-size: 1.5rem;
  }

  .price-amount {
    font-size: 2rem;
  }

  .purchase-actions-box {
    flex-wrap: wrap;
  }

  .qty-picker {
    width: 100%;
    justify-content: space-between;
  }

  .btn-add-cart, .btn-buy-now {
    width: 100%;
  }

  .related-grid {
    grid-template-columns: 1fr;
  }

  /* Fixed Bottom Bar on Phone */
  .mobile-sticky-bar {
    position: fixed;
    bottom: 58px; /* sits right above bottom mobile nav */
    left: 0;
    right: 0;
    background: rgba(10, 14, 23, 0.95);
    backdrop-filter: blur(15px);
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 90;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.6);
  }

  .mobile-bar-price {
    display: flex;
    flex-direction: column;
  }

  .mobile-price-lbl {
    font-size: 0.65rem;
    color: #94a3b8;
  }

  .mobile-price-num {
    font-size: 1.25rem;
    font-weight: 800;
    color: #ff5500;
  }

  .mobile-add-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #ff5500;
    color: white;
    border: none;
    padding: 0.65rem 1.25rem;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.85rem;
  }
}
</style>
