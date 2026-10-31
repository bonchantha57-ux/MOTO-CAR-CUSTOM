/**
 * Image URL normalization and fallback helpers for MotoCar Custom
 */

export const DEFAULT_AVATAR = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&h=200&q=80'
export const DEFAULT_USER_AVATAR = 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=200&h=200&q=80'
export const DEFAULT_PRODUCT_THUMB = 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=600&q=80'

export function formatImageUrl(url?: string | null, fallback = DEFAULT_AVATAR): string {
  if (!url || typeof url !== 'string' || url.trim() === '') {
    return fallback
  }

  const trimmed = url.trim()

  // Base64 or Blob previews
  if (trimmed.startsWith('data:') || trimmed.startsWith('blob:')) {
    return trimmed
  }

  // Convert hardcoded backend storage URLs to relative so Vite proxy / same-origin works seamlessly
  if (trimmed.includes(':8000/storage/')) {
    return '/storage/' + trimmed.split(':8000/storage/')[1]
  }

  return trimmed
}

export function handleAvatarError(event: Event, fallback = DEFAULT_AVATAR) {
  const target = event.target as HTMLImageElement | null
  if (target && target.src !== fallback) {
    target.src = fallback
  }
}

export function handleProductImageError(event: Event, fallback = DEFAULT_PRODUCT_THUMB) {
  const target = event.target as HTMLImageElement | null
  if (target && target.src !== fallback) {
    target.src = fallback
  }
}
