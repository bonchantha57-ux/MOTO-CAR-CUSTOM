import axios from 'axios'

const rawBaseUrl = (import.meta.env.VITE_API_BASE_URL as string) || 'https://moto-car-custom-production.up.railway.app'
const cleanBaseUrl = rawBaseUrl.replace(/\/+$/, '')
const apiBase = cleanBaseUrl.endsWith('/api') ? cleanBaseUrl : `${cleanBaseUrl}/api`

const api = axios.create({
  baseURL: apiBase,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor - attach auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Response interceptor - handle 401
api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      const isAuthUrl = error.config?.url?.includes('/auth/')
      const isAuthPath = window.location.pathname.startsWith('/auth')
      if (!isAuthUrl && !isAuthPath) {
        window.location.href = '/auth/login'
      }
    }
    return Promise.reject(error.response?.data || error)
  }
)

export default api

// Typed API endpoints
export const authApi = {
  register: (data: any) => api.post('/auth/register', data),
  login: (data: any) => api.post('/auth/login', data),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
  updateProfile: (data: FormData) => api.put('/auth/profile', data),
  switchMode: (mode: 'b2c' | 'b2b') => api.post('/auth/switch-mode', { mode }),
  forgotPassword: (email: string) => api.post('/auth/forgot-password', { email }),
  b2bRegister: (data: any) => api.post('/auth/b2b/register', data),
}

export const productApi = {
  list: (params?: any) => api.get('/v1/products', { params }),
  featured: () => api.get('/v1/products/featured'),
  newArrivals: () => api.get('/v1/products/new-arrivals'),
  onSale: () => api.get('/v1/products/on-sale'),
  detail: (slug: string) => api.get(`/v1/products/${slug}`),
  reviews: (id: number) => api.get(`/v1/products/${id}/reviews`),
  submitReview: (productId: number, data: any) => api.post(`/v1/products/${productId}/reviews`, data),
}

export const categoryApi = {
  list: () => api.get('/v1/categories'),
  detail: (slug: string) => api.get(`/v1/categories/${slug}`),
}

export const vehicleApi = {
  types: () => api.get('/v1/vehicles/types'),
  makes: (params?: any) => api.get('/v1/vehicles/makes', { params }),
  models: (params?: any) => api.get('/v1/vehicles/models', { params }),
  years: (params?: any) => api.get('/v1/vehicles/years', { params }),
}

export const cartApi = {
  get: () => api.get('/v1/cart'),
  add: (productId: number, quantity: number) => api.post('/v1/cart/add', { product_id: productId, quantity }),
  update: (productId: number, quantity: number) => api.put('/v1/cart/update', { product_id: productId, quantity }),
  remove: (productId: number) => api.delete(`/v1/cart/remove/${productId}`),
  clear: () => api.delete('/v1/cart/clear'),
  applyCoupon: (code: string) => api.post('/v1/cart/coupon', { code }),
  removeCoupon: () => api.delete('/v1/cart/coupon'),
}

export const wishlistApi = {
  get: () => api.get('/v1/wishlist'),
  toggle: (productId: number) => api.post(`/v1/wishlist/toggle/${productId}`),
}

export const orderApi = {
  list: (params?: any) => api.get('/v1/orders', { params }),
  checkout: (data: any) => api.post('/v1/orders/checkout', data),
  detail: (orderNumber: string) => api.get(`/v1/orders/${orderNumber}`),
  cancel: (orderId: number) => api.post(`/v1/orders/${orderId}/cancel`),
  track: (orderNumber: string) => api.get(`/v1/track/${orderNumber}`),
}

export const paymentApi = {
  generateKhqr: (orderId: number) => api.post('/v1/payment/khqr/generate', { order_id: orderId }),
  verifyKhqr: (transactionId: string) => api.post('/v1/payment/khqr/verify', { transaction_id: transactionId }),
}

export const garageApi = {
  list: (params?: any) => api.get('/v1/garages', { params }),
  detail: (slug: string) => api.get(`/v1/garages/${slug}`),
  availability: (id: number, date: string) => api.get(`/v1/garages/${id}/availability`, { params: { date } }),
  registerPartnership: (data: FormData) => api.post('/v1/garages/register', data),
}

export const bookingApi = {
  list: () => api.get('/v1/bookings'),
  create: (data: any) => api.post('/v1/bookings', data),
  detail: (id: number) => api.get(`/v1/bookings/${id}`),
  cancel: (id: number) => api.post(`/v1/bookings/${id}/cancel`),
}

export const myGarageApi = {
  list: () => api.get('/v1/my-garage'),
  add: (data: any) => api.post('/v1/my-garage', data),
  remove: (id: number) => api.delete(`/v1/my-garage/${id}`),
}

export const addressApi = {
  list: () => api.get('/v1/addresses'),
  create: (data: any) => api.post('/v1/addresses', data),
  update: (id: number, data: any) => api.put(`/v1/addresses/${id}`, data),
  delete: (id: number) => api.delete(`/v1/addresses/${id}`),
  setDefault: (id: number) => api.post(`/v1/addresses/${id}/default`),
}

export const b2bApi = {
  catalog: (params?: any) => api.get('/v1/b2b/catalog', { params }),
  tierPricing: (productId: number) => api.get(`/v1/b2b/pricing/${productId}`),
  rfqList: () => api.get('/v1/b2b/rfq'),
  rfqDetail: (id: number) => api.get(`/v1/b2b/rfq/${id}`),
  submitRfq: (data: any) => api.post('/v1/b2b/rfq', data),
}

export const homeApi = {
  get: () => api.get('/v1/home'),
  banners: () => api.get('/v1/banners'),
}

// Admin API
export const adminApi = {
  dashboard: () => api.get('/admin/dashboard'),
  analytics: () => api.get('/admin/dashboard/analytics'),
  
  // Products
  products: {
    list: (params?: any) => api.get('/admin/products', { params }),
    create: (data: FormData) => api.post('/admin/products', data),
    update: (id: number, data: FormData) => api.post(`/admin/products/${id}`, data),
    delete: (id: number) => api.delete(`/admin/products/${id}`),
    uploadImages: (id: number, data: FormData) => api.post(`/admin/products/${id}/images`, data),
  },

  // Orders
  orders: {
    list: (params?: any) => api.get('/admin/orders', { params }),
    detail: (id: number) => api.get(`/admin/orders/${id}`),
    updateStatus: (id: number, status: string) => api.put(`/admin/orders/${id}/status`, { status }),
  },

  // B2B
  b2b: {
    accounts: (params?: any) => api.get('/admin/b2b/accounts', { params }),
    approve: (id: number) => api.put(`/admin/b2b/accounts/${id}/approve`),
    reject: (id: number, reason: string) => api.put(`/admin/b2b/accounts/${id}/reject`, { reason }),
    rfqList: (params?: any) => api.get('/admin/b2b/rfq', { params }),
    sendQuote: (id: number, data: any) => api.put(`/admin/b2b/rfq/${id}/quote`, data),
  },

  // Garages
  garages: {
    list: (params?: any) => api.get('/admin/garages', { params }),
    approve: (id: number) => api.put(`/admin/garages/${id}/approve`),
    bookings: (id: number) => api.get(`/admin/garages/${id}/bookings`),
  },

  // Reports  
  reports: {
    sales: (params?: any) => api.get('/admin/reports/sales', { params }),
    inventory: () => api.get('/admin/reports/inventory'),
    garagePerformance: () => api.get('/admin/reports/garage-performance'),
    fitmentLogs: () => api.get('/admin/reports/fitment-logs'),
  },

  // Upload
  upload: (data: FormData) => api.post('/admin/upload', data),
}
