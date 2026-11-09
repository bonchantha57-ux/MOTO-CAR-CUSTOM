<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useWishlistStore } from '@/stores/wishlist'
import { useLanguageStore } from '@/stores/language'
import axios from 'axios'
import {
  Search,
  SlidersHorizontal,
  Flame,
  CheckCircle,
  ShoppingBag,
  Building2,
  Car,
  Filter,
  ArrowUpDown,
  Tag,
  Star,
  Heart
} from 'lucide-vue-next'

import { defaultCategories, defaultBrands, defaultProducts } from '@/data/initialData'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()
const langStore = useLanguageStore()
const addedSuccessId = ref<number | null>(null)

const allProductsPool = ref<any[]>([...defaultProducts])
const products = ref<any[]>([...defaultProducts])
const categories = ref<any[]>([...defaultCategories])
const brands = ref<any[]>([...defaultBrands])
const isLoading = ref(false)

const selectedCategory = ref<string>((route.query.category as string) || (route.params.category as string) || '')
const selectedBrand = ref<string>((route.query.brand as string) || '')
const selectedVehicleType = ref<string>('all')
const searchQuery = ref<string>((route.query.q as string) || '')
const sortBy = ref<string>((route.query.sort as string) || 'latest')

const fetchCategoriesAndBrands = async () => {
  // Instant UI from session cache
  try {
    const cachedCat = sessionStorage.getItem('cached_categories')
    const cachedBrands = sessionStorage.getItem('cached_brands')
    if (cachedCat) {
      const parsedCat = JSON.parse(cachedCat)
      if (Array.isArray(parsedCat) && parsedCat.length > 0 && parsedCat[0].name) {
        categories.value = parsedCat
      }
    }
    if (cachedBrands) {
      const parsedBrands = JSON.parse(cachedBrands)
      if (Array.isArray(parsedBrands) && parsedBrands.length > 0 && parsedBrands[0].name) {
        brands.value = parsedBrands
      }
    }
  } catch (e) {}

  try {
    const [catRes, brandRes] = await Promise.all([
      axios.get('/api/v1/categories'),
      axios.get('/api/v1/brands')
    ])
    if (Array.isArray(catRes.data?.data) && catRes.data.data.length > 0 && catRes.data.data[0].name) {
      categories.value = catRes.data.data
      sessionStorage.setItem('cached_categories', JSON.stringify(categories.value))
    }
    if (Array.isArray(brandRes.data?.data) && brandRes.data.data.length > 0 && brandRes.data.data[0].name) {
      brands.value = brandRes.data.data
      sessionStorage.setItem('cached_brands', JSON.stringify(brands.value))
    }
  } catch (err) {
    console.error('Failed to load categories/brands:', err)
  }
}

const fetchProducts = async () => {
  const cat = selectedCategory.value
  const brand = selectedBrand.value
  const q = searchQuery.value.toLowerCase().trim()
  const sort = sortBy.value

  const cacheKey = `mcc_products_${cat}_${brand}_${sort}_${q}`
  
  // 1. Instant display from session cache or local pool
  try {
    const cached = sessionStorage.getItem(cacheKey)
    if (cached) {
      products.value = JSON.parse(cached)
      isLoading.value = false
    } else {
      // Instant client-side filter
      let filtered = [...allProductsPool.value]
      if (cat) {
        filtered = filtered.filter(p => 
          p.category_slug === cat || 
          (p.category_name && p.category_name.toLowerCase().includes(cat.toLowerCase().replace(/-/g, ' ')))
        )
      }
      if (brand) {
        filtered = filtered.filter(p => 
          p.brand_slug === brand || 
          (p.brand_name && p.brand_name.toLowerCase().includes(brand.toLowerCase()))
        )
      }
      if (q) {
        filtered = filtered.filter(p => 
          (p.name && p.name.toLowerCase().includes(q)) || 
          (p.sku && p.sku.toLowerCase().includes(q))
        )
      }
      if (sort === 'price_asc' || sort === 'price-low') {
        filtered.sort((a, b) => Number(a.b2c_price) - Number(b.b2c_price))
      } else if (sort === 'price_desc' || sort === 'price-high') {
        filtered.sort((a, b) => Number(b.b2c_price) - Number(a.b2c_price))
      }
      products.value = filtered
      isLoading.value = false
    }
  } catch (e) {
    isLoading.value = false
  }

  // 2. Fetch live data from Railway backend API
  try {
    const params: any = {}
    if (cat) params.category = cat
    if (brand) params.brand = brand
    if (q) params.q = q
    if (sort) params.sort = sort

    const res = await axios.get('/api/v1/products', { params })
    const fetched = res.data.data || []
    if (fetched.length > 0 || (cat || brand || q)) {
      products.value = fetched
    }

    // Also update allProductsPool if unfiltered
    if (!cat && !brand && !q && fetched.length > 0) {
      allProductsPool.value = fetched
    }

    try {
      sessionStorage.setItem(cacheKey, JSON.stringify(products.value))
    } catch (e) {}
  } catch (err) {
    console.warn('API sync delayed, using instant filtered catalog:', err)
  } finally {
    isLoading.value = false
  }
}

const selectCategory = (slug: string) => {
  selectedCategory.value = selectedCategory.value === slug ? '' : slug
  router.push({
    name: 'shop',
    query: {
      ...route.query,
      category: selectedCategory.value || undefined
    }
  })
  fetchProducts()
}

const selectBrand = (slug: string) => {
  selectedBrand.value = selectedBrand.value === slug ? '' : slug
  router.push({
    name: 'shop',
    query: {
      ...route.query,
      brand: selectedBrand.value || undefined
    }
  })
  fetchProducts()
}

const getCategoryCount = (slug: string) => {
  if (!slug) return allProductsPool.value.length
  return allProductsPool.value.filter(p => 
    p.category_slug === slug || 
    (p.category_name && p.category_name.toLowerCase().includes(slug.toLowerCase().replace(/-/g, ' ')))
  ).length
}

const getBrandCount = (slug: string) => {
  if (!slug) return allProductsPool.value.length
  return allProductsPool.value.filter(p => 
    p.brand_slug === slug || 
    (p.brand_name && p.brand_name.toLowerCase().includes(slug.toLowerCase()))
  ).length
}

onMounted(() => {
  fetchCategoriesAndBrands()
  fetchProducts()
})

watch(
  () => [route.query.category, route.params.category, route.query.q, route.query.brand, route.query.sort],
  () => {
    selectedCategory.value = (route.query.category as string) || (route.params.category as string) || ''
    selectedBrand.value = (route.query.brand as string) || ''
    searchQuery.value = (route.query.q as string) || ''
    if (route.query.sort) sortBy.value = route.query.sort as string
    fetchProducts()
  }
)


const getCategoryName = (cat: any) => {
  if (!cat) return ''
  const slug = cat.slug || ''
  if (langStore.currentLang === 'km') {
    const kmMap: Record<string, string> = {
      'exhaust-systems': 'បំពង់ស៊ីម៉ាំងប្រណាំង',
      'exhaust': 'បំពង់ស៊ីម៉ាំងប្រណាំង',
      'performance-brakes': 'ប្រព័ន្ធហ្វ្រាំងប្រណាំង',
      'brakes': 'ប្រព័ន្ធហ្វ្រាំងប្រណាំង',
      'suspension-coilovers': 'ប្រព័ន្ធបូម & ជើងក្រោម',
      'suspension': 'ប្រព័ន្ធបូម & ជើងក្រោម',
      'forged-wheels': 'កង់ Forged គុណភាពខ្ពស់',
      'wheels': 'កង់ Forged គុណភាពខ្ពស់',
      'carbon-aerodynamics': 'គ្រឿង Carbon កម្លាំងខ្យល់',
      'carbon': 'គ្រឿង Carbon កម្លាំងខ្យល់',
    }
    if (kmMap[slug]) return kmMap[slug]
    if (cat.name_km) return cat.name_km
  } else if (langStore.currentLang === 'zh') {
    const zhMap: Record<string, string> = {
      'exhaust-systems': '竞技排气系统',
      'exhaust': '竞技排气系统',
      'performance-brakes': '高性能刹车卡钳',
      'brakes': '高性能刹车卡钳',
      'suspension-coilovers': '避震与悬挂系统',
      'suspension': '避震与悬挂系统',
      'forged-wheels': '定制锻造轮毂',
      'wheels': '定制锻造轮毂',
      'carbon-aerodynamics': '碳纤维空气动力学套件',
      'carbon': '碳纤维空气动力学套件',
    }
    if (zhMap[slug]) return zhMap[slug]
    if (cat.name_zh) return cat.name_zh
  }
  return cat.name || cat.slug || ''
}

const getBrandName = (brand: any) => {
  if (!brand) return ''
  return brand.name || brand.slug || ''
}

const handleSearch = () => {
  fetchProducts()
}

const viewProduct = (slug: string) => {
  router.push(`/product/${slug}`)
}

const addToCart = (product: any, e: Event) => {
  e.stopPropagation()
  cartStore.addItem(product, 1, authStore.isB2B)
  addedSuccessId.value = product.id
  setTimeout(() => {
    if (addedSuccessId.value === product.id) {
      addedSuccessId.value = null
    }
  }, 2200)

  if (!authStore.isAuthenticated) {
    router.push({ path: '/auth/login', query: { redirect: '/checkout' } })
    return
  }
}
</script>

<template>
  <div class="shop-catalog-page">
    <div class="container">
      <!-- Breadcrumb & Hero Bar -->
      <div class="shop-top-bar">
        <div>
          <span class="sub-badge">{{ langStore.t.catalogBadge }}</span>
          <h1 class="page-title">{{ langStore.t.catalogTitle }}</h1>
          <p class="page-sub">{{ langStore.t.catalogSub }}</p>
        </div>

        <!-- Mode Indicator Banner -->
        <div class="catalog-mode-badge" :class="authStore.isB2B ? 'mode-b2b' : 'mode-b2c'">
          <Building2 v-if="authStore.isB2B" :size="16" />
          <Flame v-else :size="16" />
          <div>
            <strong>{{ authStore.isB2B ? langStore.t.wholesaleB2B : langStore.t.retailB2C }}</strong>
            <small>{{ authStore.isB2B ? (langStore.currentLang === 'km' ? 'តម្លៃបោះដុំពិសេសសម្រាប់យានដ្ឋាន' : (langStore.currentLang === 'zh' ? '车行专属批发底价已激活' : 'Wholesale Garage Tier Prices Active')) : (langStore.currentLang === 'km' ? 'ការធានាគ្រឿងបន្លាស់សុទ្ធ ១០០%' : (langStore.currentLang === 'zh' ? '100% 正品零售保障' : 'Genuine Retail Guarantee')) }}</small>
          </div>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="shop-toolbar glass-panel">
        <div class="search-box">
          <Search :size="16" class="search-icon" />
          <input 
            v-model="searchQuery" 
            type="text" 
            :placeholder="langStore.t.searchPlaceholder" 
            class="search-input"
            @keyup.enter="handleSearch"
          />
        </div>

        <div class="toolbar-right">
          <div class="sort-selector">
            <ArrowUpDown :size="14" class="text-orange" />
            <select v-model="sortBy" class="sort-select" @change="fetchProducts">
              <option value="latest">{{ langStore.t.sortNewest }}</option>
              <option value="price_asc">{{ langStore.t.sortPriceAsc }}</option>
              <option value="price_desc">{{ langStore.t.sortPriceDesc }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Main Layout: Sidebar & Products Grid -->
      <div class="shop-content-layout">
        <!-- Left Filter Sidebar -->
        <aside class="filter-sidebar glass-panel">
          <!-- Categories Filter -->
          <div class="filter-group">
            <h4 class="filter-title">
              <Filter :size="16" class="text-orange" />
              <span>{{ langStore.t.categories }}</span>
            </h4>
            <div class="filter-options">
              <button 
                class="filter-chip-btn" 
                :class="{ active: selectedCategory === '' }"
                @click="selectCategory('')"
              >
                <div class="filter-chip-left">
                  <span class="cat-dot"></span>
                  <span>{{ langStore.t.allProducts }}</span>
                </div>
                <span class="count-tag">{{ allProductsPool.length }}</span>
              </button>
              <button 
                v-for="cat in categories" 
                :key="cat.id" 
                class="filter-chip-btn"
                :class="{ active: selectedCategory === cat.slug }"
                @click="selectCategory(cat.slug)"
              >
                <div class="filter-chip-left">
                  <span class="cat-dot"></span>
                  <span>{{ getCategoryName(cat) }}</span>
                </div>
                <span class="count-tag">{{ getCategoryCount(cat.slug) || cat.products_count || 1 }}</span>
              </button>
            </div>
          </div>

          <div class="sidebar-divider"></div>

          <!-- Brands Filter -->
          <div class="filter-group">
            <h4 class="filter-title">
              <Tag :size="16" class="text-orange" />
              <span>{{ langStore.t.manufacturers }}</span>
            </h4>
            <div class="filter-options">
              <button 
                class="filter-chip-btn" 
                :class="{ active: selectedBrand === '' }"
                @click="selectBrand('')"
              >
                <div class="filter-chip-left">
                  <span class="cat-dot"></span>
                  <span>{{ langStore.t.allBrands }}</span>
                </div>
                <span class="count-tag">{{ allProductsPool.length }}</span>
              </button>
              <button 
                v-for="brand in brands" 
                :key="brand.id" 
                class="filter-chip-btn"
                :class="{ active: selectedBrand === brand.slug }"
                @click="selectBrand(brand.slug)"
              >
                <div class="filter-chip-left">
                  <span class="cat-dot"></span>
                  <span>{{ getBrandName(brand) }}</span>
                </div>
                <span class="count-tag">{{ getBrandCount(brand.slug) || 1 }}</span>
              </button>
            </div>
          </div>
        </aside>

        <!-- Right: Products Grid -->
        <main class="products-grid-wrapper">
          <div v-if="isLoading && products.length === 0" class="loading-state">
            <div class="spinner"></div>
            <span>{{ langStore.currentLang === 'km' ? 'កំពុងទាញទិន្នន័យគ្រឿងបន្លាស់សុទ្ធ...' : (langStore.currentLang === 'zh' ? '正在加载原厂认证配件...' : 'Fetching certified products...') }}</span>
          </div>

          <div v-else-if="products.length === 0" class="empty-state glass-panel">
            <ShoppingBag :size="48" class="text-orange" />
            <h3>{{ langStore.t.noProductsFound }}</h3>
            <p>{{ langStore.currentLang === 'km' ? 'សូមសាកល្បងសម្អាតតម្រង ឬស្វែងរកដោយពាក្យគន្លឹះផ្សេង។' : (langStore.currentLang === 'zh' ? '请尝试重置分类筛选或输入其他关键词搜索。' : 'Try clearing your category filter or search for another keyword.') }}</p>
            <button class="btn-reset-filter" @click="selectedCategory = ''; selectedBrand = ''; searchQuery = ''; fetchProducts()">
              {{ langStore.t.resetFilters }}
            </button>
          </div>

          <div v-else class="products-cards-grid">
            <div 
              v-for="prod in products" 
              :key="prod.id" 
              class="product-card glass-panel"
              @click="viewProduct(prod.slug)"
            >
              <!-- Image Holder -->
              <div class="card-image-box">
                <img :src="prod.thumbnail" :alt="prod.name" class="card-image" />
                <span class="brand-floating-tag">{{ prod.brand_name || 'Genuine' }}</span>

                <!-- Promotion Badges (Viral, New, Sale) -->
                <span v-if="prod.badge" class="badge-pill" :class="`badge-${prod.badge.toLowerCase().replace(/[^a-z0-9]/g, '-')}`">
                  {{ prod.badge }}
                </span>
                <span v-else-if="prod.compare_at_price && Number(prod.compare_at_price) > Number(prod.b2c_price)" class="badge-pill badge-sale">
                  -{{ Math.round((1 - Number(prod.b2c_price) / Number(prod.compare_at_price)) * 100) }}% OFF
                </span>
                <span v-else-if="prod.is_new_arrival" class="badge-pill badge-new">NEW</span>

                <!-- Wishlist Heart Button -->
                <button 
                  type="button" 
                  class="btn-card-wishlist"
                  :class="{ active: wishlistStore.isInWishlist(prod.id) }"
                  @click.stop="wishlistStore.toggleWishlist(prod)"
                  :title="wishlistStore.isInWishlist(prod.id) ? 'Remove from Wishlist' : 'Save to Wishlist'"
                >
                  <Heart :size="16" :fill="wishlistStore.isInWishlist(prod.id) ? '#ef4444' : 'none'" :class="{ 'text-red': wishlistStore.isInWishlist(prod.id) }" />
                </button>
              </div>

              <!-- Content Body -->
              <div class="card-info">
                <span class="card-category">{{ getCategoryName({ slug: prod.category_slug, name: prod.category_name, name_km: prod.category_name_km, name_zh: prod.category_name_zh }) }}</span>
                <h3 class="card-name">{{ prod.name }}</h3>
                <p class="card-short-desc">{{ prod.short_description }}</p>

                <div class="fitment-assurance">
                  <CheckCircle :size="13" class="text-green" />
                  <span>{{ langStore.t.boltOnFitment }}</span>
                </div>
              </div>

              <!-- Price & Action Footer -->
              <div class="card-price-footer">
                <div class="price-stack">
                  <!-- If in B2B Mode -->
                  <template v-if="authStore.isB2B">
                    <span class="tier-lbl">{{ langStore.t.b2bWholesale }}</span>
                    <strong class="price-val text-cyan">${{ Number(prod.b2b_price || prod.b2c_price * 0.8).toLocaleString() }}</strong>
                    <small class="retail-strike">Retail: ${{ Number(prod.b2c_price).toLocaleString() }}</small>
                  </template>

                  <!-- If in B2C Mode -->
                  <template v-else>
                    <span class="tier-lbl">{{ langStore.t.retailPrice }}</span>
                    <strong class="price-val text-orange">${{ Number(prod.b2c_price).toLocaleString() }}</strong>
                    <small v-if="prod.compare_at_price" class="retail-strike">
                      ${{ Number(prod.compare_at_price).toLocaleString() }}
                    </small>
                  </template>
                </div>

                <button 
                  class="btn-card-cart" 
                  :class="{ 'is-added': addedSuccessId === prod.id }"
                  @click="addToCart(prod, $event)"
                  title="Add to Shopping Cart"
                >
                  <CheckCircle v-if="addedSuccessId === prod.id" :size="15" />
                  <ShoppingBag v-else :size="15" />
                  <span>{{ addedSuccessId === prod.id ? langStore.t.added : langStore.t.buy }}</span>
                </button>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<style scoped>
.shop-catalog-page {
  padding: 2.5rem 0 5rem;
  min-height: 85vh;
}

.container {
  max-width: 1320px;
  margin: 0 auto;
  padding: 0 1.25rem;
}

.shop-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.sub-badge {
  display: inline-block;
  background: rgba(249, 115, 22, 0.12);
  color: #ff7834;
  border: 1px solid rgba(249, 115, 22, 0.3);
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

.catalog-mode-badge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 1.2rem;
  border-radius: 10px;
}

.mode-b2c {
  background: rgba(249, 115, 22, 0.1);
  border: 1px solid rgba(249, 115, 22, 0.3);
  color: #ff7834;
}

.mode-b2b {
  background: rgba(2, 132, 199, 0.15);
  border: 1px solid rgba(56, 189, 248, 0.35);
  color: #38bdf8;
}

.catalog-mode-badge strong {
  display: block;
  font-size: 0.85rem;
}

.catalog-mode-badge small {
  font-size: 0.72rem;
  opacity: 0.85;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

/* Toolbar */
.shop-toolbar {
  padding: 0.85rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.search-box {
  flex: 1;
  max-width: 500px;
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
  border-color: var(--orange-500);
}

.sort-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 0.5rem 0.9rem;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.sort-selector:hover,
.sort-selector:focus-within {
  border-color: var(--orange-500);
  box-shadow: 0 0 14px rgba(249, 115, 22, 0.25);
}

.sort-select {
  background: #0f172a;
  border: none;
  color: #ffffff !important;
  color-scheme: dark;
  font-size: 0.85rem;
  font-weight: 600;
  outline: none;
  cursor: pointer;
}

.sort-select option {
  background-color: #0b1120 !important;
  color: #ffffff !important;
  font-size: 0.88rem;
  padding: 10px 14px;
}

/* Layout Grid */
.shop-content-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 2rem;
}

.filter-sidebar {
  padding: 1.5rem;
  height: fit-content;
}

.filter-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #ffffff;
  font-size: 0.95rem;
  font-weight: 700;
  margin: 0 0 1rem;
}

.sidebar-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.06);
  margin: 1.5rem 0;
}

.filter-options {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.filter-chip-btn {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.09);
  color: #cbd5e1;
  padding: 0.75rem 1rem;
  min-height: 44px;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  text-align: left;
  width: 100%;
}

.filter-chip-left {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cat-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #64748b;
  flex-shrink: 0;
  transition: all 0.2s;
}

.filter-chip-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(249, 115, 22, 0.35);
  color: #ffffff;
  transform: translateX(3px);
}

.filter-chip-btn:hover .cat-dot {
  background: var(--orange-500);
}

.filter-chip-btn.active {
  background: rgba(249, 115, 22, 0.16);
  border-color: var(--orange-500);
  color: #ff7834;
  font-weight: 700;
  box-shadow: 0 0 15px rgba(249, 115, 22, 0.15);
}

.filter-chip-btn.active .cat-dot {
  background: var(--orange-500);
  box-shadow: 0 0 8px var(--orange-500);
}

.count-tag {
  font-size: 0.75rem;
  background: rgba(255, 255, 255, 0.12);
  color: #e2e8f0;
  padding: 0.15rem 0.5rem;
  border-radius: 6px;
  font-weight: 700;
}

/* Product Cards */
.products-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.product-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.25s ease;
  position: relative;
}

.product-card:hover {
  transform: translateY(-5px);
  border-color: rgba(249, 115, 22, 0.4);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.5);
}

.card-image-box {
  width: 100%;
  height: 220px;
  position: relative;
  background: rgba(2, 6, 23, 0.8);
  overflow: hidden;
}

.card-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.product-card:hover .card-image {
  transform: scale(1.05);
}

.brand-floating-tag {
  position: absolute;
  top: 0.75rem;
  left: 0.75rem;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  color: #ffffff;
  font-size: 0.7rem;
  font-weight: 800;
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.new-pill,
.badge-pill {
  position: absolute;
  top: 0.75rem;
  right: 3.25rem;
  color: white;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  letter-spacing: 0.04em;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
  z-index: 2;
}

.badge-new {
  background: var(--orange-500);
}

.badge-viral,
.badge-viral- {
  background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.5);
  animation: pulseGlow 2s infinite ease-in-out;
}

.badge-best-seller,
.badge-best-seller- {
  background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
  color: #000000;
  font-weight: 900;
}

.badge-hot-deal,
.badge-sale {
  background: #dc2626;
}

/* Floating Wishlist Button on Product Cards */
.btn-card-wishlist {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
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
  z-index: 3;
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

.card-info {
  padding: 1.25rem 1.25rem 0.5rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.card-category {
  color: #ff7834;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.card-name {
  color: #ffffff;
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1.35;
  margin: 0 0 0.5rem;
}

.card-short-desc {
  color: #94a3b8;
  font-size: 0.8rem;
  line-height: 1.4;
  margin: 0 0 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.fitment-assurance {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  color: #4ade80;
  font-weight: 600;
  margin-top: auto;
  padding-bottom: 0.75rem;
}

.card-price-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(2, 6, 23, 0.3);
}

.price-stack {
  display: flex;
  flex-direction: column;
}

.tier-lbl {
  font-size: 0.65rem;
  font-weight: 800;
  color: #64748b;
  letter-spacing: 0.05em;
}

.price-val {
  font-size: 1.25rem;
  font-weight: 900;
}

.text-orange { color: var(--orange-500); }
.text-cyan { color: #38bdf8; }
.text-green { color: #4ade80; }

.retail-strike {
  font-size: 0.72rem;
  color: #64748b;
  text-decoration: line-through;
}

.btn-card-cart {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: var(--orange-500);
  color: white;
  border: none;
  padding: 0.55rem 1rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-card-cart:hover {
  background: #ff6a1f;
  transform: translateY(-1px);
}

.btn-card-cart.is-added {
  background: #22c55e !important;
  color: #ffffff;
  box-shadow: 0 0 12px rgba(34, 197, 94, 0.4);
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1rem;
  gap: 1rem;
  color: #94a3b8;
  text-align: center;
}

.btn-reset-filter {
  background: var(--orange-500);
  color: white;
  border: none;
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}

.spinner {
  width: 34px;
  height: 34px;
  border: 3px solid rgba(249, 115, 22, 0.2);
  border-top-color: var(--orange-500);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 900px) {
  .shop-content-layout {
    grid-template-columns: 1fr;
  }
}
</style>
