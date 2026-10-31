<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'
import * as XLSX from 'xlsx'
import {
  BarChart3,
  TrendingUp,
  DollarSign,
  ShoppingBag,
  Users,
  Download,
  Calendar,
  Layers,
  MapPin,
  ArrowUpRight,
  Search,
  FileSpreadsheet,
  Eye,
  X,
  Filter,
  RefreshCw,
  CheckCircle2,
  Clock,
  AlertCircle,
  Package,
  Truck,
  ChevronRight,
  ShieldCheck
} from 'lucide-vue-next'
import { formatImageUrl, DEFAULT_PRODUCT_THUMB } from '@/utils/image'
import { subscribeToOrderEvents } from '@/utils/orderRealtime'
import { useLanguageStore } from '@/stores/language'

const langStore = useLanguageStore()

// Filter States
const selectedYear = ref<string>('2026')
const selectedMonth = ref<string>('all') // 'all' or '1'..'12'
const selectedChannel = ref<string>('all') // 'all', 'b2c', 'b2b'
const selectedStatus = ref<string>('all') // 'all', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'
const searchQuery = ref<string>('')

const isLoading = ref<boolean>(true)
const isExporting = ref<boolean>(false)
const exportSuccess = ref<string>('')

// Raw Data
const stats = ref<any>({
  total_revenue: 0,
  orders_count: 0,
  products_count: 0,
  users_count: 0,
  b2b_count: 0,
  b2c_count: 0,
  garages_count: 0
})
const liveOrders = ref<any[]>([])
const categoryBreakdown = ref<any[]>([])
const provincialDeliveries = ref<any[]>([])

// Modal Inspection
const selectedOrder = ref<any>(null)
const isModalOpen = ref<boolean>(false)

let unsubscribeEvents: (() => void) | null = null
let pollTimer: any = null

const monthsList = [
  { value: 'all', label: 'All Months (Full Year)' },
  { value: '1', label: '01 - January' },
  { value: '2', label: '02 - February' },
  { value: '3', label: '03 - March' },
  { value: '4', label: '04 - April' },
  { value: '5', label: '05 - May' },
  { value: '6', label: '06 - June' },
  { value: '7', label: '07 - July' },
  { value: '8', label: '08 - August' },
  { value: '9', label: '09 - September' },
  { value: '10', label: '10 - October' },
  { value: '11', label: '11 - November' },
  { value: '12', label: '12 - December' }
]

const yearsList = ['all', '2026', '2025', '2024']

// Fetch Live Data
const fetchReportsData = async (silent = false) => {
  if (!silent) isLoading.value = true
  try {
    const [statsRes, ordersRes] = await Promise.all([
      axios.get('/api/v1/admin/dashboard-stats'),
      axios.get('/api/v1/admin/orders')
    ])
    stats.value = statsRes.data.stats || {}
    liveOrders.value = ordersRes.data.data || []
    categoryBreakdown.value = statsRes.data.category_breakdown || []
    provincialDeliveries.value = statsRes.data.geo_breakdown || []
  } catch (err) {
    console.error('Failed to load reports data:', err)
  } finally {
    if (!silent) isLoading.value = false
  }
}

onMounted(() => {
  fetchReportsData()

  // Real-time listener for purchases or order updates
  unsubscribeEvents = subscribeToOrderEvents((event) => {
    if (event.action === 'ORDER_CREATED' || event.action === 'STATUS_UPDATED' || event.action === 'ORDER_DELETED') {
      fetchReportsData(true)
    }
  })

  // Background silent polling every 3 seconds
  pollTimer = setInterval(() => {
    fetchReportsData(true)
  }, 3000)
})

onUnmounted(() => {
  if (unsubscribeEvents) {
    unsubscribeEvents()
    unsubscribeEvents = null
  }
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
})

// Filtered Orders Computation
const filteredOrders = computed(() => {
  return liveOrders.value.filter(o => {
    const orderDate = new Date(o.created_at || o.paid_at || Date.now())
    const yearStr = String(orderDate.getFullYear())
    const monthNum = String(orderDate.getMonth() + 1)

    // Year filter
    if (selectedYear.value !== 'all' && yearStr !== selectedYear.value) {
      return false
    }

    // Month filter
    if (selectedMonth.value !== 'all' && monthNum !== selectedMonth.value) {
      return false
    }

    // Channel filter
    if (selectedChannel.value !== 'all') {
      const type = (o.order_type || 'b2c').toLowerCase()
      if (type !== selectedChannel.value) return false
    }

    // Status filter
    if (selectedStatus.value !== 'all') {
      const status = (o.status || o.order_status || '').toLowerCase()
      if (status !== selectedStatus.value) return false
    }

    // Search query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase().trim()
      const matchesNum = (o.order_number || '').toLowerCase().includes(q)
      const matchesCust = (o.customer_name || '').toLowerCase().includes(q)
      const matchesEmail = (o.customer_email || '').toLowerCase().includes(q)
      const matchesPhone = (o.customer_phone || '').toLowerCase().includes(q)
      const matchesTrack = (o.tracking_number || '').toLowerCase().includes(q)
      const matchesItems = (o.items || []).some((item: any) => 
        (item.product_name || '').toLowerCase().includes(q) || 
        (item.product_sku || '').toLowerCase().includes(q)
      )
      if (!matchesNum && !matchesCust && !matchesEmail && !matchesPhone && !matchesTrack && !matchesItems) {
        return false
      }
    }

    return true
  })
})

// Financial KPI metrics based on filtered subset
const totalFilteredGmv = computed(() => {
  return filteredOrders.value.reduce((sum, o) => sum + Number(o.total_amount || 0), 0)
})

const totalFilteredOrders = computed(() => {
  return filteredOrders.value.length
})

const totalFilteredItemsCount = computed(() => {
  return filteredOrders.value.reduce((sum, o) => {
    if (o.items && o.items.length > 0) {
      return sum + o.items.reduce((iSum: number, item: any) => iSum + Number(item.quantity || 1), 0)
    }
    return sum + (Number(o.items_count) || 1)
  }, 0)
})

const filteredAov = computed(() => {
  if (totalFilteredOrders.value === 0) return '0.00'
  return (totalFilteredGmv.value / totalFilteredOrders.value).toFixed(2)
})

// Monthly Sales Breakdown for the selected Year
const monthlyBreakdown = computed(() => {
  const currentYear = selectedYear.value === 'all' ? '2026' : selectedYear.value
  const months = [
    { num: 1, name: 'Jan', fullName: 'January' },
    { num: 2, name: 'Feb', fullName: 'February' },
    { num: 3, name: 'Mar', fullName: 'March' },
    { num: 4, name: 'Apr', fullName: 'April' },
    { num: 5, name: 'May', fullName: 'May' },
    { num: 6, name: 'Jun', fullName: 'June' },
    { num: 7, name: 'Jul', fullName: 'July' },
    { num: 8, name: 'Aug', fullName: 'August' },
    { num: 9, name: 'Sep', fullName: 'September' },
    { num: 10, name: 'Oct', fullName: 'October' },
    { num: 11, name: 'Nov', fullName: 'November' },
    { num: 12, name: 'Dec', fullName: 'December' }
  ]

  const annualTotal = liveOrders.value
    .filter(o => {
      const d = new Date(o.created_at || Date.now())
      return selectedYear.value === 'all' || String(d.getFullYear()) === currentYear
    })
    .reduce((sum, o) => sum + Number(o.total_amount || 0), 0) || 1

  return months.map(m => {
    const monthOrders = liveOrders.value.filter(o => {
      const d = new Date(o.created_at || Date.now())
      const matchYear = selectedYear.value === 'all' || String(d.getFullYear()) === currentYear
      const matchMonth = (d.getMonth() + 1) === m.num
      return matchYear && matchMonth
    })

    const revenue = monthOrders.reduce((sum, o) => sum + Number(o.total_amount || 0), 0)
    const itemsCount = monthOrders.reduce((sum, o) => {
      if (o.items && o.items.length > 0) {
        return sum + o.items.reduce((iSum: number, item: any) => iSum + Number(item.quantity || 1), 0)
      }
      return sum + (Number(o.items_count) || 1)
    }, 0)

    const share = Math.round((revenue / annualTotal) * 100)

    return {
      num: m.num,
      name: m.name,
      fullName: `${m.fullName} ${currentYear}`,
      ordersCount: monthOrders.length,
      itemsCount,
      revenue,
      share
    }
  })
})

const selectSpecificMonth = (monthNum: number) => {
  selectedMonth.value = String(monthNum)
}

const openOrderModal = (order: any) => {
  selectedOrder.value = order
  isModalOpen.value = true
}

// ==========================================
// EXCEL (.XLSX) & CSV EXPORT GENERATOR
// ==========================================

const exportToExcel = () => {
  isExporting.value = true
  try {
    const wb = XLSX.utils.book_new()

    // 1. Prepare Itemized Sales Sheet Data
    const sheetData: any[] = []

    filteredOrders.value.forEach(order => {
      const formattedDate = new Date(order.created_at).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })

      if (order.items && order.items.length > 0) {
        order.items.forEach((item: any, idx: number) => {
          sheetData.push({
            'Order Number': order.order_number,
            'Order Date': formattedDate,
            'Customer Name': order.customer_name || 'Customer',
            'Customer Email': order.customer_email || '—',
            'Customer Phone': order.customer_phone || '—',
            'Channel': (order.order_type || 'b2c').toUpperCase(),
            'Item Name': item.product_name,
            'Item SKU': item.product_sku,
            'Quantity': Number(item.quantity || 1),
            'Unit Price ($)': Number(item.unit_price || 0),
            'Line Total ($)': Number(item.total_price || (item.unit_price * item.quantity) || 0),
            'Shipping Fee ($)': idx === 0 ? Number(order.shipping_fee || 15) : 0,
            'Discount ($)': idx === 0 ? Number(order.discount_amount || 0) : 0,
            'Order Total ($)': idx === 0 ? Number(order.total_amount || 0) : '',
            'Payment Method': (order.payment_method || 'Bakong KHQR').toUpperCase(),
            'Payment Status': (order.payment_status || 'PAID').toUpperCase(),
            'Payment Reference': order.payment_reference || '—',
            'Order Status': (order.status || 'CONFIRMED').toUpperCase(),
            'Tracking Number': order.tracking_number || '—',
            'Courier Name': order.courier_name || 'Apex Express Air-Freight'
          })
        })
      } else {
        sheetData.push({
          'Order Number': order.order_number,
          'Order Date': formattedDate,
          'Customer Name': order.customer_name || 'Customer',
          'Customer Email': order.customer_email || '—',
          'Customer Phone': order.customer_phone || '—',
          'Channel': (order.order_type || 'b2c').toUpperCase(),
          'Item Name': 'Motorsport Performance Parts',
          'Item SKU': 'MCC-GEN',
          'Quantity': order.items_count || 1,
          'Unit Price ($)': Number(order.total_amount || 0),
          'Line Total ($)': Number(order.total_amount || 0),
          'Shipping Fee ($)': Number(order.shipping_fee || 15),
          'Discount ($)': Number(order.discount_amount || 0),
          'Order Total ($)': Number(order.total_amount || 0),
          'Payment Method': (order.payment_method || 'Bakong KHQR').toUpperCase(),
          'Payment Status': (order.payment_status || 'PAID').toUpperCase(),
          'Payment Reference': order.payment_reference || '—',
          'Order Status': (order.status || 'CONFIRMED').toUpperCase(),
          'Tracking Number': order.tracking_number || '—',
          'Courier Name': order.courier_name || 'Apex Express Air-Freight'
        })
      }
    })

    const wsOrders = XLSX.utils.json_to_sheet(sheetData)

    // Set auto column widths for Excel
    wsOrders['!cols'] = [
      { wch: 18 }, // Order Number
      { wch: 20 }, // Date
      { wch: 22 }, // Customer Name
      { wch: 26 }, // Email
      { wch: 16 }, // Phone
      { wch: 10 }, // Channel
      { wch: 35 }, // Item Name
      { wch: 16 }, // SKU
      { wch: 10 }, // Qty
      { wch: 14 }, // Unit Price
      { wch: 14 }, // Line Total
      { wch: 15 }, // Shipping Fee
      { wch: 14 }, // Discount
      { wch: 16 }, // Order Total
      { wch: 18 }, // Payment Method
      { wch: 16 }, // Payment Status
      { wch: 20 }, // Payment Ref
      { wch: 14 }, // Order Status
      { wch: 16 }, // Tracking #
      { wch: 30 }  // Courier
    ]

    XLSX.utils.book_append_sheet(wb, wsOrders, 'Sales Transactions')

    // 2. Prepare Monthly Summary Sheet
    const summaryData = monthlyBreakdown.value.map(m => ({
      'Month Period': m.fullName,
      'Total Orders': m.ordersCount,
      'Total Items Sold': m.itemsCount,
      'Total Revenue ($ USD)': m.revenue,
      'Sales Share of Year': `${m.share}%`
    }))

    const wsSummary = XLSX.utils.json_to_sheet(summaryData)
    wsSummary['!cols'] = [
      { wch: 20 },
      { wch: 15 },
      { wch: 18 },
      { wch: 22 },
      { wch: 20 }
    ]
    XLSX.utils.book_append_sheet(wb, wsSummary, 'Monthly Breakdown')

    // Save Excel file
    const monthTag = selectedMonth.value === 'all' ? 'FullYear' : `Month_${selectedMonth.value}`
    const fileName = `MotoCar_Sales_Report_${selectedYear.value}_${monthTag}.xlsx`
    XLSX.writeFile(wb, fileName)

    exportSuccess.value = `Excel Report exported successfully (${fileName})!`
    setTimeout(() => { exportSuccess.value = '' }, 4500)
  } catch (err: any) {
    console.error('Failed to export Excel:', err)
    alert('Failed to generate Excel file: ' + err.message)
  } finally {
    isExporting.value = false
  }
}

const exportToCsv = () => {
  isExporting.value = true
  try {
    const headers = [
      'Order Number',
      'Date',
      'Customer Name',
      'Email',
      'Phone',
      'Channel',
      'Items Purchased',
      'Items Count',
      'Payment Method',
      'Payment Status',
      'Subtotal ($)',
      'Shipping ($)',
      'Discount ($)',
      'Total Paid ($)',
      'Status',
      'Tracking Number'
    ]

    const rows = filteredOrders.value.map(order => {
      const itemsDesc = (order.items || [])
        .map((i: any) => `${i.product_name} (x${i.quantity})`)
        .join('; ') || 'Motorsport Parts'

      return [
        `"${order.order_number}"`,
        `"${new Date(order.created_at).toLocaleString()}"`,
        `"${(order.customer_name || 'Customer').replace(/"/g, '""')}"`,
        `"${order.customer_email || '—'}"`,
        `"${order.customer_phone || '—'}"`,
        `"${(order.order_type || 'b2c').toUpperCase()}"`,
        `"${itemsDesc.replace(/"/g, '""')}"`,
        order.items_count || order.items?.length || 1,
        `"${(order.payment_method || 'Bakong KHQR').toUpperCase()}"`,
        `"${(order.payment_status || 'paid').toUpperCase()}"`,
        Number(order.subtotal || 0).toFixed(2),
        Number(order.shipping_fee || 15).toFixed(2),
        Number(order.discount_amount || 0).toFixed(2),
        Number(order.total_amount || 0).toFixed(2),
        `"${(order.status || 'confirmed').toUpperCase()}"`,
        `"${order.tracking_number || '—'}"`
      ].join(',')
    })

    // Add UTF-8 BOM so Excel opens non-Latin and special characters properly
    const csvContent = '\uFEFF' + headers.join(',') + '\n' + rows.join('\n')
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    const monthTag = selectedMonth.value === 'all' ? 'FullYear' : `Month_${selectedMonth.value}`
    link.download = `MotoCar_Sales_Report_${selectedYear.value}_${monthTag}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)

    exportSuccess.value = `CSV file downloaded successfully!`
    setTimeout(() => { exportSuccess.value = '' }, 4000)
  } catch (err: any) {
    console.error('Failed to export CSV:', err)
  } finally {
    isExporting.value = false
  }
}

const resetFilters = () => {
  selectedYear.value = '2026'
  selectedMonth.value = 'all'
  selectedChannel.value = 'all'
  selectedStatus.value = 'all'
  searchQuery.value = ''
}
</script>

<template>
  <div class="reports-page">
    <div class="container">
      <!-- Page Header -->
      <div class="reports-top-bar">
        <div>
          <div class="header-tags-row">
            <span class="sub-badge">{{ langStore.t.admin?.reportsTitle || 'EXECUTIVE SALES & INVENTORY INTELLIGENCE' }}</span>
            <span class="live-realtime-badge">
              <span class="pulse-radar-dot"></span>
              <span>{{ langStore.t.admin?.liveDatabase || 'LIVE DATABASE CONNECTED' }}</span>
            </span>
          </div>
          <h1 class="page-title">{{ langStore.t.admin?.reportsTitle || 'Executive Sales & Monthly Reports' }}</h1>
          <p class="page-sub">{{ langStore.t.admin?.reportsSub || 'Comprehensive financial ledger, monthly revenues, and Excel data export for MotoCar Custom' }}</p>
        </div>

        <!-- Export Buttons -->
        <div class="top-actions">
          <button class="btn-export-excel" :disabled="isExporting" @click="exportToExcel">
            <FileSpreadsheet :size="16" />
            <span>{{ isExporting ? 'Generating...' : (langStore.t.admin?.exportExcel || 'Export Excel (.xlsx)') }}</span>
          </button>
          <button class="btn-export-csv" :disabled="isExporting" @click="exportToCsv">
            <Download :size="15" />
            <span>{{ langStore.t.admin?.exportCsv || 'Export CSV' }}</span>
          </button>
        </div>
      </div>

      <!-- Export Notification Alert -->
      <transition name="slide-fade">
        <div v-if="exportSuccess" class="alert-success-banner">
          <CheckCircle2 :size="18" />
          <span>{{ exportSuccess }}</span>
        </div>
      </transition>

      <!-- Advanced Filter Control Center -->
      <div class="filter-panel glass-panel">
        <div class="filter-row-top">
          <div class="filter-group">
            <label class="filter-lbl"><Calendar :size="14" /> {{ langStore.t.admin?.year || 'Year' }}</label>
            <select v-model="selectedYear" class="form-select">
              <option v-for="yr in yearsList" :key="yr" :value="yr">
                {{ yr === 'all' ? 'All Years' : `Year ${yr}` }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-lbl"><Clock :size="14" /> {{ langStore.t.admin?.month || 'Month' }}</label>
            <select v-model="selectedMonth" class="form-select">
              <option v-for="m in monthsList" :key="m.value" :value="m.value">
                {{ m.label }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-lbl"><Layers :size="14" /> {{ langStore.t.admin?.channel || 'Channel' }}</label>
            <select v-model="selectedChannel" class="form-select">
              <option value="all">{{ langStore.t.admin?.allChannels || 'All Channels (B2C & B2B)' }}</option>
              <option value="b2c">{{ langStore.t.admin?.b2cRetailConsumers || 'B2C Retail Consumers' }}</option>
              <option value="b2b">{{ langStore.t.admin?.b2bWholesaleGarages || 'B2B Wholesale Garages' }}</option>
            </select>
          </div>

          <div class="filter-group">
            <label class="filter-lbl"><CheckCircle2 :size="14" /> {{ langStore.t.admin?.thStatus || 'Status' }}</label>
            <select v-model="selectedStatus" class="form-select">
              <option value="all">{{ langStore.t.admin?.allStatuses || 'All Statuses' }}</option>
              <option value="confirmed">{{ langStore.t.admin?.statusConfirmed || 'Confirmed (Paid)' }}</option>
              <option value="processing">{{ langStore.t.admin?.statusProcessing || 'Processing (Inspection)' }}</option>
              <option value="shipped">{{ langStore.t.admin?.statusShipped || 'Shipped (In Transit)' }}</option>
              <option value="delivered">{{ langStore.t.admin?.statusDelivered || 'Delivered' }}</option>
              <option value="cancelled">{{ langStore.t.admin?.statusCancelled || 'Cancelled' }}</option>
            </select>
          </div>

          <div class="filter-group search-group">
            <label class="filter-lbl"><Search :size="14" /> {{ langStore.t.admin?.searchReports || 'Search Orders' }}</label>
            <div class="search-wrap">
              <Search :size="15" class="search-icon" />
              <input 
                v-model="searchQuery" 
                type="text" 
                :placeholder="langStore.t.admin?.searchReportsPlaceholder || 'Search order #, customer, email, phone, or parts...'" 
                class="form-input"
              />
              <button v-if="searchQuery" class="btn-clear-search" @click="searchQuery = ''">&times;</button>
            </div>
          </div>
        </div>

        <div class="filter-row-bottom">
          <div class="quick-pills">
            <span class="pills-lbl">{{ langStore.t.admin?.quickPresets || 'Quick Presets:' }}</span>
            <button 
              class="btn-preset" 
              :class="{ active: selectedMonth === '10' && selectedYear === '2026' }"
              @click="selectedYear = '2026'; selectedMonth = '10'"
            >
              {{ langStore.t.admin?.thisMonth || 'This Month' }} (10/2026)
            </button>
            <button 
              class="btn-preset" 
              :class="{ active: selectedMonth === 'all' && selectedYear === '2026' }"
              @click="selectedYear = '2026'; selectedMonth = 'all'"
            >
              {{ langStore.t.admin?.fullYear || 'Full Year' }} 2026
            </button>
            <button 
              class="btn-preset" 
              :class="{ active: selectedYear === 'all' && selectedMonth === 'all' }"
              @click="selectedYear = 'all'; selectedMonth = 'all'"
            >
              {{ langStore.t.admin?.allTime || 'All Time' }}
            </button>
          </div>

          <div class="filter-summary-info">
            <span>{{ langStore.t.admin?.showing || 'Showing:' }} <strong>{{ filteredOrders.length }}</strong> order(s)</span>
            <button v-if="selectedMonth !== 'all' || selectedYear !== '2026' || selectedChannel !== 'all' || selectedStatus !== 'all' || searchQuery" class="btn-reset" @click="resetFilters">
              <RefreshCw :size="12" />
              <span>{{ langStore.t.admin?.reset || 'Reset' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Financial KPI Overview Cards -->
      <div class="kpi-grid">
        <div class="kpi-card glass-panel">
          <div class="kpi-head">
            <span class="kpi-lbl">{{ langStore.t.admin?.totalGrossRevenue || 'Total Gross Revenue' }}</span>
            <div class="kpi-icon-wrap bg-orange-glow">
              <DollarSign :size="18" class="text-orange" />
            </div>
          </div>
          <strong class="kpi-val">${{ totalFilteredGmv.toLocaleString() }}.00</strong>
          <span class="kpi-sub text-green">
            <ArrowUpRight :size="14" />
            <span>Aggregated for {{ selectedMonth === 'all' ? selectedYear : monthsList.find(m => m.value === selectedMonth)?.label }}</span>
          </span>
        </div>

        <div class="kpi-card glass-panel">
          <div class="kpi-head">
            <span class="kpi-lbl">{{ langStore.t.admin?.ordersProcessed || 'Orders Processed' }}</span>
            <div class="kpi-icon-wrap bg-cyan-glow">
              <ShoppingBag :size="18" class="text-cyan" />
            </div>
          </div>
          <strong class="kpi-val">{{ totalFilteredOrders }} Orders</strong>
          <span class="kpi-sub text-cyan">
            <CheckCircle2 :size="14" />
            <span>100% Verified in Database</span>
          </span>
        </div>

        <div class="kpi-card glass-panel">
          <div class="kpi-head">
            <span class="kpi-lbl">{{ langStore.t.admin?.partsSold || 'Parts & Items Sold' }}</span>
            <div class="kpi-icon-wrap bg-purple-glow">
              <Package :size="18" class="text-purple" />
            </div>
          </div>
          <strong class="kpi-val">{{ totalFilteredItemsCount }} Units</strong>
          <span class="kpi-sub text-purple">
            <span>Genuine Performance Parts</span>
          </span>
        </div>

        <div class="kpi-card glass-panel">
          <div class="kpi-head">
            <span class="kpi-lbl">Average Order Value (AOV)</span>
            <div class="kpi-icon-wrap bg-green-glow">
              <TrendingUp :size="18" class="text-green" />
            </div>
          </div>
          <strong class="kpi-val">${{ filteredAov }}</strong>
          <span class="kpi-sub text-orange">Motorsport performance tuning focus</span>
        </div>
      </div>

      <!-- Monthly Sales Breakdown (ប្រចាំខែឆ្នាំ) -->
      <div class="monthly-breakdown-card glass-panel">
        <div class="card-head-row">
          <div>
            <h3 class="sec-title">
              <BarChart3 :size="18" class="text-orange" />
              <span>{{ langStore.t.admin?.monthlyBreakdown || '12-Month Performance Breakdown' }} (Year {{ selectedYear === 'all' ? '2026' : selectedYear }})</span>
            </h3>
            <p class="sec-sub">Click on any month to filter the detailed transactions below</p>
          </div>
          <span class="badge-annual-total">
            Annual Total: <strong>${{ totalFilteredGmv.toLocaleString() }} USD</strong>
          </span>
        </div>

        <div class="months-table-container">
          <table class="months-table">
            <thead>
              <tr>
                <th>{{ langStore.t.admin?.month || 'Month' }}</th>
                <th>Orders</th>
                <th>Units Sold</th>
                <th>Revenue ($ USD)</th>
                <th>Annual Share</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="m in monthlyBreakdown" 
                :key="m.num"
                :class="{ 'month-active-row': selectedMonth === String(m.num), 'month-has-sales': m.revenue > 0 }"
              >
                <td>
                  <strong class="month-name">{{ m.fullName }}</strong>
                </td>
                <td>
                  <span class="orders-count-chip">{{ m.ordersCount }} Orders</span>
                </td>
                <td>
                  <span class="units-chip">{{ m.itemsCount }} Units</span>
                </td>
                <td>
                  <strong class="revenue-val">${{ m.revenue.toLocaleString() }}</strong>
                </td>
                <td class="share-cell">
                  <div class="share-progress-wrap">
                    <div class="progress-bar-bg">
                      <div class="progress-fill" :style="{ width: `${m.share}%`, background: m.share > 0 ? '#ff5500' : 'rgba(255,255,255,0.1)' }"></div>
                    </div>
                    <span class="share-num">{{ m.share }}%</span>
                  </div>
                </td>
                <td>
                  <button class="btn-filter-month" @click="selectSpecificMonth(m.num)">
                    <span>Filter Month</span>
                    <ChevronRight :size="13" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Detailed Itemized Transactions Ledger (ដឹងថាលុយគេកក់អ្វីខ្លះ ទិញអ្វីខ្លះ នរណាទិញ) -->
      <div class="transactions-section glass-panel">
        <div class="section-top-bar">
          <div>
            <h3 class="sec-title">
              <Layers :size="18" class="text-cyan" />
              <span>{{ langStore.t.admin?.itemizedLedger || 'Itemized Transaction Ledger' }}</span>
            </h3>
            <p class="sec-sub">Itemized audit of customer names, purchased parts, quantities, and Bakong KHQR deposits</p>
          </div>

          <div class="ledger-actions">
            <button class="btn-mini-export" title="Export this view to Excel" @click="exportToExcel">
              <FileSpreadsheet :size="14" />
              <span>{{ langStore.t.admin?.exportExcel || 'Export to Excel' }}</span>
            </button>
          </div>
        </div>

        <div class="table-container">
          <table class="ledger-table">
            <thead>
              <tr>
                <th>{{ langStore.t.admin?.thOrderNumber || 'Order Number' }}</th>
                <th>{{ langStore.t.admin?.thDate || 'Date & Time' }}</th>
                <th>{{ langStore.t.admin?.thCustomer || 'Customer Details' }}</th>
                <th>{{ langStore.t.admin?.thItems || 'Purchased Parts & Items' }}</th>
                <th>{{ langStore.t.admin?.channel || 'Channel' }}</th>
                <th>{{ langStore.t.admin?.thPayment || 'Payment (KHQR)' }}</th>
                <th>{{ langStore.t.admin?.thTotal || 'Total Paid' }}</th>
                <th>{{ langStore.t.admin?.thStatus || 'Status' }}</th>
                <th>{{ langStore.t.admin?.thActions || 'Actions' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredOrders.length === 0">
                <td colspan="9" class="empty-cell">
                  <ShoppingBag :size="40" class="text-orange" style="margin-bottom: 0.5rem;" />
                  <p>No orders or transactions found matching the selected filters.</p>
                  <button class="btn-reset-filters" @click="resetFilters">Reset Filters</button>
                </td>
              </tr>
              <tr v-for="order in filteredOrders" :key="order.id" class="order-table-row">
                <!-- Order Number & Tracking -->
                <td>
                  <span class="order-id-tag">{{ order.order_number }}</span>
                  <small v-if="order.tracking_number" class="tracking-pill">{{ order.tracking_number }}</small>
                </td>

                <!-- Date -->
                <td>
                  <span class="date-main">{{ new Date(order.created_at).toLocaleDateString() }}</span>
                  <small class="time-sub">{{ new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</small>
                </td>

                <!-- Customer Details -->
                <td>
                  <div class="customer-info">
                    <strong class="cust-name">{{ order.customer_name || 'Customer' }}</strong>
                    <span class="cust-contact">{{ order.customer_phone || order.customer_email || '—' }}</span>
                    <small v-if="order.customer_phone && order.customer_email" class="cust-sub-email">{{ order.customer_email }}</small>
                  </div>
                </td>

                <!-- Purchased Parts & Items List -->
                <td class="items-cell">
                  <div v-if="order.items && order.items.length > 0" class="items-list-compact">
                    <div v-for="item in order.items" :key="item.id" class="compact-item-entry">
                      <img 
                        :src="formatImageUrl(item.product_image, DEFAULT_PRODUCT_THUMB)" 
                        class="thumb-compact" 
                        :alt="item.product_name"
                      />
                      <div class="item-meta">
                        <strong class="item-product-name" :title="item.product_name">{{ item.product_name }}</strong>
                        <div class="sku-qty-row">
                          <span class="sku-badge">{{ item.product_sku }}</span>
                          <span class="qty-badge">x{{ item.quantity }}</span>
                          <span class="item-price-tag">${{ Number(item.unit_price).toLocaleString() }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-muted">
                    <span>Performance Parts (Qty: 1)</span>
                  </div>
                </td>

                <!-- Channel -->
                <td>
                  <span class="channel-badge" :class="order.order_type === 'b2b' ? 'b2b-tag' : 'b2c-tag'">
                    {{ (order.order_type || 'B2C').toUpperCase() }}
                  </span>
                </td>

                <!-- Payment Status -->
                <td>
                  <div class="payment-block">
                    <span class="pay-badge-paid">
                      <span class="dot-green"></span>
                      {{ (order.payment_method || 'Bakong KHQR').toUpperCase() }}
                    </span>
                    <small v-if="order.payment_reference" class="pay-ref">{{ order.payment_reference }}</small>
                  </div>
                </td>

                <!-- Total Amount Paid -->
                <td>
                  <strong class="price-strong">${{ Number(order.total_amount).toLocaleString() }}</strong>
                  <small class="currency-label">USD</small>
                </td>

                <!-- Order Status -->
                <td>
                  <span class="order-status-pill" :class="`status-${order.status?.toLowerCase()}`">
                    <span class="status-dot"></span>
                    <span>{{ order.status?.toUpperCase() }}</span>
                  </span>
                </td>

                <!-- Actions -->
                <td>
                  <button class="btn-inspect" title="Inspect Full Transaction Details" @click="openOrderModal(order)">
                    <Eye :size="14" />
                    <span>Inspect</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Order Inspection Modal -->
    <div v-if="isModalOpen && selectedOrder" class="modal-backdrop" @click.self="isModalOpen = false">
      <div class="modal-box glass-panel">
        <div class="modal-header">
          <div>
            <span class="modal-badge">ORDER & DEPOSIT INSPECTION</span>
            <h2 class="modal-order-num">{{ selectedOrder.order_number }}</h2>
            <small class="modal-timestamp">Purchased on {{ new Date(selectedOrder.created_at).toLocaleString() }}</small>
          </div>
          <button class="btn-close" @click="isModalOpen = false">&times;</button>
        </div>

        <div class="modal-body">
          <!-- Customer & Logistics Row -->
          <div class="info-grid-2">
            <div class="info-card">
              <span class="info-lbl">Buyer Information</span>
              <strong>{{ selectedOrder.customer_name || 'Member' }}</strong>
              <small>{{ selectedOrder.customer_email || '—' }}</small>
              <small>{{ selectedOrder.customer_phone || '+855 23 888 999' }}</small>
            </div>
            <div class="info-card">
              <span class="info-lbl">Payment & Telemetry</span>
              <strong class="text-green">BAKONG KHQR PAID</strong>
              <small>Ref: <span class="text-cyan">{{ selectedOrder.payment_reference || 'BK-KHQR-VERIFIED' }}</span></small>
              <small>Tracking: <span class="text-cyan">{{ selectedOrder.tracking_number || 'APX-545392' }}</span></small>
            </div>
          </div>

          <!-- Items Breakdown -->
          <div class="modal-items-section">
            <h4 class="modal-sec-title">Itemized Purchased Parts</h4>
            <div class="modal-items-list">
              <div v-for="itm in selectedOrder.items || []" :key="itm.id" class="modal-item-row">
                <img :src="formatImageUrl(itm.product_image, DEFAULT_PRODUCT_THUMB)" class="modal-item-img" />
                <div class="modal-item-info">
                  <strong class="modal-item-name">{{ itm.product_name }}</strong>
                  <div class="modal-item-tags">
                    <span class="sku-tag">{{ itm.product_sku }}</span>
                    <span class="qty-tag">Qty: {{ itm.quantity }}</span>
                  </div>
                </div>
                <div class="modal-item-price">
                  <strong>${{ Number(itm.total_price || itm.unit_price * itm.quantity).toLocaleString() }}</strong>
                  <small>${{ Number(itm.unit_price).toLocaleString() }} each</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Financial Calculation -->
          <div class="modal-totals">
            <div class="m-row">
              <span>Subtotal:</span>
              <strong>${{ Number(selectedOrder.subtotal).toLocaleString() }}</strong>
            </div>
            <div class="m-row">
              <span>Express Delivery:</span>
              <strong>${{ Number(selectedOrder.shipping_fee || 15).toLocaleString() }}</strong>
            </div>
            <div v-if="Number(selectedOrder.discount_amount) > 0" class="m-row text-green">
              <span>Discount Applied:</span>
              <strong>-${{ Number(selectedOrder.discount_amount).toLocaleString() }}</strong>
            </div>
            <div class="m-row m-total">
              <span>Total Paid via Bakong KHQR:</span>
              <strong class="text-orange">${{ Number(selectedOrder.total_amount).toLocaleString() }} USD</strong>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <router-link :to="`/track/${selectedOrder.order_number}`" class="btn-track-external" target="_blank">
            <Truck :size="15" />
            <span>Open Public Tracking</span>
          </router-link>
          <button class="btn-modal-close" @click="isModalOpen = false">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.reports-page {
  padding: 2rem 0 6rem;
  width: 100%;
  color: #f1f5f9;
}

.container {
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 2rem;
  box-sizing: border-box;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
}

/* Header */
.reports-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.header-tags-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
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
}

.live-realtime-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #4ade80;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.05em;
}

.pulse-radar-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  box-shadow: 0 0 8px #22c55e;
  animation: radar-pulse 1.5s infinite;
}

@keyframes radar-pulse {
  0% { transform: scale(0.9); opacity: 0.6; }
  50% { transform: scale(1.4); opacity: 1; }
  100% { transform: scale(0.9); opacity: 0.6; }
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

/* Top Export Buttons */
.top-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn-export-excel {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  padding: 0.65rem 1.25rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
  transition: all 0.2s;
}

.btn-export-excel:hover:not(:disabled) {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45);
  transform: translateY(-1px);
}

.btn-export-csv {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: rgba(255, 85, 0, 0.15);
  border: 1px solid rgba(255, 85, 0, 0.35);
  color: #ff7834;
  padding: 0.65rem 1.15rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-export-csv:hover:not(:disabled) {
  background: #ff5500;
  color: white;
}

.alert-success-banner {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: rgba(16, 185, 129, 0.18);
  border: 1px solid rgba(16, 185, 129, 0.4);
  color: #34d399;
  padding: 0.8rem 1.25rem;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 20px rgba(16, 185, 129, 0.2);
}

/* Filter Control Panel */
.filter-panel {
  padding: 1.25rem 1.5rem;
  margin-bottom: 2rem;
}

.filter-row-top {
  display: grid;
  grid-template-columns: 140px 180px 180px 160px 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

@media (max-width: 1100px) {
  .filter-row-top {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.filter-lbl {
  font-size: 0.72rem;
  font-weight: 700;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.form-select {
  background: rgba(2, 6, 23, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #ffffff;
  padding: 0.55rem 0.75rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  outline: none;
  cursor: pointer;
}

.form-select:focus {
  border-color: #ff5500;
}

.search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  color: #64748b;
}

.form-input {
  width: 100%;
  background: rgba(2, 6, 23, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #ffffff;
  padding: 0.55rem 0.75rem 0.55rem 2.2rem;
  border-radius: 8px;
  font-size: 0.85rem;
  outline: none;
}

.form-input:focus {
  border-color: #ff5500;
}

.btn-clear-search {
  position: absolute;
  right: 0.5rem;
  background: none;
  border: none;
  color: #64748b;
  font-size: 1.1rem;
  cursor: pointer;
}

.filter-row-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 0.85rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.quick-pills {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.pills-lbl {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 700;
}

.btn-preset {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  padding: 0.3rem 0.75rem;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-preset:hover, .btn-preset.active {
  background: rgba(255, 85, 0, 0.15);
  border-color: rgba(255, 85, 0, 0.4);
  color: #ff7834;
}

.filter-summary-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.8rem;
  color: #94a3b8;
}

.filter-summary-info strong {
  color: #ffffff;
}

.btn-reset {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
  padding: 0.25rem 0.65rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

/* KPI Cards */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (max-width: 1050px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.kpi-card {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
}

.kpi-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.6rem;
}

.kpi-lbl {
  color: #94a3b8;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.kpi-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bg-orange-glow { background: rgba(255, 85, 0, 0.15); }
.bg-cyan-glow { background: rgba(56, 189, 248, 0.15); }
.bg-purple-glow { background: rgba(168, 85, 247, 0.15); }
.bg-green-glow { background: rgba(34, 197, 94, 0.15); }

.kpi-val {
  color: #ffffff;
  font-size: 1.85rem;
  font-weight: 900;
  margin-bottom: 0.3rem;
  letter-spacing: -0.02em;
}

.kpi-sub {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.76rem;
  font-weight: 700;
}

.text-orange { color: #ff5500; }
.text-cyan { color: #38bdf8; }
.text-green { color: #4ade80; }
.text-purple { color: #c084fc; }

/* Monthly Breakdown Section */
.monthly-breakdown-card {
  padding: 1.75rem;
  margin-bottom: 2rem;
}

.card-head-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.sec-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #ffffff;
  font-size: 1.15rem;
  font-weight: 800;
  margin: 0 0 0.25rem;
}

.sec-sub {
  color: #94a3b8;
  font-size: 0.82rem;
  margin: 0;
}

.badge-annual-total {
  background: rgba(255, 85, 0, 0.12);
  border: 1px solid rgba(255, 85, 0, 0.3);
  color: #ff7834;
  padding: 0.4rem 0.9rem;
  border-radius: 8px;
  font-size: 0.82rem;
}

.months-table-container {
  overflow-x: auto;
}

.months-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  text-align: left;
}

.months-table th {
  background: rgba(2, 6, 23, 0.5);
  color: #94a3b8;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.85rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.months-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  vertical-align: middle;
}

.month-name {
  color: #ffffff;
}

.month-active-row {
  background: rgba(255, 85, 0, 0.12) !important;
}

.orders-count-chip {
  background: rgba(255, 255, 255, 0.05);
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  font-size: 0.78rem;
  color: #cbd5e1;
}

.units-chip {
  color: #38bdf8;
  font-weight: 600;
}

.revenue-val {
  color: #ff5500;
  font-size: 0.95rem;
}

.share-cell {
  width: 250px;
}

.share-progress-wrap {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.progress-bar-bg {
  flex: 1;
  height: 8px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 9999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.6s ease;
}

.share-num {
  font-size: 0.75rem;
  color: #64748b;
  width: 35px;
  text-align: right;
}

.btn-filter-month {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  padding: 0.3rem 0.65rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-filter-month:hover {
  background: #ff5500;
  border-color: #ff5500;
  color: white;
}

/* Detailed Ledger Table */
.transactions-section {
  padding: 1.75rem;
}

.section-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.btn-mini-export {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.35);
  color: #34d399;
  padding: 0.45rem 0.9rem;
  border-radius: 6px;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-mini-export:hover {
  background: #10b981;
  color: white;
}

.table-container {
  overflow-x: auto;
}

.ledger-table {
  width: 100%;
  min-width: 1100px;
  border-collapse: collapse;
  font-size: 0.85rem;
  text-align: left;
}

.ledger-table th {
  background: rgba(2, 6, 23, 0.6);
  color: #94a3b8;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1.15rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  white-space: nowrap;
}

.ledger-table td {
  padding: 1rem 1.15rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  vertical-align: middle;
}

.order-id-tag {
  font-family: monospace;
  font-weight: 700;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.1);
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  display: inline-block;
  white-space: nowrap;
}

.tracking-pill {
  display: block;
  font-family: monospace;
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.date-main {
  display: block;
  color: #ffffff;
  font-size: 0.82rem;
  white-space: nowrap;
}

.time-sub {
  color: #64748b;
  font-size: 0.72rem;
}

.customer-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.cust-name {
  color: #ffffff;
  font-size: 0.88rem;
}

.cust-contact {
  color: #94a3b8;
  font-size: 0.76rem;
}

.cust-sub-email {
  color: #64748b;
  font-size: 0.72rem;
}

.items-cell {
  max-width: 320px;
}

.items-list-compact {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.compact-item-entry {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.thumb-compact {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  object-fit: cover;
  background: #020617;
  border: 1px solid rgba(255, 255, 255, 0.1);
  flex-shrink: 0;
}

.item-meta {
  flex: 1;
  min-width: 0;
}

.item-product-name {
  color: #ffffff;
  font-size: 0.82rem;
  display: block;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sku-qty-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.15rem;
}

.sku-badge {
  font-family: monospace;
  font-size: 0.68rem;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.05);
  padding: 0.1rem 0.35rem;
  border-radius: 3px;
}

.qty-badge {
  color: #38bdf8;
  font-weight: 700;
  font-size: 0.72rem;
}

.item-price-tag {
  color: #ff7834;
  font-size: 0.72rem;
  font-weight: 600;
}

.channel-badge {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.04em;
}

.b2c-tag {
  background: rgba(255, 85, 0, 0.12);
  color: #ff7834;
  border: 1px solid rgba(255, 85, 0, 0.3);
}

.b2b-tag {
  background: rgba(56, 189, 248, 0.12);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.payment-block {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.pay-badge-paid {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.25);
  color: #4ade80;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
}

.dot-green {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #22c55e;
}

.pay-ref {
  font-family: monospace;
  font-size: 0.68rem;
  color: #64748b;
}

.price-strong {
  color: #ff5500;
  font-size: 1rem;
  font-weight: 800;
}

.currency-label {
  color: #64748b;
  font-size: 0.72rem;
  margin-left: 0.2rem;
}

/* Status Pill */
.order-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-confirmed { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
.status-confirmed .status-dot { background: #38bdf8; }

.status-processing { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
.status-processing .status-dot { background: #fbbf24; }

.status-shipped { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
.status-shipped .status-dot { background: #c084fc; }

.status-delivered { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
.status-delivered .status-dot { background: #22c55e; }

.status-cancelled { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.status-cancelled .status-dot { background: #ef4444; }

.btn-inspect {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  background: rgba(255, 85, 0, 0.12);
  border: 1px solid rgba(255, 85, 0, 0.3);
  color: #ff5500;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-inspect:hover {
  background: #ff5500;
  color: white;
}

.empty-cell {
  text-align: center;
  padding: 4rem 1rem !important;
  color: #94a3b8;
}

.btn-reset-filters {
  margin-top: 1rem;
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
}

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.5rem;
  backdrop-filter: blur(4px);
}

.modal-box {
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  overflow-y: auto;
  background: #090e18;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.7);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 1.5rem;
}

.modal-badge {
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #ff7834;
}

.modal-order-num {
  font-size: 1.6rem;
  color: white;
  margin: 0.2rem 0;
  font-weight: 800;
}

.modal-timestamp {
  color: #64748b;
  font-size: 0.8rem;
}

.btn-close {
  background: none;
  border: none;
  color: #64748b;
  font-size: 1.5rem;
  cursor: pointer;
}

.info-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.info-card {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.info-lbl {
  font-size: 0.72rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 700;
  margin-bottom: 0.2rem;
}

.modal-sec-title {
  color: white;
  font-size: 0.95rem;
  margin: 0 0 0.85rem;
  font-weight: 700;
}

.modal-items-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.modal-item-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(2, 6, 23, 0.4);
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.04);
}

.modal-item-img {
  width: 44px;
  height: 44px;
  border-radius: 6px;
  object-fit: cover;
  background: #020617;
}

.modal-item-info {
  flex: 1;
}

.modal-item-name {
  color: white;
  font-size: 0.88rem;
  display: block;
}

.modal-item-tags {
  display: flex;
  gap: 0.4rem;
  margin-top: 0.2rem;
}

.sku-tag {
  font-family: monospace;
  font-size: 0.7rem;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  color: #94a3b8;
}

.qty-tag {
  font-size: 0.72rem;
  color: #38bdf8;
  font-weight: 700;
}

.modal-item-price {
  text-align: right;
}

.modal-item-price strong {
  color: #ff5500;
  display: block;
}

.modal-item-price small {
  color: #64748b;
  font-size: 0.72rem;
}

.modal-totals {
  background: rgba(2, 6, 23, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.m-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.88rem;
  color: #94a3b8;
}

.m-row strong {
  color: white;
}

.m-total {
  margin-top: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px dashed rgba(255, 255, 255, 0.1);
  font-size: 1rem;
  color: white;
  font-weight: 800;
}

.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.btn-track-external {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #38bdf8;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
}

.btn-modal-close {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: white;
  padding: 0.55rem 1.4rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
</style>
