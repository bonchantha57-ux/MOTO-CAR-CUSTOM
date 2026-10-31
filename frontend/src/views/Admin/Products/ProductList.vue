<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useLanguageStore } from '@/stores/language'
import axios from 'axios'
import {
  Plus,
  Search,
  Package,
  Eye,
  Trash2,
  Edit,
  ExternalLink,
  Tag,
  Building2,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next'
import { formatImageUrl, handleProductImageError, DEFAULT_PRODUCT_THUMB } from '@/utils/image'

const router = useRouter()
const langStore = useLanguageStore()

const products = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')

const fetchProducts = async () => {
  isLoading.value = true
  try {
    const res = await axios.get('/api/v1/products', {
      params: { q: searchQuery.value || undefined, per_page: 50 }
    })
    products.value = res.data.data || []
  } catch (err) {
    console.error('Error fetching products:', err)
  } finally {
    isLoading.value = false
  }
}

const successMessage = ref('')
const errorMessage = ref('')

onMounted(() => {
  fetchProducts()
})

const handleSearch = () => {
  fetchProducts()
}

const handleDelete = async (prod: any) => {
  if (confirm(`Are you sure you want to permanently delete "${prod.name}" from the database?`)) {
    try {
      const res = await axios.delete(`/api/v1/admin/products/${prod.id}`)
      successMessage.value = res.data.message || 'Product deleted successfully from database.'
      fetchProducts()
      setTimeout(() => { successMessage.value = '' }, 4000)
    } catch (err: any) {
      errorMessage.value = err.response?.data?.message || 'Failed to delete product.'
    }
  }
}
</script>

<template>
  <div class="product-list-page">
    <div class="admin-page-container">
      <!-- Page Header -->
      <div class="page-top-bar">
        <div>
          <span class="sub-badge">{{ langStore.t.admin?.inventoryManagement || 'INVENTORY MANAGEMENT' }}</span>
          <h1 class="page-title">{{ langStore.t.admin?.catalogProducts || 'Catalog & Products' }}</h1>
          <p class="page-sub">{{ langStore.t.admin?.catalogSub || 'Manage all motorcycle and car performance parts available in your store' }}</p>
        </div>

        <router-link to="/admin/products/create" class="btn-create-new">
          <Plus :size="16" />
          <span>{{ langStore.t.admin?.addNewProduct || 'Add New Product' }}</span>
        </router-link>
      </div>

      <!-- Alerts -->
      <div v-if="successMessage" class="alert-box success-box">
        <CheckCircle2 :size="18" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="alert-box error-box">
        <AlertCircle :size="18" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Search & Filters Bar -->
      <div class="filters-card glass-panel">
        <div class="search-input-box">
          <Search :size="16" class="search-icon" />
          <input 
            v-model="searchQuery" 
            type="text" 
            :placeholder="langStore.t.admin?.searchProductPlaceholder || 'Search by product name, SKU, or brand...'" 
            class="search-input"
            @keyup.enter="handleSearch"
          />
        </div>
        <button class="btn-search" @click="handleSearch">{{ langStore.t.admin?.searchBtn || 'Search' }}</button>
      </div>

      <!-- Products Table -->
      <div class="table-container glass-panel">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <span>Loading products from database...</span>
        </div>

        <div v-else-if="products.length === 0" class="empty-state">
          <Package :size="48" class="text-orange" />
          <h3>{{ langStore.t.admin?.noProducts || 'No Products Found' }}</h3>
          <p>{{ langStore.t.admin?.noProductsDesc || 'Get started by adding your first performance tuning product to the store.' }}</p>
          <router-link to="/admin/products/create" class="btn-create-new">
            <Plus :size="16" />
            <span>{{ langStore.t.admin?.addFirstProduct || 'Add First Product' }}</span>
          </router-link>
        </div>

        <table v-else class="products-table">
          <thead>
            <tr>
              <th>{{ langStore.t.admin?.thPart || 'Part / Product' }}</th>
              <th>{{ langStore.t.admin?.thSku || 'SKU' }}</th>
              <th>{{ langStore.t.admin?.thCategory || 'Category' }}</th>
              <th>{{ langStore.t.admin?.thBrand || 'Brand' }}</th>
              <th>{{ langStore.t.admin?.thRetail || 'Retail (B2C)' }}</th>
              <th>{{ langStore.t.admin?.thWholesale || 'Wholesale (B2B)' }}</th>
              <th>{{ langStore.t.admin?.thStock || 'Stock' }}</th>
              <th>{{ langStore.t.admin?.thActions || 'Actions' }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="prod in products" :key="prod.id">
              <td class="product-cell">
                <img 
                  :src="formatImageUrl(prod.thumbnail, DEFAULT_PRODUCT_THUMB)" 
                  class="product-thumb" 
                  :alt="prod.name"
                  @error="handleProductImageError($event)"
                />
                <div class="product-meta">
                  <strong class="product-name">{{ prod.name }}</strong>
                  <span class="product-vehicle-type">{{ prod.vehicle_type || 'Universal' }}</span>
                </div>
              </td>
              <td><span class="sku-chip">{{ prod.sku }}</span></td>
              <td><span class="category-name">{{ prod.category_name || 'General' }}</span></td>
              <td><span class="brand-name">{{ prod.brand_name || 'MotoCar' }}</span></td>
              <td><strong class="price-b2c">${{ Number(prod.b2c_price).toLocaleString() }}</strong></td>
              <td><span class="price-b2b">${{ Number(prod.b2b_price || prod.b2c_price * 0.8).toLocaleString() }}</span></td>
              <td>
                <span class="stock-pill" :class="prod.stock_quantity > 0 ? 'in-stock' : 'out-stock'">
                  {{ prod.stock_quantity > 0 ? `${prod.stock_quantity} ${langStore.t.admin?.inStock || 'in stock'}` : (langStore.t.admin?.outOfStock || 'Out of Stock') }}
                </span>
              </td>
              <td class="actions-cell">
                <router-link :to="`/product/${prod.slug}`" class="btn-icon-view" title="View live on website" target="_blank">
                  <ExternalLink :size="15" />
                </router-link>
                <router-link :to="`/admin/products/${prod.id}/edit`" class="btn-icon-edit" title="Edit Product">
                  <Edit :size="15" />
                </router-link>
                <button class="btn-icon-delete" title="Delete Product" @click="handleDelete(prod)">
                  <Trash2 :size="15" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-list-page {
  padding: 2rem 0 5rem;
  min-height: 85vh;
  width: 100%;
}

.admin-page-container {
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 1.75rem !important;
  box-sizing: border-box !important;
}

.page-top-bar {
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

.btn-create-new {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  padding: 0.75rem 1.4rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.9rem;
  text-decoration: none;
  transition: all 0.2s;
  flex-shrink: 0;
  white-space: nowrap;
}

.btn-create-new:hover {
  background: #ff6a1f;
  transform: translateY(-2px);
  box-shadow: 0 4px 18px rgba(255, 85, 0, 0.35);
}

.glass-panel {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

.filters-card {
  padding: 1rem 1.25rem;
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
  padding: 0.7rem 0.9rem 0.7rem 2.4rem;
  font-size: 0.88rem;
  outline: none;
}

.search-input:focus {
  border-color: #ff5500;
}

.btn-search {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #cbd5e1;
  padding: 0.65rem 1.4rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
  white-space: nowrap;
}

.btn-search:hover {
  background: rgba(255, 255, 255, 0.15);
  color: white;
}

/* Table */
.table-container {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  box-sizing: border-box;
}

.products-table {
  width: 100%;
  min-width: 960px;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.88rem;
}

.products-table th {
  background: rgba(2, 6, 23, 0.5);
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.products-table td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  vertical-align: middle;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.product-thumb {
  width: 50px;
  height: 50px;
  border-radius: 8px;
  object-fit: cover;
  background: rgba(2, 6, 23, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.product-meta {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.product-name {
  color: #ffffff;
  font-size: 0.92rem;
}

.product-vehicle-type {
  font-size: 0.72rem;
  color: #64748b;
  text-transform: capitalize;
}

.sku-chip {
  font-family: monospace;
  font-size: 0.78rem;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.25rem 0.6rem;
  border-radius: 4px;
  color: #94a3b8;
  white-space: nowrap;
  display: inline-block;
  letter-spacing: 0.03em;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.price-b2c {
  color: #ff5500;
  font-size: 1rem;
  font-weight: 800;
}

.price-b2b {
  color: #38bdf8;
  font-weight: 700;
  font-size: 0.88rem;
}

.stock-pill {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.stock-pill.in-stock {
  background: rgba(34, 197, 94, 0.12);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.25);
}

.stock-pill.out-stock {
  background: rgba(239, 68, 68, 0.12);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.25);
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.btn-icon-view {
  color: #94a3b8;
  padding: 0.4rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon-view:hover {
  color: #ff5500;
  background: rgba(255, 85, 0, 0.1);
}

.btn-icon-edit {
  color: #38bdf8;
  padding: 0.4rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon-edit:hover {
  background: rgba(56, 189, 248, 0.15);
}

.btn-icon-delete {
  background: none;
  border: none;
  color: #f87171;
  padding: 0.4rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon-delete:hover {
  background: rgba(239, 68, 68, 0.15);
}

.alert-box {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.85rem 1.25rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.88rem;
}

.success-box {
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #4ade80;
}

.error-box {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
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
}

.text-orange { color: #ff5500; }

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(255, 85, 0, 0.2);
  border-top-color: #ff5500;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
