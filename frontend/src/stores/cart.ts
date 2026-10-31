import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import { useAuthStore } from './auth'

export interface CartItem {
  id: number
  slug: string
  name: string
  sku: string
  thumbnail: string
  price: number // Current applied price (b2c or b2b)
  b2c_price: number
  b2b_price: number | null
  quantity: number
  category_name?: string
  brand_name?: string
  installation_selected?: boolean
  installation_fee?: number
}

export const useCartStore = defineStore('cart', () => {
  const authStore = useAuthStore()

  const getStorageKey = () => {
    return authStore.user?.id ? `motocar_cart_user_${authStore.user.id}` : 'motocar_cart_auth'
  }

  // Cart is ONLY available when user is authenticated with an account
  const loadItems = (): CartItem[] => {
    if (!authStore.isAuthenticated) {
      localStorage.removeItem('motocar_cart')
      return []
    }
    const savedCart = localStorage.getItem(getStorageKey())
    if (savedCart) {
      try {
        const parsed = JSON.parse(savedCart)
        if (Array.isArray(parsed)) {
          return parsed.filter(item => item.sku !== 'MCC-EXH-001' && item.name !== 'Akrapovič Evolution Titanium Full Exhaust System')
        }
      } catch {
        return []
      }
    }
    return []
  }

  const items = ref<CartItem[]>(loadItems())

  // If user logs in or out, synchronize cart state
  watch(() => authStore.isAuthenticated, (isAuth) => {
    if (!isAuth) {
      items.value = []
      localStorage.removeItem('motocar_cart')
    } else {
      items.value = loadItems()
    }
  })

  const persistCart = () => {
    if (authStore.isAuthenticated) {
      localStorage.setItem(getStorageKey(), JSON.stringify(items.value))
    } else {
      items.value = []
      localStorage.removeItem('motocar_cart')
    }
  }

  const totalItems = computed(() => {
    return items.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  const subtotal = computed(() => {
    return items.value.reduce((sum, item) => {
      const itemTotal = item.price * item.quantity
      const installTotal = (item.installation_selected && item.installation_fee) ? item.installation_fee : 0
      return sum + itemTotal + installTotal
    }, 0)
  })

  const shippingFee = computed(() => {
    return items.value.length > 0 ? (subtotal.value > 500 ? 0 : 15) : 0
  })

  const totalAmount = computed(() => {
    return subtotal.value + shippingFee.value
  })

  function addItem(product: any, quantity: number = 1, isB2B: boolean = false, withInstallation: boolean = false): boolean {
    if (!authStore.isAuthenticated) {
      items.value = []
      localStorage.removeItem('motocar_cart')
      return false
    }
    const existingIndex = items.value.findIndex(item => item.id === product.id)
    const appliedPrice = isB2B && product.b2b_price ? Number(product.b2b_price) : Number(product.b2c_price)

    if (existingIndex > -1) {
      items.value[existingIndex].quantity += quantity
      items.value[existingIndex].price = appliedPrice
      if (withInstallation) {
        items.value[existingIndex].installation_selected = true
        items.value[existingIndex].installation_fee = 25
      }
    } else {
      items.value.push({
        id: product.id,
        slug: product.slug,
        name: product.name,
        sku: product.sku || `MCC-${product.id}`,
        thumbnail: product.thumbnail || 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=600&q=80',
        price: appliedPrice,
        b2c_price: Number(product.b2c_price),
        b2b_price: product.b2b_price ? Number(product.b2b_price) : null,
        quantity: Math.max(1, quantity),
        category_name: product.category_name,
        brand_name: product.brand_name,
        installation_selected: withInstallation,
        installation_fee: withInstallation ? 25 : 0
      })
    }
    persistCart()
  }

  function updateQuantity(productId: number, newQty: number) {
    const item = items.value.find(i => i.id === productId)
    if (item) {
      if (newQty <= 0) {
        removeItem(productId)
      } else {
        item.quantity = newQty
        persistCart()
      }
    }
  }

  function removeItem(productId: number) {
    items.value = items.value.filter(i => i.id !== productId)
    persistCart()
  }

  function clearCart() {
    items.value = []
    persistCart()
  }

  function toggleInstallation(productId: number) {
    const item = items.value.find(i => i.id === productId)
    if (item) {
      item.installation_selected = !item.installation_selected
      item.installation_fee = item.installation_selected ? 25 : 0
      persistCart()
    }
  }

  return {
    items,
    totalItems,
    subtotal,
    shippingFee,
    totalAmount,
    addItem,
    updateQuantity,
    removeItem,
    clearCart,
    toggleInstallation
  }
})
