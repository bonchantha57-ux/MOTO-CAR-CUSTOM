import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export interface WishlistItem {
  id: number
  slug: string
  name: string
  sku?: string
  thumbnail: string
  b2c_price: number
  compare_at_price?: number | null
  b2b_price?: number | null
  category_name?: string
  brand_name?: string
  badge?: string | null
  is_new_arrival?: boolean | number
  is_on_sale?: boolean | number
  is_featured?: boolean | number
  short_description?: string
}

export const useWishlistStore = defineStore('wishlist', () => {
  const savedWishlist = localStorage.getItem('motocar_wishlist')
  const items = ref<WishlistItem[]>(savedWishlist ? JSON.parse(savedWishlist) : [])
  const isLoading = ref(false)

  const persistWishlist = () => {
    localStorage.setItem('motocar_wishlist', JSON.stringify(items.value))
  }

  const itemsCount = computed(() => items.value.length)

  const isInWishlist = (id: number | string) => {
    const numId = Number(id)
    return items.value.some(item => Number(item.id) === numId)
  }

  const toggleWishlist = async (product: any) => {
    const numId = Number(product.id)
    const existingIndex = items.value.findIndex(item => Number(item.id) === numId)

    if (existingIndex > -1) {
      // Remove
      items.value.splice(existingIndex, 1)
      persistWishlist()
    } else {
      // Add
      const newItem: WishlistItem = {
        id: numId,
        slug: product.slug || `product-${numId}`,
        name: product.name || 'Performance Part',
        sku: product.sku || '',
        thumbnail: product.thumbnail || product.image || 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=600&q=80',
        b2c_price: Number(product.b2c_price || product.price_b2c || 0),
        compare_at_price: product.compare_at_price ? Number(product.compare_at_price) : null,
        b2b_price: product.b2b_price ? Number(product.b2b_price) : null,
        category_name: product.category_name,
        brand_name: product.brand_name,
        badge: product.badge,
        is_new_arrival: product.is_new_arrival,
        is_on_sale: product.is_on_sale,
        is_featured: product.is_featured,
        short_description: product.short_description
      }
      items.value.unshift(newItem)
      persistWishlist()
    }

    // Sync with backend API if user is authenticated
    const token = localStorage.getItem('auth_token')
    if (token) {
      try {
        await axios.post(`/api/v1/wishlist/toggle/${numId}`)
      } catch (err) {
        // Silently keep local state if offline or token expired
        console.warn('Backend wishlist sync skipped:', err)
      }
    }
  }

  const removeFromWishlist = async (productId: number | string) => {
    const numId = Number(productId)
    items.value = items.value.filter(item => Number(item.id) !== numId)
    persistWishlist()

    const token = localStorage.getItem('auth_token')
    if (token) {
      try {
        await axios.delete(`/api/v1/wishlist/${numId}`)
      } catch (err) {
        console.warn('Backend wishlist remove skipped:', err)
      }
    }
  }

  const fetchWishlist = async () => {
    const token = localStorage.getItem('auth_token')
    if (!token) return

    isLoading.value = true
    try {
      const res = await axios.get('/api/v1/wishlist')
      if (res.data.status === 'success' && Array.isArray(res.data.data)) {
        // If server has items, sync with server
        if (res.data.data.length > 0) {
          items.value = res.data.data
          persistWishlist()
        }
      }
    } catch (err) {
      console.warn('Could not fetch backend wishlist:', err)
    } finally {
      isLoading.value = false
    }
  }

  const clearWishlist = () => {
    items.value = []
    persistWishlist()
  }

  return {
    items,
    isLoading,
    itemsCount,
    isInWishlist,
    toggleWishlist,
    removeFromWishlist,
    fetchWishlist,
    clearWishlist
  }
})
