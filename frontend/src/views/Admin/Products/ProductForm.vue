<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import {
  Upload,
  Image as ImageIcon,
  CheckCircle2,
  AlertCircle,
  Plus,
  Trash2,
  ArrowLeft,
  Eye,
  Sparkles,
  DollarSign,
  Package,
  Layers,
  Tag,
  Flame,
  Zap,
  Award
} from 'lucide-vue-next'
import { formatImageUrl, handleProductImageError, DEFAULT_PRODUCT_THUMB } from '@/utils/image'
import { useRoute } from 'vue-router'
import { useLanguageStore } from '@/stores/language'

const router = useRouter()
const route = useRoute()
const langStore = useLanguageStore()

const isEditMode = ref(false)
const productId = ref<any>(null)

const form = ref({
  name: '',
  sku: '',
  category_id: '' as string | number,
  brand_id: '' as string | number,
  b2c_price: '' as string | number,
  compare_at_price: '' as string | number,
  b2b_price: '' as string | number,
  stock_quantity: 10,
  vehicle_type: 'motorcycle',
  short_description: '',
  description: '',
  thumbnail: '',
  badge: '',
  is_on_sale: false,
  is_new_arrival: false,
  is_featured: false,
})

const categories = ref<any[]>([])
const brands = ref<any[]>([])

const imagePreview = ref<string>('')
const selectedFile = ref<File | null>(null)
const isUploadingImage = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const createdSlug = ref('')

const fileInputRef = ref<HTMLInputElement | null>(null)

// Discount calculations
const discountPercent = computed(() => {
  const orig = Number(form.value.compare_at_price)
  const current = Number(form.value.b2c_price)
  if (orig && current && orig > current) {
    return Math.round(((orig - current) / orig) * 100)
  }
  return 0
})

const discountSavings = computed(() => {
  const orig = Number(form.value.compare_at_price)
  const current = Number(form.value.b2c_price)
  if (orig && current && orig > current) {
    return (orig - current).toLocaleString()
  }
  return '0'
})

const badgePresets = [
  { label: '🔥 VIRAL', value: 'VIRAL', desc: 'Trending / Popular' },
  { label: '✨ NEW', value: 'NEW', desc: 'New Arrival' },
  { label: '🏆 BEST SELLER', value: 'BEST SELLER', desc: 'Top Sales' },
  { label: '⚡ HOT DEAL', value: 'HOT DEAL', desc: 'Special Discount' },
]

const selectBadge = (val: string) => {
  if (form.value.badge === val) {
    form.value.badge = ''
  } else {
    form.value.badge = val
    if (val === 'NEW') form.value.is_new_arrival = true
    if (val === 'HOT DEAL') form.value.is_on_sale = true
  }
}

onMounted(async () => {
  try {
    const [catRes, brandRes] = await Promise.all([
      axios.get('/api/v1/categories'),
      axios.get('/api/v1/brands')
    ])
    categories.value = catRes.data.data || []
    brands.value = brandRes.data.data || []

    if (categories.value.length > 0) {
      form.value.category_id = categories.value[0].id
    }
    if (brands.value.length > 0) {
      form.value.brand_id = brands.value[0].id
    }

    // Check if Edit Mode
    if (route.params.id) {
      isEditMode.value = true
      productId.value = route.params.id
      const pRes = await axios.get(`/api/v1/admin/products/${productId.value}`)
      const p = pRes.data.data
      if (p) {
        form.value = {
          name: p.name,
          sku: p.sku,
          category_id: p.category_id,
          brand_id: p.brand_id,
          b2c_price: p.b2c_price,
          compare_at_price: p.compare_at_price || '',
          b2b_price: p.b2b_price,
          stock_quantity: p.stock_quantity,
          vehicle_type: p.vehicle_type || 'motorcycle',
          short_description: p.short_description || '',
          description: p.description || '',
          thumbnail: p.thumbnail || '',
          badge: p.badge || '',
          is_on_sale: Boolean(p.is_on_sale),
          is_new_arrival: Boolean(p.is_new_arrival),
          is_featured: Boolean(p.is_featured),
        }
        imagePreview.value = p.thumbnail || ''
      }
    }
  } catch (err) {
    console.warn('Could not fetch categories/brands/product:', err)
  }
})

// Auto compute B2B Wholesale discount when retail price changes
const handlePriceChange = () => {
  const price = Number(form.value.b2c_price)
  if (price && !form.value.b2b_price) {
    form.value.b2b_price = Math.round(price * 0.8) // 20% discount for garages
  }
  if (Number(form.value.compare_at_price) > price) {
    form.value.is_on_sale = true
  }
}

// Handle file selection from computer / phone
const handleFileChange = async (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    const file = target.files[0]
    selectedFile.value = file

    // Instant local preview
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)

    // Upload to server
    await uploadFileToServer(file)
  }
}

const triggerFileInput = () => {
  fileInputRef.value?.click()
}

const uploadFileToServer = async (file: File) => {
  isUploadingImage.value = true
  errorMessage.value = ''
  try {
    const formData = new FormData()
    formData.append('image', file)

    const res = await axios.post('/api/v1/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (res.data.url) {
      const formatted = formatImageUrl(res.data.url)
      form.value.thumbnail = formatted
      imagePreview.value = formatted
    }
  } catch (err: any) {
    console.error('Image upload failed:', err)
    errorMessage.value = err.response?.data?.message || 'Failed to upload image file to server.'
  } finally {
    isUploadingImage.value = false
  }
}

const removeImage = () => {
  imagePreview.value = ''
  form.value.thumbnail = ''
  selectedFile.value = null
  if (fileInputRef.value) {
    fileInputRef.value.value = ''
  }
}

const handleSubmit = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!form.value.name || !form.value.b2c_price || !form.value.category_id) {
    errorMessage.value = 'Please provide product name, category, and retail price.'
    return
  }

  isSubmitting.value = true
  try {
    const payload = {
      name: form.value.name,
      sku: form.value.sku || undefined,
      category_id: form.value.category_id,
      brand_id: form.value.brand_id || null,
      b2c_price: Number(form.value.b2c_price),
      compare_at_price: form.value.compare_at_price ? Number(form.value.compare_at_price) : null,
      b2b_price: form.value.b2b_price ? Number(form.value.b2b_price) : undefined,
      badge: form.value.badge || null,
      is_on_sale: form.value.is_on_sale ? 1 : 0,
      is_new_arrival: form.value.is_new_arrival ? 1 : 0,
      is_featured: form.value.is_featured ? 1 : 0,
      stock_quantity: Number(form.value.stock_quantity),
      vehicle_type: form.value.vehicle_type,
      short_description: form.value.short_description,
      description: form.value.description,
      thumbnail: form.value.thumbnail || imagePreview.value || undefined,
    }

    if (isEditMode.value) {
      await axios.put(`/api/v1/admin/products/${productId.value}`, payload)
      successMessage.value = `Product "${form.value.name}" updated successfully in database!`
    } else {
      const res = await axios.post('/api/v1/admin/products', payload)
      successMessage.value = 'Product uploaded & published to store successfully! Customers can now see it!'
      createdSlug.value = res.data.slug || ''
      // Reset fields
      form.value.name = ''
      form.value.sku = ''
      form.value.b2c_price = ''
      form.value.compare_at_price = ''
      form.value.b2b_price = ''
      form.value.badge = ''
      form.value.is_on_sale = false
      form.value.is_new_arrival = false
      form.value.is_featured = false
      form.value.short_description = ''
      form.value.description = ''
      removeImage()
    }
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || err.message || 'Failed to save product.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="product-form-page">
    <div class="admin-page-container">
      <!-- Top Bar -->
      <div class="top-nav-row">
        <button class="back-link" @click="router.push('/admin/products')">
          <ArrowLeft :size="16" />
          <span>{{ langStore.t.admin?.backToProducts || 'Back to Product List' }}</span>
        </button>
        <span class="badge-tag">{{ isEditMode ? (langStore.t.admin?.editingProduct || 'EDITING PRODUCT') : (langStore.t.admin?.adminCatalogManager || 'ADMIN CATALOG MANAGER') }}</span>
      </div>

      <div class="page-title-row">
        <div>
          <h1 class="main-title">{{ isEditMode ? (langStore.t.admin?.editPart || 'Edit Performance Part') : (langStore.t.admin?.addNewPart || 'Add New Performance Part') }}</h1>
          <p class="subtitle">{{ isEditMode ? (langStore.t.admin?.editPartSubtitle || 'Modify pricing, stock, technical specs, and photos directly in database') : (langStore.t.admin?.addPartSubtitle || 'Upload high-res images, configure fitment, retail pricing, and B2B wholesale tiers without code') }}</p>
        </div>
      </div>

      <!-- Alerts -->
      <div v-if="successMessage" class="alert-box success-box">
        <div class="alert-left">
          <CheckCircle2 :size="20" />
          <div>
            <strong>{{ successMessage }}</strong>
            <p>{{ langStore.t.admin?.liveSuccess || 'Your product is live on the store right now!' }}</p>
          </div>
        </div>
        <div class="alert-actions">
          <router-link to="/shop" class="btn-live-view">
            <Eye :size="14" />
            <span>{{ langStore.t.admin?.viewLive || 'View Live in Store' }}</span>
          </router-link>
        </div>
      </div>

      <div v-if="errorMessage" class="alert-box error-box">
        <AlertCircle :size="18" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Main Form Grid -->
      <form @submit.prevent="handleSubmit" class="form-layout-grid">
        <!-- Left Column: Details -->
        <div class="details-column">
          <!-- General Info Card -->
          <div class="form-card glass-panel">
            <h3 class="card-title">
              <Package :size="18" class="text-orange" />
              <span>{{ langStore.t.admin?.generalInfo || 'General Information' }}</span>
            </h3>

            <div class="form-group">
              <label class="lbl">{{ langStore.t.admin?.productTitle || 'Product Title / Part Name *' }}</label>
              <input 
                v-model="form.name" 
                type="text" 
                :placeholder="langStore.t.admin?.titlePlaceholder || 'e.g. Yoshimura R-77 Carbon Fiber Full Exhaust System'" 
                class="custom-input"
                required
              />
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.partSku || 'Part SKU / Code' }}</label>
                <input 
                  v-model="form.sku" 
                  type="text" 
                  :placeholder="langStore.t.admin?.skuPlaceholder || 'e.g. YOS-R77-CB650 (Auto if blank)'" 
                  class="custom-input"
                />
              </div>

              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.vehicleType || 'Vehicle Type *' }}</label>
                <select v-model="form.vehicle_type" class="custom-select">
                  <option value="motorcycle">{{ langStore.t.admin?.motorcycleOption || '🏍️ Motorcycle / Superbike' }}</option>
                  <option value="car">{{ langStore.t.admin?.carOption || '🚗 Performance Car / Offroad' }}</option>
                  <option value="both">{{ langStore.t.admin?.universalOption || '⚡ Universal Fitment' }}</option>
                </select>
              </div>
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.category || 'Category *' }}</label>
                <select v-model="form.category_id" class="custom-select" required>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.brand || 'Brand / Manufacturer *' }}</label>
                <select v-model="form.brand_id" class="custom-select">
                  <option v-for="b in brands" :key="b.id" :value="b.id">
                    {{ b.name }} ({{ b.country }})
                  </option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="lbl">{{ langStore.t.admin?.shortSummary || 'Short Summary (Highlights)' }}</label>
              <input 
                v-model="form.short_description" 
                type="text" 
                :placeholder="langStore.t.admin?.summaryPlaceholder || 'e.g. Lightweight titanium body, carbon fiber end-cap, +7.5 HP dyno tested'" 
                class="custom-input"
              />
            </div>

            <div class="form-group">
              <label class="lbl">{{ langStore.t.admin?.detailedSpecs || 'Detailed Product Specifications' }}</label>
              <textarea 
                v-model="form.description" 
                rows="4" 
                :placeholder="langStore.t.admin?.specsPlaceholder || 'Describe material, dyno results, certified installation notes, sound db level...'"
                class="custom-textarea"
              ></textarea>
            </div>
          </div>

          <!-- Pricing, Discounts & Inventory Card -->
          <div class="form-card glass-panel">
            <h3 class="card-title">
              <DollarSign :size="18" class="text-orange" />
              <span>{{ langStore.t.admin?.pricingAndStock || 'Pricing, Discounts & Wholesale Tiers' }}</span>
            </h3>

            <div class="form-row-3">
              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.retailPrice || 'B2C Retail / Selling Price ($ USD) *' }}</label>
                <div class="input-with-symbol">
                  <span class="symbol">$</span>
                  <input 
                    v-model="form.b2c_price" 
                    type="number" 
                    step="0.01" 
                    placeholder="2400.00" 
                    class="custom-input with-prefix"
                    required
                    @input="handlePriceChange"
                  />
                </div>
                <small class="hint-text">{{ langStore.t.admin?.retailPriceHint || 'Current price customer pays' }}</small>
              </div>

              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.compareAtPrice || 'Compare-At / Original Price ($ USD) (Discount)' }}</label>
                <div class="input-with-symbol">
                  <span class="symbol">$</span>
                  <input 
                    v-model="form.compare_at_price" 
                    type="number" 
                    step="0.01" 
                    placeholder="2800.00" 
                    class="custom-input with-prefix"
                    @input="handlePriceChange"
                  />
                </div>
                <small class="hint-text">{{ langStore.t.admin?.compareAtPriceHint || 'Original price before discount (strikethrough)' }}</small>
              </div>

              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.b2bWholesalePrice || 'B2B Wholesale Price ($ USD)' }}</label>
                <div class="input-with-symbol">
                  <span class="symbol">$</span>
                  <input 
                    v-model="form.b2b_price" 
                    type="number" 
                    step="0.01" 
                    placeholder="1950.00" 
                    class="custom-input with-prefix"
                  />
                </div>
                <small class="hint-text">{{ langStore.t.admin?.b2bWholesaleHint || 'Wholesale discount for garages' }}</small>
              </div>
            </div>

            <!-- Discount Savings Banner & On Sale Toggle -->
            <div class="discount-banner-row">
              <div v-if="discountPercent > 0" class="discount-pill-alert">
                <span class="pill-fire">🔥</span>
                <span><strong>{{ langStore.t.admin?.activeDiscount || 'Active Discount:' }}</strong> {{ langStore.t.admin?.customerSaves || 'Customer saves' }} ${{ discountSavings }} (<strong>{{ discountPercent }}% {{ langStore.t.admin?.off || 'OFF' }}</strong>)</span>
              </div>
              <div v-else class="discount-pill-none">
                <span>💡 {{ langStore.t.admin?.discountHint || 'Set Compare-At Price higher than Retail Price to display discount tags & strikethrough.' }}</span>
              </div>

              <label class="switch-checkbox-label">
                <input type="checkbox" v-model="form.is_on_sale" />
                <span class="switch-text">{{ langStore.t.admin?.markOnSale || '🏷️ Mark as On Sale (Special Promotion)' }}</span>
              </label>
            </div>

            <div class="form-row-2 mt-3">
              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.stockQuantity || 'Stock Quantity' }}</label>
                <input 
                  v-model="form.stock_quantity" 
                  type="number" 
                  min="0" 
                  placeholder="10" 
                  class="custom-input"
                />
              </div>
              <div class="form-group">
                <label class="lbl">{{ langStore.t.admin?.inventoryStatus || 'Inventory Status' }}</label>
                <div class="stock-status-pill in-stock">
                  <span class="dot-green"></span>
                  <span>{{ Number(form.stock_quantity) > 0 ? `${form.stock_quantity} ${langStore.t.admin?.unitsAvailable || 'Units Available in Stock'}` : (langStore.t.admin?.outOfStock || 'Out of Stock') }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Marketing Badges & Promotion Card (Viral / New / Best Seller) -->
          <div class="form-card glass-panel">
            <h3 class="card-title">
              <Flame :size="18" class="text-orange" />
              <span>{{ langStore.t.admin?.marketingBadges || 'Marketing Badges & Store Promotion' }}</span>
            </h3>
            <p class="card-subtext">{{ langStore.t.admin?.badgesSubtitle || 'Choose a badge to highlight this product on the shop catalog & homepage cards' }}</p>

            <div class="badge-selector-grid">
              <button 
                type="button" 
                class="badge-preset-btn"
                :class="{ active: form.badge === '' }"
                @click="form.badge = ''"
              >
                <span class="btn-preset-name">{{ langStore.t.admin?.none || 'None' }}</span>
                <small class="btn-preset-sub">{{ langStore.t.admin?.standardDisplay || 'Standard display' }}</small>
              </button>

              <button 
                v-for="b in badgePresets" 
                :key="b.value" 
                type="button" 
                class="badge-preset-btn"
                :class="{ active: form.badge === b.value, 'preset-viral': b.value === 'VIRAL' }"
                @click="selectBadge(b.value)"
              >
                <span class="btn-preset-name">{{ b.label }}</span>
                <small class="btn-preset-sub">{{ b.desc }}</small>
              </button>
            </div>

            <div class="form-group mt-3">
              <label class="lbl">{{ langStore.t.admin?.orCustomBadge || 'Or Custom Badge Text:' }}</label>
              <input 
                v-model="form.badge" 
                type="text" 
                :placeholder="langStore.t.admin?.customBadgePlaceholder || 'e.g. VIRAL, NEW, LIMITED EDITION, DYNO TESTED...'" 
                class="custom-input"
              />
            </div>

            <!-- Promotion Switches -->
            <div class="promo-toggles-grid">
              <label class="promo-toggle-card" :class="{ checked: form.is_featured }">
                <input type="checkbox" v-model="form.is_featured" />
                <div class="toggle-content">
                  <Award :size="16" class="text-orange" />
                  <div>
                    <strong>{{ langStore.t.admin?.featuredHomepage || 'Featured on Homepage' }}</strong>
                    <small>{{ langStore.t.admin?.featuredHomepageSub || 'Pin to Top Rated & Showcase sections' }}</small>
                  </div>
                </div>
              </label>

              <label class="promo-toggle-card" :class="{ checked: form.is_new_arrival }">
                <input type="checkbox" v-model="form.is_new_arrival" />
                <div class="toggle-content">
                  <Zap :size="16" class="text-cyan" />
                  <div>
                    <strong>{{ langStore.t.admin?.newArrivalTag || 'New Arrival Tag' }}</strong>
                    <small>{{ langStore.t.admin?.newArrivalSub || 'Display orange NEW badge on product card' }}</small>
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Right Column: Image Upload & Media -->
        <div class="sidebar-column">
          <!-- Image Upload Box -->
          <div class="form-card glass-panel">
            <h3 class="card-title">
              <ImageIcon :size="18" class="text-orange" />
              <span>{{ langStore.t.admin?.productImage || 'Product Image' }}</span>
            </h3>
            <p class="card-subtext">{{ langStore.t.admin?.imageSubtitle || 'Upload product photos directly from your computer or phone' }}</p>

            <!-- Hidden File Input -->
            <input 
              ref="fileInputRef"
              type="file" 
              accept="image/*" 
              class="hidden-file-input" 
              @change="handleFileChange"
            />

            <!-- Dropzone / Preview Area -->
            <div 
              class="dropzone-box" 
              :class="{ 'has-preview': !!imagePreview }"
              @click="!imagePreview && triggerFileInput()"
            >
              <template v-if="imagePreview">
                <img 
                  :src="formatImageUrl(imagePreview, DEFAULT_PRODUCT_THUMB)" 
                  class="preview-image" 
                  alt="Product Preview" 
                  @error="handleProductImageError($event)"
                />
                <div class="preview-overlay">
                  <button type="button" class="btn-change-image" @click.stop="triggerFileInput">
                    {{ langStore.t.admin?.changeImage || 'Change Image' }}
                  </button>
                  <button type="button" class="btn-remove-image" @click.stop="removeImage">
                    <Trash2 :size="14" />
                  </button>
                </div>
              </template>

              <template v-else>
                <div class="empty-dropzone">
                  <div class="upload-icon-circle">
                    <Upload :size="24" class="text-orange" />
                  </div>
                  <strong class="drop-title">{{ langStore.t.admin?.clickToUpload || 'Click to Upload Image' }}</strong>
                  <span class="drop-hint">{{ langStore.t.admin?.uploadFormats || 'PNG, JPG, WEBP up to 10MB' }}</span>
                </div>
              </template>

              <!-- Uploading state overlay -->
              <div v-if="isUploadingImage" class="uploading-state">
                <div class="spinner"></div>
                <span>Uploading Image to Storage...</span>
              </div>
            </div>

            <!-- Alternative Image URL input -->
            <div class="url-fallback">
              <label class="lbl">{{ langStore.t.admin?.orPasteUrl || 'Or Paste Image URL directly:' }}</label>
              <input 
                v-model="form.thumbnail" 
                type="url" 
                placeholder="https://... (Cloudflare R2 or Web URL)" 
                class="custom-input"
                @input="imagePreview = form.thumbnail"
              />
            </div>
          </div>

          <!-- Actions Card -->
          <div class="form-card glass-panel actions-card">
            <button 
              type="submit" 
              class="btn-publish" 
              :disabled="isSubmitting || isUploadingImage"
            >
              <Sparkles :size="16" />
              <span>{{ isSubmitting ? 'Saving...' : (isEditMode ? (langStore.t.admin?.updateBtn || 'Update Product in Database') : (langStore.t.admin?.publishBtn || 'Publish Product to Store')) }}</span>
            </button>
            <p class="publish-note">{{ isEditMode ? (langStore.t.admin?.updateNote || 'Updates are saved directly to the database and reflected immediately on store.') : (langStore.t.admin?.publishNote || 'Once published, the product will be instantly visible to all online customers.') }}</p>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.product-form-page {
  padding: 2rem 0 5rem;
  min-height: 85vh;
  width: 100%;
}

.admin-page-container {
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 2rem;
}

.top-nav-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.back-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: color 0.2s;
}

.back-link:hover {
  color: #ff5500;
}

.badge-tag {
  background: rgba(255, 85, 0, 0.12);
  color: #ff7834;
  border: 1px solid rgba(255, 85, 0, 0.3);
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.05em;
}

.page-title-row {
  margin-bottom: 2rem;
}

.main-title {
  font-size: 1.85rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.35rem;
}

.subtitle {
  color: #94a3b8;
  font-size: 0.92rem;
  margin: 0;
}

.alert-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-radius: 10px;
  margin-bottom: 1.75rem;
  font-size: 0.9rem;
}

.alert-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.alert-left strong {
  display: block;
}

.alert-left p {
  margin: 0;
  font-size: 0.8rem;
  opacity: 0.85;
}

.success-box {
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.35);
  color: #4ade80;
}

.btn-live-view {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: #22c55e;
  color: #042f2e;
  font-weight: 700;
  font-size: 0.82rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-live-view:hover {
  background: #16a34a;
  color: white;
}

.error-box {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.form-layout-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 1.75rem;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

.form-card {
  padding: 1.75rem;
  margin-bottom: 1.5rem;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #ffffff;
  font-size: 1.1rem;
  font-weight: 700;
  margin: 0 0 1.25rem;
}

.card-subtext {
  color: #94a3b8;
  font-size: 0.82rem;
  margin: -0.8rem 0 1.2rem;
}

.text-orange { color: #ff5500; }

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: 1.1rem;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.1rem;
}

.form-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 1.1rem;
}

.lbl {
  font-size: 0.82rem;
  font-weight: 600;
  color: #cbd5e1;
}

.custom-input,
.custom-select,
.custom-textarea {
  width: 100%;
  background: rgba(2, 6, 23, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #ffffff;
  padding: 0.75rem 0.9rem;
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
}

.custom-input:focus,
.custom-select:focus,
.custom-textarea:focus {
  border-color: #ff5500;
  box-shadow: 0 0 0 3px rgba(255, 85, 0, 0.15);
}

.input-with-symbol {
  position: relative;
  display: flex;
  align-items: center;
}

.input-with-symbol .symbol {
  position: absolute;
  left: 0.9rem;
  color: #64748b;
  font-weight: 700;
}

.custom-input.with-prefix {
  padding-left: 2rem;
}

.hint-text {
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 0.2rem;
}

/* Image Dropzone */
.hidden-file-input {
  display: none;
}

.dropzone-box {
  width: 100%;
  height: 240px;
  border: 2px dashed rgba(255, 255, 255, 0.18);
  border-radius: 12px;
  background: rgba(2, 6, 23, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.2s;
}

.dropzone-box:hover:not(.has-preview) {
  border-color: #ff5500;
  background: rgba(255, 85, 0, 0.05);
}

.empty-dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.5rem;
}

.upload-icon-circle {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(255, 85, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.drop-title {
  color: #f1f5f9;
  font-size: 0.9rem;
}

.drop-hint {
  color: #64748b;
  font-size: 0.75rem;
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.preview-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  opacity: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  transition: opacity 0.2s;
}

.dropzone-box:hover .preview-overlay {
  opacity: 1;
}

.btn-change-image {
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-remove-image {
  background: rgba(239, 68, 68, 0.8);
  color: white;
  border: none;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  cursor: pointer;
}

.uploading-state {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  font-size: 0.82rem;
  color: #ff7834;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid rgba(255, 85, 0, 0.2);
  border-top-color: #ff5500;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.url-fallback {
  margin-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 1rem;
}

.actions-card {
  text-align: center;
}

.btn-publish {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.9rem;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 800;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-publish:hover:not(:disabled) {
  background: #ff6a1f;
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(255, 85, 0, 0.4);
}

.btn-publish:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.publish-note {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0.75rem 0 0;
}

/* Discount & Promotional Badges Styling */
.discount-banner-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 1rem;
  padding: 0.85rem 1rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px dashed rgba(255, 85, 0, 0.25);
  border-radius: 8px;
}

.discount-pill-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #ff5500;
  font-size: 0.85rem;
}

.discount-pill-none {
  color: #64748b;
  font-size: 0.78rem;
}

.switch-checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
}

.switch-text {
  font-size: 0.82rem;
  font-weight: 700;
  color: #f1f5f9;
}

.stock-status-pill {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #4ade80;
  font-size: 0.82rem;
  font-weight: 600;
}

.dot-green {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 8px #22c55e;
}

/* Badge Presets */
.badge-selector-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.badge-preset-btn {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  padding: 0.75rem 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  cursor: pointer;
  transition: all 0.2s;
  color: #94a3b8;
  text-align: center;
}

.badge-preset-btn:hover {
  border-color: rgba(255, 85, 0, 0.4);
  background: rgba(255, 85, 0, 0.05);
}

.badge-preset-btn.active {
  border-color: #ff5500;
  background: rgba(255, 85, 0, 0.15);
  color: #ffffff;
  box-shadow: 0 0 15px rgba(255, 85, 0, 0.25);
}

.badge-preset-btn.preset-viral.active {
  border-color: #f97316;
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(249, 115, 22, 0.2));
  color: #fb923c;
}

.btn-preset-name {
  font-weight: 800;
  font-size: 0.85rem;
}

.btn-preset-sub {
  font-size: 0.7rem;
  color: #64748b;
}

/* Promotion Toggles */
.promo-toggles-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-top: 1rem;
}

.promo-toggle-card {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.promo-toggle-card:hover {
  border-color: rgba(255, 255, 255, 0.2);
}

.promo-toggle-card.checked {
  border-color: #ff5500;
  background: rgba(255, 85, 0, 0.08);
}

.promo-toggle-card input {
  margin-top: 0.2rem;
  cursor: pointer;
}

.toggle-content {
  display: flex;
  gap: 0.5rem;
  align-items: flex-start;
}

.toggle-content strong {
  display: block;
  font-size: 0.85rem;
  color: white;
}

.toggle-content small {
  display: block;
  font-size: 0.72rem;
  color: #94a3b8;
}

@media (max-width: 950px) {
  .form-layout-grid {
    grid-template-columns: 1fr;
  }
  .promo-toggles-grid {
    grid-template-columns: 1fr;
  }
}
</style>
