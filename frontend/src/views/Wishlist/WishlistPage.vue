<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWishlistStore } from '@/stores/wishlist'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'
import {
  Heart,
  ShoppingBag,
  Trash2,
  ArrowRight,
  Sparkles,
  CheckCircle,
  Tag,
  Building2,
  Flame,
  Layers
} from 'lucide-vue-next'

const router = useRouter()
const wishlistStore = useWishlistStore()
const cartStore = useCartStore()
const authStore = useAuthStore()

onMounted(() => {
  wishlistStore.fetchWishlist()
})

const handleAddToCart = (item: any) => {
  cartStore.addItem({
    id: Number(item.id),
    slug: item.slug,
    name: item.name,
    sku: item.sku || 'MCC-PART',
    thumbnail: item.thumbnail,
    price: authStore.isB2B && item.b2b_price ? Number(item.b2b_price) : Number(item.b2c_price),
    b2c_price: Number(item.b2c_price),
    b2b_price: item.b2b_price ? Number(item.b2b_price) : null,
    quantity: 1,
    category_name: item.category_name,
    brand_name: item.brand_name
  })
}

const handleMoveAllToCart = () => {
  wishlistStore.items.forEach(item => {
    handleAddToCart(item)
  })
  wishlistStore.clearWishlist()
}

const viewProduct = (slug: string) => {
  router.push(`/product/${slug}`)
}
</script>

<template>
  <div class="wishlist-page">
    <div class="container">
      <!-- Header -->
      <div class="wishlist-top-bar">
        <div>
          <span class="sub-badge">PERSONAL SHORTLIST</span>
          <h1 class="page-title">Saved Wishlist Parts</h1>
          <p class="page-sub">Keep track of performance upgrades, dyno components & custom accessories</p>
        </div>

        <div v-if="wishlistStore.itemsCount > 0" class="top-actions">
          <button class="btn-move-all" @click="handleMoveAllToCart">
            <ShoppingBag :size="16" />
            <span>Move All to Cart ({{ wishlistStore.itemsCount }})</span>
          </button>
          <button class="btn-clear-wishlist" @click="wishlistStore.clearWishlist">
            <Trash2 :size="15" />
            <span>Clear All</span>
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="wishlistStore.itemsCount === 0" class="empty-wishlist-card glass-panel">
        <div class="empty-icon-wrap">
          <Heart :size="48" class="text-orange" />
        </div>
        <h2 class="empty-title">Your Wishlist is Empty</h2>
        <p class="empty-sub">
          You haven't added any performance parts yet. Browse our verified genuine catalog and tap the heart icon to save products for later!
        </p>
        <router-link to="/shop" class="btn-explore-catalog">
          <span>Explore Performance Catalog</span>
          <ArrowRight :size="16" />
        </router-link>
      </div>

      <!-- Wishlist Grid -->
      <div v-else class="wishlist-grid">
        <div 
          v-for="item in wishlistStore.items" 
          :key="item.id" 
          class="wishlist-item-card glass-panel"
          @click="viewProduct(item.slug)"
        >
          <!-- Image Box -->
          <div class="item-img-box">
            <img :src="item.thumbnail" :alt="item.name" class="item-img" />
            
            <!-- Badges -->
            <span v-if="item.badge" class="badge-pill" :class="`badge-${item.badge.toLowerCase().replace(/[^a-z0-9]/g, '-')}`">
              {{ item.badge }}
            </span>
            <span v-else-if="item.compare_at_price && Number(item.compare_at_price) > Number(item.b2c_price)" class="badge-pill badge-sale">
              -{{ Math.round((1 - Number(item.b2c_price) / Number(item.compare_at_price)) * 100) }}% OFF
            </span>
            <span v-else-if="item.is_new_arrival" class="badge-pill badge-new">NEW</span>

            <!-- Remove Button -->
            <button 
              type="button" 
              class="btn-remove-wishlist"
              @click.stop="wishlistStore.removeFromWishlist(item.id)"
              title="Remove from Wishlist"
            >
              <Trash2 :size="15" />
            </button>
          </div>

          <!-- Content -->
          <div class="item-info">
            <div class="meta-row">
              <span class="brand-tag">{{ item.brand_name || 'MotoCar Custom' }}</span>
              <span class="category-tag">{{ item.category_name || 'Performance Part' }}</span>
            </div>

            <h3 class="item-name">{{ item.name }}</h3>
            <p v-if="item.short_description" class="item-desc">{{ item.short_description }}</p>

            <div class="fitment-chip">
              <CheckCircle :size="13" class="text-green" />
              <span>100% Guaranteed Fitment</span>
            </div>

            <!-- Price Stack -->
            <div class="price-action-footer">
              <div class="price-stack">
                <template v-if="authStore.isB2B">
                  <span class="lbl-price">B2B WHOLESALE:</span>
                  <strong class="price-val text-cyan">${{ Number(item.b2b_price || item.b2c_price * 0.8).toLocaleString() }}</strong>
                  <small class="retail-strike">Retail: ${{ Number(item.b2c_price).toLocaleString() }}</small>
                </template>
                <template v-else>
                  <span class="lbl-price">RETAIL PRICE:</span>
                  <strong class="price-val text-orange">${{ Number(item.b2c_price).toLocaleString() }}</strong>
                  <small v-if="item.compare_at_price && Number(item.compare_at_price) > Number(item.b2c_price)" class="retail-strike">
                    ${{ Number(item.compare_at_price).toLocaleString() }}
                  </small>
                </template>
              </div>

              <button class="btn-card-cart" @click.stop="handleAddToCart(item)">
                <ShoppingBag :size="15" />
                <span>Add to Cart</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.wishlist-page {
  padding: 3rem 0 5rem;
  min-height: 80vh;
}

.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1.25rem;
}

.wishlist-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 2.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sub-badge {
  font-size: 0.72rem;
  font-weight: 800;
  color: #ff5500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.page-title {
  font-size: 2.4rem;
  font-weight: 900;
  color: #ffffff;
  margin: 0.25rem 0 0.5rem;
  letter-spacing: -0.03em;
}

.page-sub {
  color: #94a3b8;
  font-size: 0.95rem;
  margin: 0;
}

.top-actions {
  display: flex;
  gap: 0.85rem;
}

.btn-move-all {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-move-all:hover {
  background: #ff6a1f;
  transform: translateY(-2px);
  box-shadow: 0 4px 18px rgba(255, 85, 0, 0.4);
}

.btn-clear-wishlist {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #94a3b8;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-clear-wishlist:hover {
  background: rgba(239, 68, 68, 0.15);
  border-color: #ef4444;
  color: #ef4444;
}

/* Empty State */
.empty-wishlist-card {
  text-align: center;
  padding: 4.5rem 2rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px dashed rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  max-width: 600px;
  margin: 2rem auto;
}

.empty-icon-wrap {
  width: 90px;
  height: 90px;
  margin: 0 auto 1.5rem;
  border-radius: 50%;
  background: rgba(255, 85, 0, 0.1);
  border: 1px solid rgba(255, 85, 0, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-title {
  font-size: 1.6rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.75rem;
}

.empty-sub {
  color: #94a3b8;
  font-size: 0.95rem;
  line-height: 1.5;
  margin: 0 0 2rem;
}

.btn-explore-catalog {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  background: #ff5500;
  color: white;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.92rem;
  padding: 0.85rem 1.75rem;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(255, 85, 0, 0.35);
  transition: all 0.2s;
}

.btn-explore-catalog:hover {
  background: #ff6a1f;
  transform: translateY(-2px);
  box-shadow: 0 6px 25px rgba(255, 85, 0, 0.5);
}

/* Wishlist Grid */
.wishlist-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.wishlist-item-card {
  background: rgba(15, 23, 42, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  transition: all 0.25s ease;
}

.wishlist-item-card:hover {
  transform: translateY(-4px);
  border-color: rgba(255, 85, 0, 0.4);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
}

.item-img-box {
  position: relative;
  height: 220px;
  background: #020617;
  overflow: hidden;
}

.item-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.wishlist-item-card:hover .item-img {
  transform: scale(1.06);
}

.btn-remove-wishlist {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 5;
}

.btn-remove-wishlist:hover {
  background: rgba(239, 68, 68, 0.25);
  border-color: #ef4444;
  color: #ef4444;
  transform: scale(1.1);
}

.badge-pill {
  position: absolute;
  top: 0.75rem;
  left: 0.75rem;
  color: white;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  letter-spacing: 0.04em;
  z-index: 2;
}

.badge-new {
  background: #ff5500;
}

.badge-viral,
.badge-viral- {
  background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
  box-shadow: 0 0 12px rgba(239, 68, 68, 0.5);
}

.badge-sale {
  background: #dc2626;
}

.item-info {
  padding: 1.25rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.meta-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.brand-tag {
  color: #ff5500;
  font-weight: 800;
  font-size: 0.72rem;
  text-transform: uppercase;
}

.category-tag {
  color: #64748b;
  font-size: 0.72rem;
}

.item-name {
  color: #ffffff;
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1.35;
  margin: 0 0 0.5rem;
}

.item-desc {
  color: #94a3b8;
  font-size: 0.8rem;
  line-height: 1.4;
  margin: 0 0 0.75rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.fitment-chip {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.72rem;
  color: #22c55e;
  font-weight: 600;
  margin-bottom: 1rem;
}

.price-action-footer {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding-top: 0.85rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.price-stack {
  display: flex;
  flex-direction: column;
}

.lbl-price {
  font-size: 0.62rem;
  color: #64748b;
  font-weight: 800;
}

.price-val {
  font-size: 1.2rem;
  font-weight: 900;
}

.retail-strike {
  font-size: 0.72rem;
  color: #64748b;
  text-decoration: line-through;
}

.btn-card-cart {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.55rem 0.9rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.82rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-card-cart:hover {
  background: #ff6a1f;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(255, 85, 0, 0.35);
}

.text-orange { color: #ff5500; }
.text-cyan { color: #00d2ff; }
.text-green { color: #22c55e; }
</style>
