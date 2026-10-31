/**
 * Order Real-Time Synchronization Utility
 * Enables cross-tab & live synchronization when Admin modifies or deletes orders.
 * Uses BroadcastChannel with localStorage event fallback.
 */

const CHANNEL_NAME = 'motocar_orders_realtime_sync'
const STORAGE_KEY = 'motocar_order_event_payload'

export interface OrderRealtimeEvent {
  action: 'STATUS_UPDATED' | 'ORDER_DELETED' | 'ORDER_CREATED'
  orderId?: number | string
  orderNumber?: string
  trackingNumber?: string
  status?: string
  customerName?: string
  totalAmount?: number
  itemsCount?: number
  timestamp: number
}

// Singleton BroadcastChannel instance
let channel: BroadcastChannel | null = null
try {
  if (typeof window !== 'undefined' && 'BroadcastChannel' in window) {
    channel = new BroadcastChannel(CHANNEL_NAME)
  }
} catch (e) {
  console.warn('BroadcastChannel not supported in current environment; using storage events fallback', e)
}

/**
 * Broadcast an order status update event
 */
export function broadcastOrderUpdate(orderNumber: string, status: string, orderId?: number | string) {
  const event: OrderRealtimeEvent = {
    action: 'STATUS_UPDATED',
    orderNumber,
    orderId,
    status,
    timestamp: Date.now()
  }

  // 1. Post to BroadcastChannel (Instant cross-tab)
  if (channel) {
    try {
      channel.postMessage(event)
    } catch (err) {
      console.warn('Failed to postMessage to channel:', err)
    }
  }

  // 2. Write to localStorage (Triggers storage event on other tabs)
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(event))
  } catch (err) {
    // Ignore storage quota/security issues
  }
}

/**
 * Broadcast an order deletion event
 */
export function broadcastOrderDelete(orderNumber: string, orderId?: number | string) {
  const event: OrderRealtimeEvent = {
    action: 'ORDER_DELETED',
    orderNumber,
    orderId,
    timestamp: Date.now()
  }

  if (channel) {
    try {
      channel.postMessage(event)
    } catch (err) {
      console.warn('Failed to postMessage to channel:', err)
    }
  }

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(event))
  } catch (err) {
    // Ignore storage quota/security issues
  }
}

/**
 * Broadcast an order creation event with purchase details
 */
export function broadcastOrderCreated(
  orderNumber: string, 
  orderId?: number | string,
  meta?: { customerName?: string; totalAmount?: number; itemsCount?: number; trackingNumber?: string }
) {
  const event: OrderRealtimeEvent = {
    action: 'ORDER_CREATED',
    orderNumber,
    orderId,
    customerName: meta?.customerName,
    totalAmount: meta?.totalAmount,
    itemsCount: meta?.itemsCount,
    trackingNumber: meta?.trackingNumber,
    timestamp: Date.now()
  }

  if (channel) {
    try {
      channel.postMessage(event)
    } catch (err) {
      console.warn('Failed to postMessage to channel:', err)
    }
  }

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(event))
  } catch (err) {
    // Ignore storage quota/security issues
  }
}

/**
 * Subscribe to real-time order events
 * Returns an unsubscribe cleanup function
 */
export function subscribeToOrderEvents(callback: (event: OrderRealtimeEvent) => void): () => void {
  const handleMessage = (e: MessageEvent) => {
    if (e.data && e.data.action) {
      callback(e.data)
    }
  }

  const handleStorage = (e: StorageEvent) => {
    if (e.key === STORAGE_KEY && e.newValue) {
      try {
        const parsed = JSON.parse(e.newValue) as OrderRealtimeEvent
        if (parsed && parsed.action) {
          callback(parsed)
        }
      } catch (err) {
        // Ignore parse error
      }
    }
  }

  if (channel) {
    channel.addEventListener('message', handleMessage)
  }
  window.addEventListener('storage', handleStorage)

  return () => {
    if (channel) {
      channel.removeEventListener('message', handleMessage)
    }
    window.removeEventListener('storage', handleStorage)
  }
}
