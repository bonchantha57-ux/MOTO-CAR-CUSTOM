<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useWishlistStore } from '@/stores/wishlist'
import { useCartStore } from '@/stores/cart'
import { useLanguageStore } from '@/stores/language'
import axios from 'axios'
import {
  Flame,
  SlidersHorizontal,
  Zap,
  Disc,
  CircleDot,
  ShieldCheck,
  CheckCircle2,
  Star,
  ShoppingCart,
  ArrowRight,
  Search,
  Building2,
  Wrench,
  MapPin,
  Calendar,
  TrendingUp,
  FileText,
  Check,
  Car,
  Bike,
  Heart
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const wishlistStore = useWishlistStore()
const cartStore = useCartStore()
const langStore = useLanguageStore()

const handleHomeAddToCart = (product: any) => {
  if (!authStore.isAuthenticated) {
    alert(langStore.t.loginRequiredMsg)
    router.push({ path: '/auth/login', query: { redirect: '/shop' } })
    return
  }
  cartStore.addItem(product, 1, authStore.isB2B)
  router.push('/cart')
}

const vehicleType = ref('all')
import { defaultCategories, defaultProducts } from '@/data/initialData'

const selectedMake = ref('')
const selectedModel = ref('')
const selectedYear = ref('')

const makes = ref(['Honda', 'Ducati', 'Yamaha', 'Kawasaki', 'BMW Motorrad', 'Ford Performance', 'Porsche', 'Toyota GR'])

const categoryMetaMap: Record<string, { icon: any, accent: string }> = {
  'exhaust-systems': { icon: Flame, accent: 'var(--orange-500)' },
  'performance-brakes': { icon: Disc, accent: '#ef4444' },
  'suspension-coilovers': { icon: SlidersHorizontal, accent: '#fbbf24' },
  'forged-wheels': { icon: CircleDot, accent: '#a855f7' },
  'carbon-aerodynamics': { icon: Zap, accent: '#00d2ff' },
}

const categories = ref<any[]>(defaultCategories.map((c) => {
  const meta = categoryMetaMap[c.slug] || { icon: Flame, accent: 'var(--orange-500)' }
  return {
    id: c.id,
    name: c.name,
    slug: c.slug,
    count: c.products_count ?? 1,
    icon: meta.icon,
    accent: meta.accent,
  }
}))
const featuredProducts = ref<any[]>([...defaultProducts])

const loadHomeData = async () => {
  // Instant load from session cache
  try {
    const cachedCat = sessionStorage.getItem('cached_categories')
    const cachedFeatured = sessionStorage.getItem('cached_home_featured')
    if (cachedCat) {
      const parsedCat = JSON.parse(cachedCat)
      categories.value = parsedCat.map((c: any) => {
        const meta = categoryMetaMap[c.slug] || { icon: Flame, accent: 'var(--orange-500)' }
        return {
          id: c.id,
          name: c.name,
          slug: c.slug,
          count: c.products_count ?? 1,
          icon: meta.icon,
          accent: meta.accent,
        }
      })
    }
    if (cachedFeatured) {
      featuredProducts.value = JSON.parse(cachedFeatured)
    }
  } catch (e) {}

  try {
    const [catRes, prodRes] = await Promise.all([
      axios.get('/api/v1/categories'),
      axios.get('/api/v1/products?per_page=8')
    ])

    if (catRes.data && catRes.data.data) {
      categories.value = catRes.data.data.map((c: any) => {
        const meta = categoryMetaMap[c.slug] || { icon: Flame, accent: 'var(--orange-500)' }
        return {
          id: c.id,
          name: c.name,
          slug: c.slug,
          count: c.products_count ?? 1,
          icon: meta.icon,
          accent: meta.accent,
        }
      })
      try {
        sessionStorage.setItem('cached_categories', JSON.stringify(catRes.data.data))
      } catch (e) {}
    }

    if (prodRes.data && prodRes.data.data) {
      featuredProducts.value = prodRes.data.data
      try {
        sessionStorage.setItem('cached_home_featured', JSON.stringify(prodRes.data.data))
      } catch (e) {}
    }
  } catch (err) {
    console.error('Failed to load home page dynamic data:', err)
  }
}

onMounted(() => {
  loadHomeData()
})

const partnerGarages = ref([
  {
    name: 'Apex Speed Garage Phnom Penh',
    location: 'Toul Kork, Phnom Penh',
    specialty: 'Dyno Tuning, Akrapovic Installs & Superbike Track Prep',
    rating: 4.9,
    reviews: 148,
    bays: 6,
    verified: true,
  },
  {
    name: 'R-Performance 4x4 Workshop',
    location: 'Sen Sok, Phnom Penh',
    specialty: 'Raptor Offroad Suspension, Forged Wheels & Carbon Body Kits',
    rating: 4.8,
    reviews: 94,
    bays: 8,
    verified: true,
  },
  {
    name: 'MotoCustom Studio Cambodia',
    location: 'BKK1, Phnom Penh',
    specialty: 'Cafe Racer, Titanium Welds & Brembo Brake Conversions',
    rating: 5.0,
    reviews: 120,
    bays: 4,
    verified: true,
  },
])

const applyFitmentSearch = () => {
  router.push({
    name: 'shop',
    query: {
      type: vehicleType.value !== 'all' ? vehicleType.value : undefined,
      make: selectedMake.value || undefined,
      model: selectedModel.value || undefined,
      year: selectedYear.value || undefined,
    },
  })
}
</script>

<template>
  <div class="home-page">
    <!-- HERO SECTION WITH HIGH-IMPACT 8K AUTOMOTIVE BACKGROUND -->
    <section class="hero-section">
      <div class="hero-bg-media">
        <img src="/images/hero-banner.jpg" alt="MotoCar Custom Showroom" class="hero-img" />
        <div class="hero-gradient-overlay"></div>
      </div>

      <div class="container hero-content">

        <h1 class="hero-main-title">
          {{ langStore.t.heroTitle }}
        </h1>

        <p class="hero-description">
          {{ langStore.t.heroSub }}
        </p>

        <!-- HIGH-TECH VEHICLE FITMENT ENGINE CARD -->
        <div class="fitment-engine-card glass-panel">
          <div class="card-header-bar">
            <div class="header-title-box">
              <ShieldCheck :size="20" class="text-orange" />
              <div>
                <h3>{{ langStore.t.vehicleFitmentBoxTitle }}</h3>
                <p>We automatically filter only parts certified for your exact model year</p>
              </div>
            </div>

            <!-- Vehicle Type Switcher -->
            <div class="type-pill-switch">
              <button 
                :class="{ active: vehicleType === 'all' }" 
                @click="vehicleType = 'all'"
              >
                {{ langStore.t.allVehicles }}
              </button>
              <button 
                :class="{ active: vehicleType === 'motorcycle' }" 
                @click="vehicleType = 'motorcycle'"
              >
                <Bike :size="15" />
                <span>{{ langStore.t.motorcycle }}</span>
              </button>
              <button 
                :class="{ active: vehicleType === 'car' }" 
                @click="vehicleType = 'car'"
              >
                <Car :size="15" />
                <span>{{ langStore.t.car4x4 }}</span>
              </button>
            </div>
          </div>

          <!-- Cascading Selectors -->
          <div class="fitment-grid-inputs">
            <div class="input-group">
              <label>{{ langStore.t.selectBrandMake }}</label>
              <select v-model="selectedMake" class="custom-select">
                <option value="">{{ langStore.t.allBrands }}</option>
                <option v-for="m in makes" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>

            <div class="input-group">
              <label>{{ langStore.t.modelName }}</label>
              <input 
                v-model="selectedModel" 
                type="text" 
                :placeholder="langStore.t.modelPlaceholder" 
                class="custom-input"
              />
            </div>

            <div class="input-group">
              <label>{{ langStore.t.productionYear }}</label>
              <input 
                v-model="selectedYear" 
                type="text" 
                :placeholder="langStore.t.yearPlaceholder" 
                class="custom-input"
              />
            </div>

            <div class="input-group action-group">
              <label>&nbsp;</label>
              <button class="btn-find-parts" @click="applyFitmentSearch">
                <Search :size="16" />
                <span>{{ langStore.t.findCompatibleParts }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Metric Badges -->
        <div class="hero-stats-row">
          <div class="stat-box">
            <strong class="stat-number">5,200+</strong>
            <span class="stat-caption">Verified Genuine Parts</span>
          </div>
          <div class="stat-sep"></div>
          <div class="stat-box">
            <strong class="stat-number">45+</strong>
            <span class="stat-caption">Certified Garages in Cambodia</span>
          </div>
          <div class="stat-sep"></div>
          <div class="stat-box">
            <strong class="stat-number">100%</strong>
            <span class="stat-caption">Direct Importer Guarantee</span>
          </div>
        </div>
      </div>
    </section>

    <!-- CATEGORIES SHOWCASE -->
    <section class="section categories-section">
      <div class="container">
        <div class="section-top-header">
          <div>
            <div class="sub-label">PRECISION PERFORMANCE SELECTION</div>
            <h2 class="title-heading">Explore Custom Tuning Categories</h2>
          </div>
          <router-link to="/shop" class="explore-all-link">
            <span>View All Categories</span>
            <ArrowRight :size="16" />
          </router-link>
        </div>

        <div class="categories-flex-grid">
          <router-link 
            v-for="cat in categories" 
            :key="cat.id" 
            :to="{ name: 'shop', query: { category: cat.slug } }"
            class="cat-tile glass-card"
          >
            <div class="cat-icon-container" :style="{ color: cat.accent, background: `${cat.accent}18` }">
              <component :is="cat.icon" :size="28" />
            </div>
            <h3 class="cat-tile-title">{{ cat.name }}</h3>
            <span class="cat-tile-count">{{ cat.count }} Tested Products</span>
          </router-link>
        </div>
      </div>
    </section>

    <!-- FEATURED PRODUCTS SHOWCASE -->
    <section class="section products-section">
      <div class="container">
        <div class="section-top-header">
          <div>
            <div class="sub-label">TRACK-PROVEN UPGRADES</div>
            <h2 class="title-heading">Top Rated Custom Performance Parts</h2>
          </div>

          <div class="wholesale-indicator-pill" :class="{ 'b2b-active': authStore.isB2B }">
            <Building2 v-if="authStore.isB2B" :size="15" />
            <span v-if="authStore.isB2B">Active View: <strong>B2B Wholesale Tier Pricing</strong></span>
            <span v-else>Active View: <strong>B2C Retail MSRP</strong></span>
          </div>
        </div>

        <div class="products-grid-container">
          <div v-for="product in featuredProducts" :key="product.id" class="product-item-card glass-card">
            <!-- Badge -->
            <div class="card-badge" :class="`badge-${(product.badge || '').toLowerCase().replace(/[^a-z0-9]/g, '-')}`">
              {{ product.badge || (product.is_on_sale ? '🔥 HOT SALE' : (product.is_featured ? 'Best Seller' : 'Genuine Parts')) }}
            </div>

            <!-- Floating Wishlist Heart Button -->
            <button 
              type="button" 
              class="btn-card-wishlist"
              :class="{ active: wishlistStore.isInWishlist(product.id) }"
              @click.stop="wishlistStore.toggleWishlist(product)"
              :title="wishlistStore.isInWishlist(product.id) ? 'Remove from Wishlist' : 'Save to Wishlist'"
            >
              <Heart :size="16" :fill="wishlistStore.isInWishlist(product.id) ? '#ef4444' : 'none'" :class="{ 'text-red': wishlistStore.isInWishlist(product.id) }" />
            </button>

            <!-- Product Image -->
            <router-link :to="`/product/${product.slug}`" class="product-thumb-wrap">
              <img :src="product.thumbnail || product.image" :alt="product.name || product.title" class="product-thumb-img" />
              <div class="fitment-chip">
                <CheckCircle2 :size="13" class="text-green" />
                <span>Fitment Certified</span>
              </div>
            </router-link>

            <!-- Card Body -->
            <div class="card-body-content">
              <div class="vehicle-compatibility">
                <Car :size="13" />
                <span>{{ product.category_name || product.vehicle || 'Fitment Guaranteed' }}</span>
              </div>

              <router-link :to="`/product/${product.slug}`" class="product-name-link">
                {{ product.name || product.title }}
              </router-link>

              <div class="rating-strip">
                <div class="stars">
                  <Star v-for="s in 5" :key="s" :size="13" class="star-filled" />
                </div>
                <span class="rating-score">{{ product.rating_average || product.rating || 5.0 }}</span>
                <span class="rating-count">({{ product.rating_count || product.reviews_count || 24 }} verified reviews)</span>
              </div>

              <!-- Price Box with B2B wholesale calculation -->
              <div class="price-container">
                <template v-if="!authStore.isB2B">
                  <div class="retail-pricing">
                    <span class="price-currency">$</span>
                    <span class="price-value">{{ product.b2c_price || product.price_b2c }}</span>
                    <span class="price-tag">Retail Price</span>
                  </div>
                </template>
                <template v-else>
                  <div class="wholesale-pricing">
                    <div class="wholesale-main">
                      <span class="price-currency">$</span>
                      <span class="price-value text-cyan-glow">{{ product.b2b_price || product.price_b2b }}</span>
                      <span class="moq-tag">MOQ: {{ product.min_order_qty_b2b || product.moq || 1 }} Units</span>
                    </div>
                    <div class="retail-strike">MSRP: ${{ product.b2c_price || product.price_b2c }}</div>
                  </div>
                </template>
              </div>

              <!-- Action Buttons -->
              <div class="card-action-row">
                <router-link :to="`/product/${product.slug}`" class="btn-view-spec">
                  {{ langStore.t.viewSpecs }}
                </router-link>
                <button class="btn-add-cart" title="Add to Shopping Cart" @click="handleHomeAddToCart(product)">
                  <ShoppingCart :size="16" />
                  <span>{{ langStore.t.buy }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CERTIFIED PARTNER GARAGES -->
    <section class="section garages-section">
      <div class="container">
        <div class="section-top-header">
          <div>
            <div class="sub-label">PROFESSIONAL FITTING WORKSHOPS</div>
            <h2 class="title-heading">Partner Garages & Certified Mechanics</h2>
            <p class="section-sub-desc">
              Order your parts online and pick certified installation at partner garages with dyno tuning & warranty guarantee
            </p>
          </div>
          <router-link to="/garages" class="explore-all-link">
            <span>Garages Directory</span>
            <ArrowRight :size="16" />
          </router-link>
        </div>

        <div class="garages-list-grid">
          <div v-for="garage in partnerGarages" :key="garage.name" class="garage-station-card glass-card">
            <div class="station-top">
              <span class="verified-status">
                <ShieldCheck :size="15" />
                Certified Partner Workshop
              </span>
              <div class="station-rating">
                <Star :size="14" class="star-filled" />
                <span>{{ garage.rating }} ({{ garage.reviews }})</span>
              </div>
            </div>

            <h3 class="station-name">{{ garage.name }}</h3>

            <div class="station-meta">
              <MapPin :size="15" class="text-orange" />
              <span>{{ garage.location }}</span>
            </div>

            <div class="station-specs-box">
              <div class="spec-label">
                <Wrench :size="13" />
                <span>Specialization:</span>
              </div>
              <p class="spec-desc">{{ garage.specialty }}</p>
            </div>

            <div class="station-footer-action">
              <router-link to="/garages" class="btn-schedule-slot">
                <Calendar :size="15" />
                <span>Book Fitting Appointment</span>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- B2B WHOLESALE BANNER CALLOUT -->
    <section class="b2b-highlight-banner">
      <div class="container banner-flex-box">
        <div class="banner-info-pane">
          <div class="banner-pill">
            <Building2 :size="14" />
            <span>WHOLESALE & WORKSHOP SUPPLY NETWORK</span>
          </div>
          <h2 class="banner-hero-heading">
            Do You Own a Tuning Garage or Motorcycle Shop in Cambodia?
          </h2>
          <p class="banner-lead-text">
            Join the MotoCar Custom B2B Network to unlock exclusive wholesale volume pricing, 30-day credit lines, consolidated air-freight, and automated customer installation booking referrals.
          </p>

          <div class="feature-bullets-grid">
            <div class="bullet-item">
              <Check :size="16" class="bullet-check" />
              <span>Up to 35% Wholesale Margin Discounts</span>
            </div>
            <div class="bullet-item">
              <Check :size="16" class="bullet-check" />
              <span>Formal RFQ Invoicing & VAT Receipts</span>
            </div>
            <div class="bullet-item">
              <Check :size="16" class="bullet-check" />
              <span>Priority Direct Dyno Technical Support</span>
            </div>
            <div class="bullet-item">
              <Check :size="16" class="bullet-check" />
              <span>Free Installation Customer Referrals</span>
            </div>
          </div>
        </div>

        <div class="banner-cta-pane">
          <router-link to="/auth/register?type=b2b" class="btn-b2b-primary">
            <TrendingUp :size="18" />
            <span>Apply for B2B Account</span>
          </router-link>
          <router-link to="/shop?b2b=true" class="btn-b2b-secondary">
            <FileText :size="18" />
            <span>Browse Wholesale Price Catalog</span>
          </router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.home-page {
  display: flex;
  flex-direction: column;
}

/* HERO SECTION */
.hero-section {
  position: relative;
  min-height: 85vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5rem 1rem 6rem 1rem;
  overflow: hidden;
  width: 100%;
}

.hero-bg-media {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  z-index: 1;
}

.hero-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 30%;
  filter: brightness(0.65) contrast(1.15);
}

.hero-gradient-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: radial-gradient(circle at 50% 30%, rgba(5, 7, 11, 0.4) 0%, rgba(5, 7, 11, 0.96) 90%),
              linear-gradient(180deg, rgba(5, 7, 11, 0.7) 0%, rgba(5, 7, 11, 0.98) 100%);
}

.hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 1100px;
  width: 100%;
  margin-left: auto !important;
  margin-right: auto !important;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.hero-badge-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(249, 115, 22, 0.15);
  border: 1px solid rgba(249, 115, 22, 0.4);
  color: #ff7834;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.35rem 0.9rem;
  border-radius: 9999px;
  margin-bottom: 1.5rem;
  box-shadow: 0 0 20px rgba(249, 115, 22, 0.2);
}

.badge-icon-wrap {
  display: flex;
  color: var(--orange-500);
}

.hero-main-title {
  font-size: 3.5rem;
  font-weight: 900;
  line-height: 1.1;
  letter-spacing: -0.04em;
  color: #ffffff;
  margin-bottom: 1.25rem;
  text-transform: uppercase;
}

.neon-orange {
  color: var(--orange-500);
  text-shadow: 0 0 30px rgba(249, 115, 22, 0.5);
}

.neon-cyan {
  color: #00d2ff;
  text-shadow: 0 0 30px rgba(0, 210, 255, 0.5);
}

.hero-description {
  font-size: 1.15rem;
  color: #cbd5e1;
  max-width: 780px;
  margin: 0 auto 2.5rem auto;
  line-height: 1.6;
}

/* FITMENT ENGINE CARD */
.fitment-engine-card {
  width: 100%;
  max-width: 1050px;
  margin: 0 auto 3rem auto;
  background: rgba(13, 18, 29, 0.88);
  border: 1px solid rgba(249, 115, 22, 0.3);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8), 0 0 30px rgba(249, 115, 22, 0.15);
  border-radius: 14px;
  padding: 1.6rem;
  text-align: left;
}

.card-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 1.25rem;
}

.header-title-box {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-title-box h3 {
  font-size: 1.05rem;
  color: #ffffff;
  font-weight: 700;
  margin: 0;
}

.header-title-box p {
  font-size: 0.8rem;
  color: #94a3b8;
  margin: 0;
}

.type-pill-switch {
  display: flex;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 3px;
  border-radius: 8px;
}

.type-pill-switch button {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 0.82rem;
  font-weight: 700;
  padding: 0.4rem 0.85rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
}

.type-pill-switch button.active {
  background: var(--orange-500);
  color: white;
  box-shadow: 0 2px 10px rgba(249, 115, 22, 0.4);
}

.fitment-grid-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr 0.8fr 1.2fr;
  gap: 1rem;
}

.input-group {
  display: flex;
  flex-direction: column;
}

.input-group label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #cbd5e1;
  margin-bottom: 0.4rem;
}

.custom-select, .custom-input {
  width: 100%;
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #ffffff;
  padding: 0.65rem 0.9rem;
  border-radius: 8px;
  font-size: 0.88rem;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s;
}

.custom-select:focus, .custom-input:focus {
  border-color: var(--orange-500);
}

.btn-find-parts {
  height: 42px;
  background: linear-gradient(135deg, var(--orange-500) 0%, #ea580c 100%);
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 800;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  box-shadow: 0 4px 15px rgba(249, 115, 22, 0.35);
  transition: all 0.2s;
}

.btn-find-parts:hover {
  background: #e04b00;
  transform: translateY(-1px);
}

/* STATS ROW */
.hero-stats-row {
  width: 100%;
  max-width: 850px;
  margin: 0 auto;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 3.5rem;
}

.stat-box {
  display: flex;
  flex-direction: column;
}

.stat-number {
  font-size: 2rem;
  font-weight: 900;
  color: #ffffff;
  font-family: var(--font-display);
}

.stat-caption {
  font-size: 0.82rem;
  color: #94a3b8;
}

.stat-sep {
  width: 1px;
  height: 40px;
  background: rgba(255, 255, 255, 0.12);
}

/* SECTION GLOBAL HEADINGS */
.section {
  padding: 5rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.section-top-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2.75rem;
  flex-wrap: wrap;
  gap: 1.25rem;
}

.sub-label {
  color: #ff7834;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  margin-bottom: 0.35rem;
}

.title-heading {
  font-size: 2.2rem;
  font-weight: 900;
  color: #ffffff;
}

.section-sub-desc {
  color: #94a3b8;
  font-size: 0.95rem;
  margin-top: 0.4rem;
}

.explore-all-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: #00d2ff;
  font-weight: 700;
  font-size: 0.9rem;
  transition: gap 0.2s;
}

.explore-all-link:hover {
  gap: 0.65rem;
  color: var(--orange-500);
}

/* CATEGORIES */
.categories-flex-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.cat-tile {
  padding: 1.75rem 1.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  text-decoration: none;
}

.cat-icon-container {
  width: 64px;
  height: 64px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.2rem;
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.cat-tile:hover .cat-icon-container {
  transform: scale(1.15) rotate(4deg);
}

.cat-tile-title {
  font-size: 1.05rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 0.4rem;
}

.cat-tile-count {
  font-size: 0.8rem;
  color: #64748b;
}

/* PRODUCTS GRID */
.products-grid-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.75rem;
}

.product-item-card {
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.card-badge {
  position: absolute;
  top: 14px;
  left: 14px;
  background: var(--orange-500);
  color: white;
  font-size: 0.7rem;
  font-weight: 800;
  padding: 0.25rem 0.6rem;
  border-radius: 6px;
  z-index: 5;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
}

.card-badge.badge-viral,
.card-badge.badge-viral- {
  background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.5);
}

.card-badge.badge-hot-deal,
.card-badge.badge-hot-sale {
  background: #dc2626;
}

/* Floating Wishlist Button on HomePage */
.btn-card-wishlist {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
  cursor: pointer;
  z-index: 6;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.btn-card-wishlist:hover {
  background: rgba(0, 0, 0, 0.9);
  border-color: #ef4444;
  color: #ef4444;
  transform: scale(1.12);
}

.btn-card-wishlist.active {
  background: rgba(239, 68, 68, 0.2);
  border-color: #ef4444;
  color: #ef4444;
}

.text-red {
  color: #ef4444 !important;
}

.product-thumb-wrap {
  position: relative;
  height: 230px;
  overflow: hidden;
  background: #090e18;
}

.product-thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.product-item-card:hover .product-thumb-img {
  transform: scale(1.08);
}

.fitment-chip {
  position: absolute;
  bottom: 10px;
  left: 10px;
  background: rgba(10, 14, 23, 0.85);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(34, 197, 94, 0.4);
  color: #22c55e;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.card-body-content {
  padding: 1.4rem;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.vehicle-compatibility {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: #00d2ff;
  font-size: 0.78rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.product-name-link {
  color: #ffffff;
  font-size: 1.05rem;
  font-weight: 800;
  line-height: 1.35;
  margin-bottom: 0.6rem;
  transition: color 0.2s;
}

.product-name-link:hover {
  color: var(--orange-500);
}

.rating-strip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 1.25rem;
}

.stars {
  display: flex;
  gap: 2px;
}

.star-filled {
  color: #fbbf24;
  fill: #fbbf24;
}

.rating-score {
  font-weight: 800;
  font-size: 0.85rem;
  color: #f1f5f9;
}

.rating-count {
  font-size: 0.75rem;
  color: #64748b;
}

.price-container {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 1rem;
}

.retail-pricing, .wholesale-main {
  display: flex;
  align-items: baseline;
  gap: 0.2rem;
}

.price-currency {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--orange-500);
}

.price-value {
  font-size: 1.6rem;
  font-weight: 900;
  font-family: var(--font-display);
  color: var(--orange-500);
}

.price-tag {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-left: 0.5rem;
}

.moq-tag {
  background: rgba(0, 210, 255, 0.15);
  border: 1px solid rgba(0, 210, 255, 0.4);
  color: #00d2ff;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-left: auto;
}

.retail-strike {
  font-size: 0.78rem;
  color: #64748b;
  text-decoration: line-through;
  margin-top: 0.2rem;
}

.card-action-row {
  display: flex;
  gap: 0.6rem;
}

.btn-view-spec {
  flex: 1;
  text-align: center;
  background: rgba(255, 255, 255, 0.07);
  color: #cbd5e1;
  border-radius: 8px;
  padding: 0.65rem 0.5rem;
  font-size: 0.84rem;
  font-weight: 700;
  transition: all 0.2s;
}

.btn-view-spec:hover {
  background: rgba(255, 255, 255, 0.12);
  color: white;
}

.btn-add-cart {
  background: var(--orange-500);
  border: none;
  color: white;
  border-radius: 8px;
  padding: 0.65rem 1rem;
  font-weight: 800;
  font-size: 0.84rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
  transition: all 0.2s;
}

.btn-add-cart:hover {
  background: #e04b00;
}

.wholesale-indicator-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.82rem;
  color: #cbd5e1;
}

.wholesale-indicator-pill.b2b-active {
  background: rgba(2, 132, 199, 0.2);
  border-color: #38bdf8;
  color: #38bdf8;
}

/* GARAGES */
.garages-list-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  gap: 1.75rem;
}

.garage-station-card {
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
}

.station-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.85rem;
}

.verified-status {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: #22c55e;
  font-size: 0.75rem;
  font-weight: 700;
}

.station-rating {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.82rem;
  font-weight: 800;
  color: #f1f5f9;
}

.station-name {
  font-size: 1.25rem;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 0.45rem;
}

.station-meta {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.84rem;
  color: #94a3b8;
  margin-bottom: 1.25rem;
}

.station-specs-box {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 0.85rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.spec-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: #ff7834;
  margin-bottom: 0.35rem;
}

.spec-desc {
  font-size: 0.82rem;
  color: #cbd5e1;
  line-height: 1.45;
}

.btn-schedule-slot {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  background: #0284c7;
  color: white;
  padding: 0.75rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.88rem;
  transition: all 0.2s;
}

.btn-schedule-slot:hover {
  background: #0369a1;
}

/* B2B BANNER */
.b2b-highlight-banner {
  background: linear-gradient(135deg, #091a2e 0%, #032b49 100%);
  border-top: 1px solid rgba(0, 210, 255, 0.3);
  border-bottom: 1px solid rgba(0, 210, 255, 0.3);
  padding: 4.5rem 0;
  position: relative;
  overflow: hidden;
}

.banner-flex-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 3.5rem;
  flex-wrap: wrap;
}

.banner-info-pane {
  flex: 1;
  min-width: 320px;
}

.banner-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(0, 210, 255, 0.15);
  border: 1px solid rgba(0, 210, 255, 0.4);
  color: #00d2ff;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  padding: 0.3rem 0.8rem;
  border-radius: 9999px;
  margin-bottom: 1.25rem;
}

.banner-hero-heading {
  font-size: 2.2rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1.2;
  margin-bottom: 1rem;
}

.banner-lead-text {
  color: #e0f2fe;
  font-size: 1.05rem;
  line-height: 1.6;
  margin-bottom: 1.75rem;
}

.feature-bullets-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem 1.5rem;
}

.bullet-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #ffffff;
  font-size: 0.88rem;
  font-weight: 600;
}

.bullet-check {
  color: #00d2ff;
  background: rgba(0, 210, 255, 0.2);
  border-radius: 50%;
  padding: 2px;
  flex-shrink: 0;
}

.banner-cta-pane {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-width: 280px;
}

.btn-b2b-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: var(--orange-500);
  color: white;
  padding: 1rem 1.75rem;
  border-radius: 9px;
  font-size: 0.95rem;
  font-weight: 800;
  box-shadow: 0 6px 20px rgba(249, 115, 22, 0.4);
  transition: all 0.2s;
}

.btn-b2b-primary:hover {
  background: #e04b00;
  transform: translateY(-2px);
}

.btn-b2b-secondary {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  padding: 0.85rem 1.75rem;
  border-radius: 9px;
  font-size: 0.9rem;
  font-weight: 700;
  transition: all 0.2s;
}

.btn-b2b-secondary:hover {
  background: rgba(255, 255, 255, 0.16);
}

@media (max-width: 1024px) {
  .hero-main-title {
    font-size: 2.6rem;
  }
  .fitment-grid-inputs {
    grid-template-columns: 1fr 1fr;
  }
  .feature-bullets-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .hero-main-title {
    font-size: 2rem;
  }
  .fitment-grid-inputs {
    grid-template-columns: 1fr;
  }
  .hero-stats-row {
    flex-direction: column;
    gap: 1.25rem;
  }
  .stat-sep {
    display: none;
  }
}
</style>
