<script setup lang="ts">
import { ArrowLeft, ShieldCheck, Zap } from 'lucide-vue-next'
</script>

<template>
  <div class="auth-layout">
    <!-- Background Showcase Image -->
    <div class="auth-bg">
      <img src="/images/hero-banner.jpg" alt="MotoCar Tuning" class="auth-bg-img" />
      <div class="auth-overlay"></div>
    </div>

    <!-- Top Navigation Header -->
    <header class="auth-top-header">
      <router-link to="/" class="back-link">
        <ArrowLeft :size="16" />
        <span>Back to Store</span>
      </router-link>

      <router-link to="/" class="auth-brand-logo">
        <div class="logo-mark">
          <Zap :size="18" />
        </div>
        <div class="logo-text">
          <span class="text-white">MOTO</span><span class="text-orange">CAR</span>
          <span class="brand-sub">CUSTOM</span>
        </div>
      </router-link>

      <div class="security-chip">
        <ShieldCheck :size="15" class="text-green" />
        <span>256-Bit SSL Encrypted</span>
      </div>
    </header>

    <!-- Main Auth Card Slot -->
    <main class="auth-main-card">
      <router-view />
    </main>

    <!-- Bottom Footer -->
    <footer class="auth-bottom-footer">
      <p>© 2026 MotoCar Custom Co., Ltd. • Cambodia's Premier Performance & B2B Parts Network</p>
    </footer>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
  position: relative;
  padding: 1.5rem 1rem 2rem 1rem;
  background: #05070b;
  color: #f1f5f9;
  overflow-x: hidden;
}

.auth-bg {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  z-index: 1;
}

.auth-bg-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: brightness(0.28) contrast(1.2);
}

.auth-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: radial-gradient(circle at 50% 20%, rgba(249, 115, 22, 0.08) 0%, rgba(5, 7, 11, 0.95) 80%);
}

.auth-top-header {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 1200px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.back-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: #94a3b8;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.4rem 0.85rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  transition: all 0.2s;
}

.back-link:hover {
  background: rgba(249, 115, 22, 0.12);
  border-color: rgba(249, 115, 22, 0.4);
  color: var(--orange-500);
}

.auth-brand-logo {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  text-decoration: none;
}

.logo-mark {
  width: 34px;
  height: 34px;
  background: linear-gradient(135deg, var(--orange-500) 0%, #ff8c00 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
}

.logo-text {
  font-family: var(--font-display);
  font-weight: 900;
  font-size: 1.35rem;
  letter-spacing: -0.04em;
}

.text-white {
  color: #ffffff;
}

.text-orange {
  color: var(--orange-500);
}

.brand-sub {
  font-size: 0.65rem;
  background: rgba(0, 210, 255, 0.15);
  border: 1px solid rgba(0, 210, 255, 0.35);
  color: #00d2ff;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  margin-left: 0.4rem;
  font-weight: 800;
}

.security-chip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.04);
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.text-green {
  color: #22c55e;
}

.auth-main-card {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 500px;
  margin: 2rem auto;
}

.auth-bottom-footer {
  position: relative;
  z-index: 10;
  text-align: center;
  color: #64748b;
  font-size: 0.78rem;
  padding-top: 1rem;
}

@media (max-width: 640px) {
  .security-chip {
    display: none;
  }
}
</style>
