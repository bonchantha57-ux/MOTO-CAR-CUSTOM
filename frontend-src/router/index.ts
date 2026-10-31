import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    return { top: 0, behavior: 'smooth' }
  },
  routes: [
    // ============ CUSTOMER PORTAL ============
    {
      path: '/',
      component: () => import('@/layouts/CustomerLayout.vue'),
      children: [
        { path: '', name: 'home', component: () => import('@/views/Home/HomePage.vue') },
        { path: 'shop', name: 'shop', component: () => import('@/views/Shop/ShopPage.vue') },
        { path: 'shop/:category', name: 'shop-category', component: () => import('@/views/Shop/ShopPage.vue') },
        { path: 'product/:slug', name: 'product-detail', component: () => import('@/views/Product/ProductDetail.vue') },
        { path: 'garages', name: 'garages', component: () => import('@/views/Garage/GarageDirectory.vue') },
        { path: 'garages/:slug', name: 'garage-detail', component: () => import('@/views/Garage/GarageDetail.vue') },
        { path: 'track/:orderNumber?', name: 'track-order', component: () => import('@/views/Order/OrderTracking.vue') },
        { path: 'about', name: 'about', component: () => import('@/views/Content/AboutPage.vue') },
        { path: 'faq', name: 'faq', component: () => import('@/views/Content/FaqPage.vue') },
        { path: 'page/:slug', name: 'content-page', component: () => import('@/views/Content/ContentPage.vue') },
        
        // Protected Routes
        { path: 'cart', name: 'cart', component: () => import('@/views/Cart/CartPage.vue'), meta: { requiresAuth: true } },
        { path: 'checkout', name: 'checkout', component: () => import('@/views/Checkout/CheckoutPage.vue'), meta: { requiresAuth: true } },
        { path: 'order-confirmation/:orderNumber', name: 'order-confirmation', component: () => import('@/views/Order/OrderConfirmation.vue'), meta: { requiresAuth: true } },
        { path: 'wishlist', name: 'wishlist', component: () => import('@/views/Wishlist/WishlistPage.vue'), meta: { requiresAuth: true } },
        
        // Account
        {
          path: 'account',
          component: () => import('@/views/Account/AccountLayout.vue'),
          meta: { requiresAuth: true },
          children: [
            { path: '', name: 'account', redirect: '/account/dashboard' },
            { path: 'dashboard', name: 'account-dashboard', component: () => import('@/views/Account/AccountDashboard.vue') },
            { path: 'profile', name: 'account-profile', component: () => import('@/views/Account/ProfilePage.vue') },
            { path: 'orders', name: 'account-orders', component: () => import('@/views/Account/OrdersPage.vue') },
            { path: 'orders/:orderNumber', name: 'account-order-detail', component: () => import('@/views/Account/OrderDetail.vue') },
            { path: 'garage', name: 'account-garage', component: () => import('@/views/Account/MyGarage.vue') },
            { path: 'addresses', name: 'account-addresses', component: () => import('@/views/Account/AddressesPage.vue') },
            { path: 'rfq', name: 'account-rfq', component: () => import('@/views/Account/RfqPage.vue') },
            { path: 'bookings', name: 'account-bookings', component: () => import('@/views/Account/BookingsPage.vue') },
            { path: 'change-password', name: 'account-password', component: () => import('@/views/Account/ChangePassword.vue') },
          ],
        },
      ],
    },

    // ============ AUTH PAGES ============
    {
      path: '/auth',
      component: () => import('@/layouts/AuthLayout.vue'),
      children: [
        { path: 'login', name: 'login', component: () => import('@/views/Auth/LoginPage.vue'), meta: { guestOnly: true } },
        { path: 'register', name: 'register', component: () => import('@/views/Auth/RegisterPage.vue'), meta: { guestOnly: true } },
        { path: 'b2b-register', name: 'b2b-register', component: () => import('@/views/Auth/B2bRegister.vue') },
        { path: 'forgot-password', name: 'forgot-password', component: () => import('@/views/Auth/ForgotPassword.vue') },
        { path: 'reset-password', name: 'reset-password', component: () => import('@/views/Auth/ResetPassword.vue') },
      ],
    },

    // ============ ADMIN PORTAL ============
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAdmin: true },
      children: [
        { path: '', name: 'admin', redirect: '/admin/dashboard' },
        { path: 'dashboard', name: 'admin-dashboard', component: () => import('@/views/Admin/Dashboard/DashboardPage.vue') },
        
        // Products
        { path: 'products', name: 'admin-products', component: () => import('@/views/Admin/Products/ProductList.vue') },
        { path: 'products/create', name: 'admin-product-create', component: () => import('@/views/Admin/Products/ProductForm.vue') },
        { path: 'products/:id/edit', name: 'admin-product-edit', component: () => import('@/views/Admin/Products/ProductForm.vue') },
        { path: 'categories', name: 'admin-categories', component: () => import('@/views/Admin/Products/CategoryList.vue') },
        { path: 'brands', name: 'admin-brands', component: () => import('@/views/Admin/Products/BrandList.vue') },
        
        // Vehicle Fitment
        { path: 'vehicles', name: 'admin-vehicles', component: () => import('@/views/Admin/Vehicles/VehicleDatabase.vue') },
        
        // Orders
        { path: 'orders', name: 'admin-orders', component: () => import('@/views/Admin/Orders/OrderList.vue') },
        { path: 'orders/:id', name: 'admin-order-detail', component: () => import('@/views/Admin/Orders/OrderDetail.vue') },
        { path: 'b2b-orders', name: 'admin-b2b-orders', component: () => import('@/views/Admin/Orders/B2bOrderList.vue') },
        
        // B2B & RFQ
        { path: 'b2b-accounts', name: 'admin-b2b-accounts', component: () => import('@/views/Admin/B2b/B2bAccounts.vue') },
        { path: 'rfq', name: 'admin-rfq', component: () => import('@/views/Admin/B2b/RfqManagement.vue') },
        
        // Garages
        { path: 'garages', name: 'admin-garages', component: () => import('@/views/Admin/Garage/GarageList.vue') },
        { path: 'bookings', name: 'admin-bookings', component: () => import('@/views/Admin/Garage/BookingManagement.vue') },
        
        // Customers
        { path: 'customers', name: 'admin-customers', component: () => import('@/views/Admin/Customers/CustomerList.vue') },
        { path: 'customers/:id', name: 'admin-customer-detail', component: () => import('@/views/Admin/Customers/CustomerDetail.vue') },
        
        // Finance
        { path: 'payments', name: 'admin-payments', component: () => import('@/views/Admin/Finance/PaymentManagement.vue') },
        
        // Reviews
        { path: 'reviews', name: 'admin-reviews', component: () => import('@/views/Admin/Reviews/ReviewManagement.vue') },
        
        // Promotions
        { path: 'coupons', name: 'admin-coupons', component: () => import('@/views/Admin/Promotions/CouponList.vue') },
        
        // CMS
        { path: 'banners', name: 'admin-banners', component: () => import('@/views/Admin/CMS/BannerManager.vue') },
        { path: 'pages', name: 'admin-pages', component: () => import('@/views/Admin/CMS/PageManager.vue') },
        { path: 'faq', name: 'admin-faq', component: () => import('@/views/Admin/CMS/FaqManager.vue') },
        
        // Reports
        { path: 'reports', name: 'admin-reports', component: () => import('@/views/Admin/Reports/ReportsDashboard.vue') },
        
        // Settings
        { path: 'settings', name: 'admin-settings', component: () => import('@/views/Admin/Settings/SettingsPage.vue') },
        { path: 'users', name: 'admin-users', component: () => import('@/views/Admin/Settings/AdminUsers.vue') },
      ],
    },

    // Admin Login
    { path: '/admin/login', name: 'admin-login', component: () => import('@/views/Admin/Auth/AdminLogin.vue'), meta: { guestOnly: true } },

    // 404
    { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/views/NotFound.vue') },
  ],
})

// Navigation Guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  if (!authStore.user && authStore.token) {
    await authStore.fetchMe()
  }
  
  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }
  
  if (to.meta.guestOnly && authStore.isLoggedIn) {
    return next({ name: 'home' })
  }
  
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    if (!authStore.isLoggedIn) {
      return next({ name: 'admin-login', query: { redirect: to.fullPath } })
    }
    return next({ name: 'home' })
  }
  
  next()
})

export default router
