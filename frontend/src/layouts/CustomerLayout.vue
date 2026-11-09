<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useWishlistStore } from '@/stores/wishlist'
import { useLanguageStore } from '@/stores/language'
import {
  Flame,
  Building2,
  User as UserIcon,
  User,
  MapPin,
  Truck,
  Car,
  Search,
  Heart,
  ShoppingBag,
  ShoppingCart,
  Plus,
  ShieldCheck,
  Phone,
  Mail,
  Clock,
  ChevronDown,
  Wrench as WrenchIcon,
  Wrench,
  UserPlus,
  LogOut,
  LogIn,
  Menu,
  X,
  Sparkles,
  Layers,
  Globe,
  LayoutDashboard,
  Package,
  FileText,
  SlidersHorizontal,
  Disc,
  Briefcase,
  Home,
  Shield
} from 'lucide-vue-next'
import { formatImageUrl, handleAvatarError, DEFAULT_USER_AVATAR } from '@/utils/image'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()
const langStore = useLanguageStore()

onMounted(() => {
  wishlistStore.fetchWishlist()
  // Global click listener to close dropdowns
  window.addEventListener('click', () => {
    isLangDropdownOpen.value = false
    isVehicleDropdownOpen.value = false
    isUserMenuOpen.value = false
  })
})

const searchQuery = ref('')
const selectedVehicle = ref('Select Vehicle')
const isVehicleDropdownOpen = ref(false)
const isUserMenuOpen = ref(false)
const isMobileDrawerOpen = ref(false)
const isLangDropdownOpen = ref(false)

const selectLanguage = (code: any) => {
  langStore.setLanguage(code)
  isLangDropdownOpen.value = false
}

const handleCartClick = () => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login?redirect=/cart')
  } else {
    router.push('/cart')
  }
}

const handleLogout = async () => {
  await authStore.logout()
  isUserMenuOpen.value = false
  isMobileDrawerOpen.value = false
  router.push('/')
}

const savedVehicles = ref([
  { id: 1, make: 'Honda', model: 'CB650R Neo Sports', year: 2023, type: 'motorcycle' },
  { id: 2, make: 'Ford', model: 'Ranger Raptor 3.0 V6', year: 2024, type: 'car' },
  { id: 3, make: 'Yamaha', model: 'YZF-R7 Track Spec', year: 2022, type: 'motorcycle' },
])

const selectActiveVehicle = (v: any) => {
  selectedVehicle.value = `${v.make} ${v.model} (${v.year})`
  isVehicleDropdownOpen.value = false
}

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    isMobileDrawerOpen.value = false
    router.push({ name: 'shop', query: { q: searchQuery.value } })
  }
}
</script>

<template>
  <div class="customer-layout">
    <!-- Top Announcement & B2B Switcher Bar -->
    <div class="top-announcement-bar">
      <div class="container top-bar-content">
        <div class="announcement-left">
          <span class="live-dot"></span>
          <Flame class="icon-flame" :size="14" />
          <span class="announce-text">{{ langStore.t.announcement }}</span>
        </div>

        <div class="announcement-right">
          <!-- B2C / B2B Mode Switcher -->
          <div 
            class="b2b-toggle-container"
            :class="{ 'mode-b2b': authStore.isB2B }"
            @click="authStore.setMode(authStore.isB2B ? 'b2c' : 'b2b')"
            title="Toggle between Retail Shopper and Garage Wholesale Mode"
          >
            <div class="mode-icon-wrapper">
              <Building2 v-if="authStore.isB2B" :size="14" />
              <UserIcon v-else :size="14" />
            </div>
            <span class="mode-label">
              {{ authStore.isB2B ? langStore.t.wholesaleB2B : langStore.t.retailB2C }}
            </span>
            <span class="mode-pill-action">
              {{ authStore.isB2B ? langStore.t.switchToB2C : langStore.t.switchToB2B }}
            </span>
          </div>

          <!-- Language Switcher Dropdown (Khmer / Chinese / English) -->
          <div class="lang-dropdown-wrapper" @click.stop="isLangDropdownOpen = !isLangDropdownOpen">
            <div class="lang-pill-btn" title="Change Language / ផ្លាស់ប្ដូរភាសា / 切换语言">
              <Globe :size="13" class="text-orange" />
              <span class="lang-flag">{{ langStore.currentOption.flag }}</span>
              <span class="lang-label">{{ langStore.currentOption.nativeName }}</span>
              <ChevronDown :size="11" class="arrow-icon" :class="{ open: isLangDropdownOpen }" />
            </div>

            <div v-if="isLangDropdownOpen" class="lang-dropdown-menu glass-card" @click.stop>
              <div 
                v-for="opt in langStore.options" 
                :key="opt.code"
                class="lang-item"
                :class="{ active: langStore.currentLang === opt.code }"
                @click="selectLanguage(opt.code)"
              >
                <span class="lang-item-flag">{{ opt.flag }}</span>
                <span class="lang-item-native">{{ opt.nativeName }}</span>
                <span class="lang-item-sub">({{ opt.label }})</span>
              </div>
            </div>
          </div>

          <div class="top-nav-links">
            <router-link to="/garages" class="top-link">
              <MapPin :size="14" />
              <span>{{ langStore.t.partnerGarages }}</span>
            </router-link>
            <router-link to="/track" class="top-link">
              <Truck :size="14" />
              <span>{{ langStore.t.trackOrder }}</span>
            </router-link>
            
            <div class="auth-box">
              <template v-if="!authStore.isAuthenticated">
                <router-link to="/auth/login" class="top-link auth-pill">
                  <UserIcon :size="13" />
                  <span>{{ langStore.t.signIn }}</span>
                </router-link>
                <router-link to="/auth/register" class="top-link auth-pill register-pill">
                  <UserPlus :size="13" />
                  <span>{{ langStore.t.register }}</span>
                </router-link>
              </template>

              <!-- Logged-in User Profile Dropdown -->
              <div v-else class="user-menu-wrapper" @click.stop="isUserMenuOpen = !isUserMenuOpen">
                <div class="user-pill-btn">
                  <img 
                    v-if="authStore.user?.avatar_url" 
                    :src="formatImageUrl(authStore.user.avatar_url, DEFAULT_USER_AVATAR)" 
                    class="user-avatar" 
                    @error="handleAvatarError($event, DEFAULT_USER_AVATAR)"
                  />
                  <UserIcon v-else :size="14" class="text-orange" />
                  <span class="user-name">{{ authStore.user?.name?.split(' ')[0] || 'User' }}</span>
                  <span class="user-badge" :class="authStore.isB2B ? 'b2b-badge' : 'b2c-badge'">
                    {{ authStore.isAdmin ? 'ADMIN' : (authStore.isB2B ? 'B2B' : 'VIP') }}
                  </span>
                  <ChevronDown :size="12" class="arrow-icon" :class="{ open: isUserMenuOpen }" />
                </div>

                <!-- Dropdown Card -->
                <div v-if="isUserMenuOpen" class="user-dropdown-card glass-card" @click.stop>
                  <div class="dropdown-user-top">
                    <img 
                      v-if="authStore.user?.avatar_url" 
                      :src="formatImageUrl(authStore.user.avatar_url, DEFAULT_USER_AVATAR)" 
                      class="large-avatar" 
                      @error="handleAvatarError($event, DEFAULT_USER_AVATAR)"
                    />
                    <div>
                      <strong>{{ authStore.user?.name }}</strong>
                      <small>{{ authStore.user?.email }}</small>
                    </div>
                  </div>
                  <div class="dropdown-menu-list">
                    <router-link to="/account/dashboard" class="menu-item" @click="isUserMenuOpen = false">
                      <LayoutDashboard :size="15" />
                      <span>Dashboard</span>
                    </router-link>
                    <router-link to="/account/garage" class="menu-item" @click="isUserMenuOpen = false">
                      <Car :size="15" />
                      <span>My Garage Vehicles</span>
                    </router-link>
                    <router-link to="/account/orders" class="menu-item" @click="isUserMenuOpen = false">
                      <Package :size="15" />
                      <span>Orders & Tracking</span>
                    </router-link>
                    <router-link v-if="authStore.isB2B" to="/account/rfq" class="menu-item text-cyan" @click="isUserMenuOpen = false">
                      <FileText :size="15" />
                      <span>B2B RFQ Quotes</span>
                    </router-link>
                    <router-link v-if="authStore.isAdmin" to="/admin/dashboard" class="menu-item text-orange" @click="isUserMenuOpen = false">
                      <ShieldCheck :size="15" />
                      <span>Admin Portal</span>
                    </router-link>
                    <div class="dropdown-divider"></div>
                    <button type="button" class="menu-item logout-action" @click="handleLogout">
                      <LogOut :size="14" />
                      <span>Log Out</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="main-header glass-panel">
      <div class="container header-container">
        <!-- Brand Logo -->
        <router-link to="/" class="brand-logo">
          <div class="logo-mark">
            <img src="/images/logo.png" alt="MotoCar Custom" class="brand-logo-img" />
          </div>
          <div class="logo-text">
            <span class="brand-part-1">MOTO</span><span class="brand-part-2">CAR</span>
            <span class="brand-badge">CUSTOM</span>
          </div>
        </router-link>

        <!-- Vehicle Fitment Filter Bar Widget -->
        <div class="vehicle-fitment-bar" @click.stop="isVehicleDropdownOpen = !isVehicleDropdownOpen">
          <div class="fitment-icon-box">
            <Car :size="18" class="text-orange" />
          </div>
          <div class="fitment-details">
            <span class="fitment-tag">
              <span class="pulse-indicator"></span>
              {{ langStore.t.fitmentEngine }}
            </span>
            <span class="fitment-active-name">{{ selectedVehicle }}</span>
          </div>
          <div class="fitment-add-btn" @click.stop="router.push('/account/garage')">
            <Plus :size="13" />
            <span>{{ langStore.t.addVehicle }}</span>
          </div>
          <ChevronDown :size="15" class="dropdown-arrow" :class="{ open: isVehicleDropdownOpen }" />

          <!-- Saved Vehicles Dropdown -->
          <div v-if="isVehicleDropdownOpen" class="vehicle-dropdown glass-card" @click.stop>
            <div class="dropdown-header">
              <span>Select Your Garage Vehicle:</span>
              <router-link to="/account/garage" class="manage-link">Manage ({{ savedVehicles.length }})</router-link>
            </div>
            <div class="dropdown-list">
              <div 
                v-for="v in savedVehicles" 
                :key="v.id" 
                class="dropdown-vehicle-item"
                @click="selectActiveVehicle(v)"
              >
                <div class="item-icon">
                  <Car v-if="v.type === 'car'" :size="16" />
                  <Sparkles v-else :size="16" />
                </div>
                <div class="item-text">
                  <strong>{{ v.make }} {{ v.model }}</strong>
                  <small>{{ v.year }} • Verified Fitment</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar-wrapper">
          <div class="search-input-box">
            <Search :size="18" class="search-icon" />
            <input 
              v-model="searchQuery" 
              type="text" 
              :placeholder="langStore.t.searchPlaceholder"
              @keyup.enter="handleSearch"
            />
            <span class="kbd-shortcut">⌘K</span>
            <button class="search-action-btn" @click="handleSearch">
              {{ langStore.t.searchBtn }}
            </button>
          </div>
        </div>

        <!-- Header Actions -->
        <div class="header-action-group">
          <!-- Wishlist with reactive store badge -->
          <router-link to="/wishlist" class="action-btn hide-on-mobile wishlist-nav-btn" :title="`Saved Wishlist (${wishlistStore.itemsCount} items)`">
            <div class="wishlist-icon-wrapper">
              <Heart :size="20" :fill="wishlistStore.itemsCount > 0 ? '#ef4444' : 'none'" :class="{ 'text-red': wishlistStore.itemsCount > 0 }" />
              <span v-if="wishlistStore.itemsCount > 0" class="wishlist-count-badge">{{ wishlistStore.itemsCount }}</span>
            </div>
          </router-link>

          <!-- Cart with reactive store badge (Guarded for Authenticated Accounts only) -->
          <button 
            type="button" 
            class="action-btn cart-btn" 
            title="Shopping Cart"
            @click="handleCartClick"
          >
            <div class="cart-icon-wrapper">
              <ShoppingBag :size="20" />
              <span v-if="authStore.isAuthenticated && cartStore.totalItems > 0" class="cart-count-badge">{{ cartStore.totalItems }}</span>
            </div>
            <div class="cart-info hide-on-mobile">
              <span class="cart-lbl">{{ langStore.t.cart }}</span>
              <strong class="cart-total">${{ (authStore.isAuthenticated ? cartStore.totalAmount : 0).toLocaleString() }}</strong>
            </div>
          </button>

          <!-- Mobile Hamburger Menu Button -->
          <button 
            type="button" 
            class="action-btn mobile-menu-btn" 
            @click="isMobileDrawerOpen = !isMobileDrawerOpen"
            title="Toggle Navigation Menu"
          >
            <Menu v-if="!isMobileDrawerOpen" :size="20" />
            <X v-else :size="20" />
          </button>
        </div>
      </div>

      <!-- Categories Navigation Bar -->
      <nav class="sub-nav">
        <div class="container nav-content">
          <div class="nav-links-scroll">
            <router-link to="/" class="nav-tab">
              <span>{{ langStore.t.home }}</span>
            </router-link>
            <router-link to="/shop" class="nav-tab">
              <span>{{ langStore.t.allProducts }}</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { type: 'motorcycle' } }" class="nav-tab">
              <span class="tab-badge moto">MOTO</span>
              <span>{{ langStore.t.motoCustom }}</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { type: 'car' } }" class="nav-tab">
              <span class="tab-badge car">AUTO</span>
              <span>{{ langStore.t.carCustom }}</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { category: 'exhaust-systems' } }" class="nav-tab">
              <span>{{ langStore.t.exhaustSystems }}</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { category: 'suspension-coilovers' } }" class="nav-tab">
              <span>{{ langStore.t.suspension }}</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { category: 'performance-brakes' } }" class="nav-tab">
              <span>{{ langStore.t.brakes }}</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { category: 'forged-wheels' } }" class="nav-tab">
              <span>{{ langStore.t.forgedWheels }}</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { category: 'carbon-aerodynamics' } }" class="nav-tab">
              <span>{{ langStore.t.carbonAero }}</span>
            </router-link>
            <router-link to="/garages" class="nav-tab highlight-tab">
              <WrenchIcon :size="14" class="tab-icon" />
              <span>{{ langStore.t.partnerGarages }}</span>
            </router-link>
            <router-link to="/shop?b2b=true" class="nav-tab b2b-nav-tab" :class="{ active: authStore.isB2B }">
              <Building2 :size="14" />
              <span>{{ langStore.t.wholesaleB2B }}</span>
            </router-link>
          </div>
        </div>
      </nav>
    </header>

    <!-- Mobile Navigation Drawer -->
    <transition name="drawer-fade">
      <div v-if="isMobileDrawerOpen" class="mobile-drawer-overlay" @click="isMobileDrawerOpen = false">
        <div class="mobile-drawer-card glass-panel" @click.stop>
          <div class="drawer-header">
            <div class="brand-logo">
              <div class="logo-mark">
                <img src="/images/logo.png" alt="MotoCar Custom" class="brand-logo-img" />
              </div>
              <div class="logo-text">
                <span class="brand-part-1">MOTO</span><span class="brand-part-2">CAR</span>
              </div>
            </div>
            <button class="btn-close-drawer" @click="isMobileDrawerOpen = false">&times;</button>
          </div>

          <!-- Mode Switcher in Drawer -->
          <div 
            class="drawer-mode-toggle"
            :class="{ 'mode-b2b': authStore.isB2B }"
            @click="authStore.setMode(authStore.isB2B ? 'b2c' : 'b2b')"
          >
            <Building2 v-if="authStore.isB2B" :size="16" />
            <UserIcon v-else :size="16" />
            <span>{{ authStore.isB2B ? langStore.t.wholesaleB2B : langStore.t.retailB2C }}</span>
          </div>

          <!-- Language Selector in Drawer -->
          <div class="drawer-lang-selector">
            <span class="drawer-lang-lbl"><Globe :size="14" class="text-orange" /> ភាសា / Language:</span>
            <div class="drawer-lang-options">
              <button 
                v-for="opt in langStore.options" 
                :key="opt.code"
                class="drawer-lang-btn"
                :class="{ active: langStore.currentLang === opt.code }"
                @click="selectLanguage(opt.code)"
              >
                {{ opt.flag }} {{ opt.nativeName }}
              </button>
            </div>
          </div>

          <!-- Mobile Search Input -->
          <div class="drawer-search">
            <Search :size="15" class="text-orange" />
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Search Akrapovic, Brembo..."
              @keyup.enter="handleSearch"
            />
          </div>

          <!-- Drawer Navigation Links -->
          <div class="drawer-nav-list">
            <router-link to="/" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <Home :size="16" />
              <span>Home Showroom</span>
            </router-link>
            <router-link to="/shop" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <ShoppingBag :size="16" />
              <span>All Performance Parts</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { type: 'motorcycle' } }" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <Layers :size="16" />
              <span>Motorcycle Custom</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { type: 'car' } }" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <Car :size="16" />
              <span>Car Custom</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { category: 'exhaust' } }" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <Flame :size="16" />
              <span>Exhaust Systems</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { category: 'suspension' } }" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <SlidersHorizontal :size="16" />
              <span>Suspension & Coilovers</span>
            </router-link>
            <router-link :to="{ name: 'shop', query: { category: 'brakes' } }" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <Disc :size="16" />
              <span>Performance Brakes</span>
            </router-link>
            <router-link to="/shop?b2b=true" class="drawer-nav-item text-cyan" @click="isMobileDrawerOpen = false">
              <Briefcase :size="16" />
              <span>B2B Wholesale Catalog</span>
            </router-link>
            <router-link to="/garages" class="drawer-nav-item text-orange" @click="isMobileDrawerOpen = false">
              <Wrench :size="16" />
              <span>Certified Partner Garages</span>
            </router-link>
            <router-link to="/wishlist" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <Heart :size="16" />
              <span>Saved Wishlist ({{ wishlistStore.itemsCount }})</span>
            </router-link>
            <router-link to="/track" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
              <Package :size="16" />
              <span>Track Your Order</span>
            </router-link>

            <div class="drawer-divider"></div>

            <!-- Auth Options -->
            <template v-if="!authStore.isAuthenticated">
              <router-link to="/auth/login" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
                <LogIn :size="16" />
                <span>Sign In</span>
              </router-link>
              <router-link to="/auth/register" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
                <UserPlus :size="16" />
                <span>Register Free Account</span>
              </router-link>
            </template>
            <template v-else>
              <router-link to="/account/dashboard" class="drawer-nav-item" @click="isMobileDrawerOpen = false">
                <User :size="16" />
                <span>My Account ({{ authStore.user?.name }})</span>
              </router-link>
              <router-link v-if="authStore.isAdmin" to="/admin/dashboard" class="drawer-nav-item text-orange" @click="isMobileDrawerOpen = false">
                <ShieldCheck :size="16" />
                <span>Admin Command Portal</span>
              </router-link>
              <button class="drawer-nav-item drawer-logout" @click="handleLogout">
                <LogOut :size="16" />
                <span>Sign Out</span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </transition>

    <!-- Main View Content Area -->
    <main class="page-content">
      <router-view />
    </main>

    <!-- Mobile Bottom App Bar (Optimized for Mobile Phone UX) -->
    <nav class="mobile-bottom-bar">
      <router-link to="/" class="bottom-bar-item" active-class="active">
        <Home :size="20" class="bottom-bar-icon" />
        <span class="bottom-bar-lbl">Home</span>
      </router-link>
      <router-link to="/shop" class="bottom-bar-item" active-class="active">
        <ShoppingBag :size="20" class="bottom-bar-icon" />
        <span class="bottom-bar-lbl">Catalog</span>
      </router-link>
      <router-link to="/cart" class="bottom-bar-item bottom-bar-cart" active-class="active">
        <div class="bottom-cart-wrapper">
          <ShoppingCart :size="20" class="bottom-bar-icon" />
          <span v-if="cartStore.totalItems > 0" class="bottom-cart-badge">{{ cartStore.totalItems }}</span>
        </div>
        <span class="bottom-bar-lbl">Cart</span>
      </router-link>
      <router-link to="/garages" class="bottom-bar-item" active-class="active">
        <Wrench :size="20" class="bottom-bar-icon" />
        <span class="bottom-bar-lbl">Garages</span>
      </router-link>
      <router-link :to="authStore.isAuthenticated ? '/account/dashboard' : '/auth/login'" class="bottom-bar-item" active-class="active">
        <User :size="20" class="bottom-bar-icon" />
        <span class="bottom-bar-lbl">{{ authStore.isAuthenticated ? 'Account' : 'Login' }}</span>
      </router-link>
    </nav>

    <!-- Modern Luxury Footer -->
    <footer class="site-footer">
      <div class="footer-glow"></div>
      <div class="container footer-grid">
        <!-- Brand Info -->
        <div class="footer-brand">
          <div class="brand-logo mb-4">
            <div class="logo-mark">
              <img src="/images/logo.png" alt="MotoCar Custom" class="brand-logo-img" />
            </div>
            <div class="logo-text">
              <span class="brand-part-1">MOTO</span><span class="brand-part-2">CAR</span>
              <span class="brand-badge">CUSTOM</span>
            </div>
          </div>
          <p class="brand-description">
            Cambodia's premier digital distribution workshop for authentic supercar and superbike tuning parts, certified fitment guarantee, and nationwide garage installations.
          </p>
          <div class="secure-badge">
            <ShieldCheck :size="18" class="text-green" />
            <span>100% Genuine Certified & Guaranteed Fitment</span>
          </div>

          <div class="payment-badges">
            <span class="payment-chip">Bakong KHQR</span>
            <span class="payment-chip">ABA PAY</span>
            <span class="payment-chip">Visa / Master</span>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-links-col">
          <h4 class="col-title">Customer Care</h4>
          <ul>
            <li><router-link to="/shop">Shop Performance Parts</router-link></li>
            <li><router-link to="/garages">Find Certified Partner Garage</router-link></li>
            <li><router-link to="/track">Track Live Delivery</router-link></li>
            <li><router-link to="/faq">Vehicle Compatibility Check</router-link></li>
            <li><router-link to="/about">About MotoCar Custom</router-link></li>
          </ul>
        </div>

        <!-- B2B Garages Network -->
        <div class="footer-links-col">
          <h4 class="col-title">B2B Garage Network</h4>
          <ul>
            <li><router-link to="/auth/register?type=b2b">Apply for Wholesale Tier</router-link></li>
            <li><router-link to="/account/rfq">Submit RFQ Volume Quote</router-link></li>
            <li><router-link to="/shop?b2b=true">Wholesale Price List</router-link></li>
            <li><router-link to="/garages">Register as Partner Garage</router-link></li>
            <li><router-link to="/admin">Staff & Garage Portal</router-link></li>
          </ul>
        </div>

        <!-- Contact Info -->
        <div class="footer-links-col contact-col">
          <h4 class="col-title">Workshop Headquarters</h4>
          <div class="contact-entry">
            <MapPin :size="16" class="contact-icon text-orange" />
            <span>#88 Russian Blvd, Toul Kork, Phnom Penh, Cambodia</span>
          </div>
          <div class="contact-entry">
            <Phone :size="16" class="contact-icon text-orange" />
            <span>(+855) 23 888 999 / (+855) 70 888 999</span>
          </div>
          <div class="contact-entry">
            <Mail :size="16" class="contact-icon text-orange" />
            <span>vip@motocarcustom.com</span>
          </div>
          <div class="contact-entry">
            <Clock :size="16" class="contact-icon text-orange" />
            <span>Mon - Sat: 08:00 AM - 06:30 PM (ICT)</span>
          </div>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom-bar">
        <div class="container bottom-inner">
          <p>© 2026 MotoCar Custom Co., Ltd. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.customer-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #05070b;
  color: #f1f5f9;
}

.container {
  width: 100%;
  max-width: 1360px;
  margin-left: auto !important;
  margin-right: auto !important;
  padding: 0 1.25rem;
}

/* TOP ANNOUNCEMENT BAR */
.top-announcement-bar {
  background: #080c14;
  border-bottom: 1px solid rgba(255, 255, 255, 0.07);
  font-size: 0.82rem;
  padding: 0.45rem 0;
  position: relative;
  z-index: 60;
}

.top-bar-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.announcement-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #94a3b8;
}

.live-dot {
  width: 6px;
  height: 6px;
  background: #22c55e;
  border-radius: 50%;
  box-shadow: 0 0 8px #22c55e;
  animation: pulseGlow 1.8s infinite;
}

.icon-flame {
  color: var(--orange-500);
}

.announcement-right {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

/* B2B / B2C Toggle Button */
.b2b-toggle-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.25s ease;
  user-select: none;
}

.b2b-toggle-container:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(249, 115, 22, 0.4);
}

.b2b-toggle-container.mode-b2b {
  background: rgba(2, 132, 199, 0.2);
  border-color: rgba(56, 189, 248, 0.5);
  box-shadow: 0 0 15px rgba(2, 132, 199, 0.25);
}

.mode-icon-wrapper {
  color: #ff7834;
  display: flex;
}

.mode-b2b .mode-icon-wrapper {
  color: #38bdf8;
}

.mode-label {
  font-weight: 600;
  font-size: 0.78rem;
  color: #e2e8f0;
}

.mode-pill-action {
  background: rgba(255, 255, 255, 0.1);
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  color: #94a3b8;
  font-weight: 500;
}

/* LANGUAGE SWITCHER */
.lang-dropdown-wrapper {
  position: relative;
  cursor: pointer;
  user-select: none;
}

.lang-pill-btn {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.15);
  padding: 0.22rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.76rem;
  font-weight: 600;
  color: #f1f5f9;
  transition: all 0.2s ease;
}

.lang-pill-btn:hover {
  border-color: var(--orange-500);
  background: rgba(249, 115, 22, 0.12);
  color: #ff7834;
}

.lang-flag {
  font-size: 0.92rem;
  line-height: 1;
}

.lang-label {
  letter-spacing: 0.02em;
}

.lang-dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 175px;
  background: #0b1120;
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 10px;
  padding: 0.35rem;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.8), 0 0 15px rgba(249, 115, 22, 0.2);
  z-index: 100;
}

.lang-item {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.5rem 0.7rem;
  border-radius: 6px;
  font-size: 0.82rem;
  color: #e2e8f0;
  cursor: pointer;
  transition: all 0.15s ease;
}

.lang-item:hover {
  background: rgba(249, 115, 22, 0.18);
  color: #ffffff;
}

.lang-item.active {
  background: rgba(249, 115, 22, 0.28);
  color: #ff7834;
  font-weight: 700;
}

.lang-item-flag {
  font-size: 1rem;
}

.lang-item-native {
  font-weight: 600;
}

.lang-item-sub {
  font-size: 0.7rem;
  color: #94a3b8;
  margin-left: auto;
}

.top-nav-links {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.top-link {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  color: #94a3b8;
  font-size: 0.8rem;
  transition: color 0.2s;
}

.top-link:hover {
  color: var(--orange-500);
}

.auth-box {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.auth-pill {
  background: rgba(249, 115, 22, 0.12);
  border: 1px solid rgba(249, 115, 22, 0.35);
  color: #ff7834;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-weight: 700;
  font-size: 0.78rem;
}

.auth-pill:hover {
  background: var(--orange-500);
  color: white;
}

.register-pill {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.16);
  color: #f1f5f9;
}

.register-pill:hover {
  background: rgba(255, 255, 255, 0.16);
  color: white;
}

/* User Account Menu */
.user-menu-wrapper {
  position: relative;
  cursor: pointer;
}

.user-pill-btn {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(249, 115, 22, 0.15);
  border: 1px solid rgba(249, 115, 22, 0.4);
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  transition: all 0.2s;
}

.user-pill-btn:hover {
  background: rgba(249, 115, 22, 0.25);
}

.user-avatar {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid var(--orange-500);
}

.user-name {
  font-size: 0.8rem;
  font-weight: 700;
  color: #ffffff;
}

.user-badge {
  font-size: 0.62rem;
  font-weight: 800;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
}

.b2c-badge {
  background: rgba(249, 115, 22, 0.3);
  color: #ff7834;
}

.b2b-badge {
  background: rgba(0, 210, 255, 0.25);
  color: #00d2ff;
}

.arrow-icon {
  color: #94a3b8;
  transition: transform 0.2s;
}

.arrow-icon.open {
  transform: rotate(180deg);
}

/* Dropdown Card */
.user-dropdown-card {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 240px;
  background: #0d121d;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  padding: 0.75rem;
  z-index: 100;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.8), 0 0 20px rgba(249, 115, 22, 0.15);
}

.dropdown-user-top {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding-bottom: 0.6rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 0.4rem;
}

.large-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  object-fit: cover;
  border: 1.5px solid var(--orange-500);
}

.dropdown-user-top strong {
  display: block;
  font-size: 0.85rem;
  color: white;
}

.dropdown-user-top small {
  display: block;
  font-size: 0.72rem;
  color: #94a3b8;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 170px;
}

.dropdown-menu-list {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.65rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #cbd5e1;
  text-decoration: none;
  background: transparent;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s;
}

.menu-item:hover {
  background: rgba(255, 255, 255, 0.08);
  color: white;
}

.highlight-b2b {
  color: #38bdf8;
}

.highlight-admin {
  color: #ff7834;
}

.dropdown-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 0.35rem 0;
}

.logout-action {
  color: #f87171;
}

.logout-action:hover {
  background: rgba(239, 68, 68, 0.15);
  color: #fca5a5;
}

/* MAIN HEADER */
.main-header {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(10, 14, 23, 0.92);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.header-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
  padding-top: 1rem;
  padding-bottom: 1rem;
}

/* LOGO */
.brand-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo-mark {
  width: 42px;
  height: 42px;
  background: #000000;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  box-shadow: 0 4px 18px rgba(249, 115, 22, 0.45);
  border: 1px solid rgba(249, 115, 22, 0.5);
  flex-shrink: 0;
}

.brand-logo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.brand-logo:hover .brand-logo-img {
  transform: scale(1.1);
}

.logo-text {
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 1.45rem;
  letter-spacing: -0.04em;
  display: flex;
  align-items: baseline;
}

.brand-part-1 {
  color: #ffffff;
}

.brand-part-2 {
  color: var(--orange-500);
}

.brand-badge {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  background: rgba(0, 210, 255, 0.15);
  border: 1px solid rgba(0, 210, 255, 0.4);
  color: #00d2ff;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  margin-left: 0.4rem;
  transform: translateY(-2px);
}

/* VEHICLE FITMENT BAR WIDGET */
.vehicle-fitment-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(249, 115, 22, 0.35);
  border-radius: 10px;
  padding: 0.45rem 0.85rem;
  cursor: pointer;
  position: relative;
  transition: all 0.25s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.vehicle-fitment-bar:hover {
  border-color: var(--orange-500);
  box-shadow: 0 4px 20px rgba(249, 115, 22, 0.2);
}

.fitment-icon-box {
  width: 32px;
  height: 32px;
  background: rgba(249, 115, 22, 0.12);
  border-radius: 7px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.text-orange {
  color: var(--orange-500);
}

.fitment-details {
  display: flex;
  flex-direction: column;
}

.fitment-tag {
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #ff7834;
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.pulse-indicator {
  width: 5px;
  height: 5px;
  background: #22c55e;
  border-radius: 50%;
  display: inline-block;
}

.fitment-active-name {
  font-weight: 700;
  font-size: 0.85rem;
  color: #ffffff;
  white-space: nowrap;
}

.fitment-add-btn {
  background: var(--orange-500);
  color: white;
  display: flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0.25rem 0.55rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  transition: background 0.2s;
}

.fitment-add-btn:hover {
  background: #e04b00;
}

.dropdown-arrow {
  color: #94a3b8;
  transition: transform 0.2s;
}

.dropdown-arrow.open {
  transform: rotate(180deg);
}

/* Dropdown */
.vehicle-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: 310px;
  background: #0d121d;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  padding: 0.8rem;
  z-index: 100;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.7);
}

.dropdown-header {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #94a3b8;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 0.5rem;
}

.manage-link {
  color: var(--orange-500);
  font-weight: 600;
}

.dropdown-vehicle-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.dropdown-vehicle-item:hover {
  background: rgba(255, 255, 255, 0.06);
}

.item-icon {
  width: 30px;
  height: 30px;
  background: rgba(249, 115, 22, 0.15);
  border-radius: 6px;
  color: var(--orange-500);
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-text {
  display: flex;
  flex-direction: column;
}

.item-text strong {
  font-size: 0.82rem;
  color: white;
}

.item-text small {
  font-size: 0.7rem;
  color: #38bdf8;
}

/* SEARCH BAR */
.search-bar-wrapper {
  flex: 1;
  max-width: 520px;
}

.search-input-box {
  display: flex;
  align-items: center;
  position: relative;
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  padding: 0.25rem 0.35rem 0.25rem 0.9rem;
  transition: all 0.25s ease;
}

.search-input-box:focus-within {
  border-color: var(--orange-500);
  box-shadow: 0 0 15px rgba(249, 115, 22, 0.25);
}

.search-icon {
  color: #64748b;
  margin-right: 0.6rem;
}

.search-input-box input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #ffffff;
  font-size: 0.88rem;
  font-family: inherit;
}

.search-input-box input::placeholder {
  color: #64748b;
}

.kbd-shortcut {
  font-size: 0.7rem;
  color: #64748b;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  margin-right: 0.5rem;
}

.search-action-btn {
  background: var(--orange-500);
  color: white;
  border: none;
  font-weight: 700;
  font-size: 0.8rem;
  padding: 0.45rem 1rem;
  border-radius: 7px;
  transition: background 0.2s;
}

.search-action-btn:hover {
  background: #e04b00;
}

/* HEADER ACTION ICONS */
.header-action-group {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.action-btn {
  width: 42px;
  height: 42px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
  position: relative;
  transition: all 0.2s;
}

.action-btn:hover {
  color: var(--orange-500);
  background: rgba(249, 115, 22, 0.1);
  border-color: rgba(249, 115, 22, 0.4);
}

.badge-dot {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 6px;
  height: 6px;
  background: var(--orange-500);
  border-radius: 50%;
}

.wishlist-icon-wrapper {
  position: relative;
  display: flex;
}

.wishlist-count-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 800;
  width: 17px;
  height: 17px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);
  animation: heartPulse 2s infinite ease-in-out;
}

@keyframes heartPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.15); }
}

.text-red {
  color: #ef4444 !important;
}

.cart-btn {
  width: auto;
  padding: 0.4rem 0.9rem;
  gap: 0.75rem;
}

.cart-icon-wrapper {
  position: relative;
  display: flex;
}

.cart-count-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: var(--orange-500);
  color: white;
  font-size: 0.65rem;
  font-weight: 800;
  width: 17px;
  height: 17px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cart-info {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.cart-lbl {
  font-size: 0.65rem;
  color: #94a3b8;
  line-height: 1;
}

.cart-total {
  font-size: 0.85rem;
  font-weight: 800;
  color: #ffffff;
}

/* SUB NAVIGATION BAR */
.sub-nav {
  background: #080c14;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.nav-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-links-scroll {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  overflow-x: auto;
  padding: 0.45rem 0;
}

.nav-tab {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.85rem;
  border-radius: 7px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #94a3b8;
  white-space: nowrap;
  transition: all 0.2s;
}

.nav-tab:hover, .nav-tab.router-link-active {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.06);
}

.tab-badge {
  font-size: 0.65rem;
  font-weight: 800;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
}

.tab-badge.moto {
  background: rgba(249, 115, 22, 0.2);
  color: #ff7834;
}

.tab-badge.car {
  background: rgba(0, 210, 255, 0.2);
  color: #00d2ff;
}

.b2b-nav-tab {
  background: rgba(2, 132, 199, 0.15);
  border: 1px solid rgba(56, 189, 248, 0.35);
  color: #38bdf8 !important;
}

.b2b-nav-tab:hover, .b2b-nav-tab.active {
  background: #0284c7 !important;
  color: #ffffff !important;
  box-shadow: 0 0 15px rgba(2, 132, 199, 0.4);
}

/* PAGE CONTENT */
.page-content {
  flex: 1;
}

/* SITE FOOTER */
.site-footer {
  background: #030508;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  position: relative;
  overflow: hidden;
  margin-top: 5rem;
  padding-top: 4rem;
}

.footer-glow {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 80%;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(249, 115, 22, 0.6), rgba(0, 210, 255, 0.6), transparent);
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.8fr 1fr 1fr 1.3fr;
  gap: 3rem;
  padding-bottom: 3.5rem;
}

.brand-description {
  color: #94a3b8;
  font-size: 0.88rem;
  line-height: 1.6;
  margin-top: 1rem;
  margin-bottom: 1.25rem;
}

.secure-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.82rem;
  color: #22c55e;
  font-weight: 600;
  margin-bottom: 1.25rem;
}

.payment-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.payment-chip {
  background: #0c121e;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  font-size: 0.72rem;
  padding: 0.3rem 0.65rem;
  border-radius: 4px;
}

.col-title {
  color: #ffffff;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  margin-bottom: 1.25rem;
}

.footer-links-col ul {
  list-style: none;
}

.footer-links-col li {
  margin-bottom: 0.7rem;
}

.footer-links-col a {
  color: #94a3b8;
  font-size: 0.86rem;
  transition: color 0.2s;
}

.footer-links-col a:hover {
  color: var(--orange-500);
}

.contact-entry {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  font-size: 0.85rem;
  color: #94a3b8;
  margin-bottom: 0.85rem;
}

.contact-icon {
  margin-top: 0.2rem;
  flex-shrink: 0;
}

.footer-bottom-bar {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding: 1.25rem 0;
  font-size: 0.8rem;
  color: #64748b;
}

.bottom-inner {
  display: flex;
  justify-content: center;
  align-items: center;
  text-align: center;
}

@media (max-width: 1024px) {
  .header-container {
    flex-wrap: wrap;
  }
  .search-bar-wrapper {
    order: 3;
    max-width: 100%;
    width: 100%;
  }
  .footer-grid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 640px) {
  .footer-grid {
    grid-template-columns: 1fr;
  }
  .announcement-right {
    display: none;
  }
}

/* Mobile Menu Toggle Button */
.mobile-menu-btn {
  display: none;
}

/* Mobile Drawer */
.mobile-drawer-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  z-index: 999;
  display: flex;
}

.mobile-drawer-card {
  width: 320px;
  max-width: 85%;
  height: 100%;
  background: #090e18;
  border-right: 1px solid rgba(255, 255, 255, 0.12);
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  overflow-y: auto;
  box-shadow: 10px 0 35px rgba(0, 0, 0, 0.8);
}

.drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.btn-close-drawer {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 1.75rem;
  cursor: pointer;
  line-height: 1;
}

.drawer-mode-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: rgba(249, 115, 22, 0.12);
  border: 1px solid rgba(249, 115, 22, 0.35);
  color: #ff7834;
  padding: 0.65rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  margin-bottom: 1.25rem;
}

.drawer-mode-toggle.mode-b2b {
  background: rgba(2, 132, 199, 0.15);
  border-color: rgba(56, 189, 248, 0.4);
  color: #38bdf8;
}

.drawer-lang-selector {
  margin-bottom: 1.25rem;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
}

.drawer-lang-lbl {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: #94a3b8;
  margin-bottom: 0.5rem;
}

.drawer-lang-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.35rem;
}

.drawer-lang-btn {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #e2e8f0;
  padding: 0.4rem 0.2rem;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 600;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s;
}

.drawer-lang-btn.active {
  background: var(--orange-500);
  border-color: var(--orange-500);
  color: white;
}

.drawer-search {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #04070e;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  margin-bottom: 1.5rem;
}

.drawer-search input {
  background: transparent;
  border: none;
  outline: none;
  color: white;
  font-size: 0.85rem;
  width: 100%;
}

.drawer-nav-list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.drawer-nav-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.7rem 0.85rem;
  color: #cbd5e1;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.2s;
}

.drawer-nav-item:hover, .drawer-nav-item.router-link-active {
  background: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}

.drawer-logout {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #f87171 !important;
  text-align: left;
}

.drawer-divider {
  height: 1px;
  background: rgba(255, 255, 255, 0.08);
  margin: 0.75rem 0;
}

/* Mobile Bottom Navigation Bar */
.mobile-bottom-bar {
  display: none;
}

@media (max-width: 768px) {
  .mobile-menu-btn {
    display: flex;
  }

  .hide-on-mobile {
    display: none !important;
  }

  .vehicle-fitment-bar {
    display: none;
  }

  .search-bar-wrapper {
    display: none;
  }

  .main-header {
    padding: 0.5rem 0;
  }

  .header-container {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
  }

  .sub-nav {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .nav-links-scroll {
    padding: 0.4rem 1rem;
    scrollbar-width: none;
  }
  .nav-links-scroll::-webkit-scrollbar {
    display: none;
  }

  .page-content {
    padding-bottom: 65px;
  }

  /* Fixed App Bar on Mobile Phones */
  .mobile-bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: rgba(10, 14, 23, 0.96);
    backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-around;
    z-index: 998;
    box-shadow: 0 -4px 25px rgba(0, 0, 0, 0.6);
  }

  .bottom-bar-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.15rem;
    color: #94a3b8;
    text-decoration: none;
    font-size: 0.65rem;
    font-weight: 700;
    transition: color 0.2s;
    position: relative;
    padding: 0.25rem 0.5rem;
  }

  .bottom-bar-item.active {
    color: var(--orange-500);
  }

  .bottom-bar-icon {
    font-size: 1.15rem;
    line-height: 1;
  }

  .bottom-cart-wrapper {
    position: relative;
    display: inline-block;
  }

  .bottom-cart-badge {
    position: absolute;
    top: -6px;
    right: -8px;
    background: var(--orange-500);
    color: white;
    font-size: 0.6rem;
    font-weight: 800;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(249, 115, 22, 0.5);
  }
}
</style>
