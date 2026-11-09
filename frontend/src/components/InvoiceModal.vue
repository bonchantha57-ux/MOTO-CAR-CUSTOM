<script setup lang="ts">
import { computed } from 'vue'
import {
  Printer,
  X,
  ShieldCheck,
  CheckCircle2,
  Package,
  Truck,
  QrCode,
  Download,
  Calendar,
  CreditCard,
  Building2,
  ExternalLink
} from 'lucide-vue-next'
import { formatImageUrl, DEFAULT_PRODUCT_THUMB } from '@/utils/image'

const props = defineProps<{
  isOpen: boolean
  order: any
}>()

const emit = defineEmits<{
  (e: 'close'): void
}>()

const invoiceNumber = computed(() => {
  if (!props.order) return 'INV-MCC-00000'
  return props.order.order_number?.startsWith('INV-') 
    ? props.order.order_number 
    : `INV-${props.order.order_number || props.order.id || '2026'}`
})

const formattedDate = computed(() => {
  if (!props.order?.created_at) return new Date().toLocaleDateString('en-US', { dateStyle: 'medium' })
  const d = new Date(props.order.created_at)
  return d.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
})

const khrAmount = computed(() => {
  const total = Number(props.order?.total_amount || 0)
  return (total * 4100).toLocaleString()
})

const printInvoice = () => {
  window.print()
}
</script>

<template>
  <div v-if="isOpen && order" class="invoice-modal-backdrop" @click.self="emit('close')">
    <div class="invoice-dialog glass-panel">
      <!-- Top Action Bar (Hidden in Print) -->
      <div class="invoice-action-bar no-print">
        <div class="bar-left">
          <span class="invoice-tag">
            <ShieldCheck :size="14" class="text-green" />
            <span>Official Tax & Purchase Invoice</span>
          </span>
          <span class="order-ref-pill">{{ order.order_number }}</span>
        </div>
        <div class="bar-right">
          <button class="btn-print" @click="printInvoice">
            <Printer :size="15" />
            <span>បោះពុម្ព / Save PDF</span>
          </button>
          <button class="btn-close" @click="emit('close')">
            <X :size="18" />
          </button>
        </div>
      </div>

      <!-- Printable Invoice Sheet -->
      <div class="invoice-sheet" id="printable-invoice">
        <!-- Sheet Header -->
        <div class="sheet-header">
          <div class="brand-side">
            <div class="brand-logo-row">
              <img src="/images/logo.png" alt="MotoCar Custom" class="invoice-logo" />
              <div>
                <h1 class="brand-name">
                  <span class="text-orange">MOTO</span>CAR <span class="badge-custom">CUSTOM</span>
                </h1>
                <p class="brand-sub">Hyper-Performance & Genuine Motorsport Distribution</p>
              </div>
            </div>
            <div class="company-address">
              <p><strong>MotoCar Custom Co., Ltd.</strong></p>
              <p>#88 Russian Blvd, Toul Kork, Phnom Penh, Cambodia</p>
              <p>Hotline: (+855) 23 888 999 / (+855) 70 888 999</p>
              <p>Email: vip@motocarcustom.com | Web: motocarcustom.com</p>
              <p class="tax-id">VAT / Tax ID: <strong>K009-902488192</strong></p>
            </div>
          </div>

          <div class="invoice-meta-side">
            <div class="invoice-title-box">
              <h2 class="invoice-heading">PURCHASE INVOICE</h2>
              <span class="invoice-khmer-title">វិក្កយបត្រទិញទំនិញ</span>
              <strong class="inv-number">{{ invoiceNumber }}</strong>
            </div>

            <div class="meta-table">
              <div class="meta-row">
                <span class="lbl">Date Issued:</span>
                <span class="val">{{ formattedDate }}</span>
              </div>
              <div class="meta-row">
                <span class="lbl">Order Number:</span>
                <span class="val font-mono">{{ order.order_number }}</span>
              </div>
              <div class="meta-row">
                <span class="lbl">Payment Method:</span>
                <span class="val payment-chip">{{ (order.payment_method || 'Bakong KHQR').toUpperCase() }}</span>
              </div>
              <div class="meta-row">
                <span class="lbl">Payment Status:</span>
                <span class="val status-paid">
                  <CheckCircle2 :size="13" />
                  <span>PAID (បានទូទាត់)</span>
                </span>
              </div>
              <div class="meta-row" v-if="order.payment_reference">
                <span class="lbl">Payment Ref:</span>
                <span class="val font-mono text-cyan">{{ order.payment_reference }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="sheet-divider"></div>

        <!-- Customer & Shipping Information -->
        <div class="sheet-parties">
          <div class="party-box">
            <span class="party-label">BILLED TO (ព័ត៌មានអតិថិជន):</span>
            <h3 class="party-name">{{ order.customer_name || 'Valued Performance Driver' }}</h3>
            <p v-if="order.customer_phone">Phone: <strong>{{ order.customer_phone }}</strong></p>
            <p v-if="order.customer_email">Email: <strong>{{ order.customer_email }}</strong></p>
            <p class="party-mode">Account Tier: <span>{{ order.order_type === 'b2b' ? 'B2B Wholesale Partner' : 'B2C VIP Retail' }}</span></p>
          </div>

          <div class="party-box">
            <span class="party-label">SHIPMENT & FULFILLMENT (ការដឹកជញ្ជូន):</span>
            <p><strong>Carrier:</strong> {{ order.courier_name || 'Apex Express Air-Freight Logistics' }}</p>
            <p><strong>Tracking #:</strong> <span class="font-mono text-orange">{{ order.tracking_number || 'APX-882910' }}</span></p>
            <p><strong>Destination:</strong> {{ order.shipping_address || 'Phnom Penh Hub / Express Delivery' }}</p>
            <p><strong>Warranty:</strong> 1-Year Official Fitment Guarantee</p>
          </div>
        </div>

        <!-- Line Items Table -->
        <div class="sheet-items-table-wrap">
          <table class="invoice-table">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Part Description & Genuine SKU</th>
                <th style="text-align: center; width: 70px;">Qty</th>
                <th style="text-align: right; width: 120px;">Unit Price</th>
                <th style="text-align: right; width: 130px;">Amount (USD)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in (order.items || [])" :key="idx">
                <td class="item-idx">{{ idx + 1 }}</td>
                <td>
                  <div class="item-name-cell">
                    <strong class="item-title">{{ item.product_name }}</strong>
                    <span class="item-sku">SKU: {{ item.product_sku || 'MCC-GEN-PART' }}</span>
                  </div>
                </td>
                <td style="text-align: center;" class="item-qty">{{ item.quantity }}</td>
                <td style="text-align: right;" class="item-unit">${{ Number(item.unit_price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                <td style="text-align: right;" class="item-total">${{ Number(item.total_price || (item.unit_price * item.quantity)).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
              </tr>
              <!-- Fallback if items array empty -->
              <tr v-if="!order.items || order.items.length === 0">
                <td class="item-idx">1</td>
                <td>
                  <div class="item-name-cell">
                    <strong class="item-title">Motorsport Performance Upgrade Package</strong>
                    <span class="item-sku">SKU: {{ order.order_number }}</span>
                  </div>
                </td>
                <td style="text-align: center;" class="item-qty">1</td>
                <td style="text-align: right;" class="item-unit">${{ Number(order.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                <td style="text-align: right;" class="item-total">${{ Number(order.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Totals & Payment Summary -->
        <div class="sheet-summary-grid">
          <div class="guarantee-clause">
            <div class="guarantee-box">
              <ShieldCheck :size="24" class="text-orange" />
              <div>
                <strong>100% Genuine Certified Performance Guarantee</strong>
                <p>Every tuning part on this invoice is directly imported from certified manufacturers (Akrapovič, Brembo, Öhlins, Yoshimura, GTS Forged) with laser serial fitment protection.</p>
              </div>
            </div>

            <div class="qr-auth-row">
              <div class="qr-mock">
                <QrCode :size="48" class="text-dark" />
              </div>
              <div class="qr-text">
                <small>Scan to Verify Digital Authenticity</small>
                <p class="font-mono text-muted">auth.motocarcustom.com/{{ order.order_number }}</p>
              </div>
            </div>
          </div>

          <div class="summary-totals-card">
            <div class="calc-row">
              <span>Subtotal (តម្លៃទំនិញ):</span>
              <strong>${{ Number(order.subtotal || order.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</strong>
            </div>
            <div class="calc-row">
              <span>Air-Freight Shipping (ដឹកជញ្ជូន):</span>
              <strong v-if="Number(order.shipping_fee) > 0">${{ Number(order.shipping_fee).toFixed(2) }}</strong>
              <strong v-else class="text-green">FREE (ឥតគិតថ្លៃ)</strong>
            </div>
            <div class="calc-row" v-if="Number(order.discount_amount) > 0">
              <span>Discount (បញ្ចុះតម្លៃ):</span>
              <strong class="text-green">-${{ Number(order.discount_amount).toFixed(2) }}</strong>
            </div>
            <div class="calc-divider"></div>
            <div class="grand-total-row">
              <div>
                <span class="total-lbl">TOTAL PAID (សរុបបានទូទាត់):</span>
                <span class="khr-equiv">≈ ៛{{ khrAmount }} KHR</span>
              </div>
              <span class="total-usd">${{ Number(order.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }} USD</span>
            </div>
          </div>
        </div>

        <!-- Official Footer Seal & Sign-off -->
        <div class="sheet-footer">
          <div class="signature-col">
            <div class="stamp-seal">
              <div class="seal-inner">
                <span class="seal-title">MOTOCAR CUSTOM</span>
                <span class="seal-verified">OFFICIAL PAID</span>
                <span class="seal-loc">PHNOM PENH</span>
              </div>
            </div>
            <p class="sig-title">Authorized Digital Sign-off</p>
          </div>

          <div class="footer-note">
            <p>Thank you for choosing MotoCar Custom for your high-performance motorsport build!</p>
            <p>For installation appointments, present this invoice at any certified partner garage across Cambodia.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.invoice-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(8px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  overflow-y: auto;
}

.invoice-dialog {
  width: 100%;
  max-width: 900px;
  background: #0d121d;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9), 0 0 30px rgba(249, 115, 22, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-height: 94vh;
}

/* Action Bar */
.invoice-action-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #111827;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.bar-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.invoice-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: #10b981;
  background: rgba(16, 185, 129, 0.12);
  padding: 0.25rem 0.6rem;
  border-radius: 6px;
}

.order-ref-pill {
  font-family: monospace;
  font-size: 0.85rem;
  font-weight: 700;
  color: #f97316;
  background: rgba(249, 115, 22, 0.12);
  padding: 0.25rem 0.6rem;
  border-radius: 6px;
}

.bar-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-print {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: linear-gradient(135deg, #f97316, #ea580c);
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-print:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
}

.btn-close {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-close:hover {
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
}

/* Printable Sheet */
.invoice-sheet {
  padding: 2.25rem;
  background: #ffffff;
  color: #1e293b;
  overflow-y: auto;
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}

.sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.brand-side {
  max-width: 55%;
}

.brand-logo-row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 0.85rem;
}

.invoice-logo {
  width: 48px;
  height: 48px;
  object-fit: contain;
}

.brand-name {
  font-size: 1.4rem;
  font-weight: 900;
  letter-spacing: -0.5px;
  color: #0f172a;
  margin: 0;
  line-height: 1.2;
}

.badge-custom {
  background: #f97316;
  color: #fff;
  font-size: 0.65rem;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  vertical-align: middle;
  font-weight: 800;
}

.brand-sub {
  font-size: 0.72rem;
  color: #64748b;
  margin: 0;
  font-weight: 600;
}

.company-address {
  font-size: 0.76rem;
  color: #475569;
  line-height: 1.45;
}

.company-address p {
  margin: 0 0 0.15rem 0;
}

.tax-id {
  color: #0f172a;
  margin-top: 0.35rem !important;
}

/* Meta Side */
.invoice-meta-side {
  text-align: right;
  min-width: 280px;
}

.invoice-title-box {
  margin-bottom: 0.75rem;
}

.invoice-heading {
  font-size: 1.3rem;
  font-weight: 900;
  letter-spacing: 1px;
  color: #0f172a;
  margin: 0;
}

.invoice-khmer-title {
  font-family: 'Kantumruy Pro', sans-serif;
  font-size: 0.82rem;
  color: #f97316;
  display: block;
  font-weight: 700;
}

.inv-number {
  font-family: monospace;
  font-size: 1rem;
  color: #0f172a;
  display: block;
  margin-top: 0.25rem;
}

.meta-table {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.65rem 0.85rem;
  font-size: 0.75rem;
}

.meta-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.2rem 0;
}

.meta-row .lbl {
  color: #64748b;
  font-weight: 600;
}

.meta-row .val {
  color: #0f172a;
  font-weight: 700;
}

.font-mono {
  font-family: monospace;
}

.payment-chip {
  background: #e0f2fe;
  color: #0369a1;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
  font-size: 0.7rem;
}

.status-paid {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  color: #15803d;
  background: #dcfce7;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 800;
}

.sheet-divider {
  height: 2px;
  background: linear-gradient(90deg, #f97316, #e2e8f0);
  margin: 1.25rem 0;
}

/* Parties */
.sheet-parties {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.party-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  font-size: 0.76rem;
  color: #334155;
  line-height: 1.5;
}

.party-box p {
  margin: 0 0 0.25rem 0;
}

.party-label {
  font-size: 0.7rem;
  font-weight: 800;
  color: #f97316;
  letter-spacing: 0.5px;
  display: block;
  margin-bottom: 0.35rem;
}

.party-name {
  font-size: 0.95rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 0.35rem 0;
}

.party-mode span {
  font-weight: 700;
  color: #0369a1;
}

/* Items Table */
.sheet-items-table-wrap {
  margin-bottom: 1.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.invoice-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.78rem;
}

.invoice-table thead {
  background: #0f172a;
  color: #ffffff;
}

.invoice-table th {
  padding: 0.65rem 0.85rem;
  font-weight: 700;
  font-size: 0.74rem;
  text-align: left;
  letter-spacing: 0.5px;
}

.invoice-table td {
  padding: 0.75rem 0.85rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  vertical-align: middle;
}

.invoice-table tbody tr:nth-child(even) {
  background: #f8fafc;
}

.item-idx {
  color: #94a3b8;
  font-weight: 600;
}

.item-name-cell {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.item-title {
  color: #0f172a;
  font-size: 0.82rem;
}

.item-sku {
  font-family: monospace;
  font-size: 0.7rem;
  color: #64748b;
}

.item-qty {
  font-weight: 700;
  color: #0f172a;
}

.item-unit {
  color: #475569;
}

.item-total {
  font-weight: 800;
  color: #0f172a;
}

/* Summary Grid */
.sheet-summary-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.guarantee-box {
  display: flex;
  gap: 0.75rem;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 8px;
  padding: 0.75rem 0.85rem;
  font-size: 0.72rem;
  color: #9a3412;
  line-height: 1.4;
  margin-bottom: 0.85rem;
}

.guarantee-box strong {
  display: block;
  font-size: 0.78rem;
  color: #c2410c;
  margin-bottom: 0.2rem;
}

.guarantee-box p {
  margin: 0;
}

.qr-auth-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
}

.qr-mock {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 0.25rem;
  border-radius: 6px;
}

.qr-text small {
  display: block;
  font-size: 0.68rem;
  font-weight: 700;
  color: #475569;
}

.qr-text p {
  margin: 0;
  font-size: 0.68rem;
  color: #94a3b8;
}

/* Summary Card */
.summary-totals-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  font-size: 0.78rem;
}

.calc-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.3rem 0;
  color: #475569;
}

.calc-row strong {
  color: #0f172a;
}

.calc-divider {
  height: 1px;
  background: #cbd5e1;
  margin: 0.5rem 0;
}

.grand-total-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding-top: 0.35rem;
}

.total-lbl {
  font-size: 0.85rem;
  font-weight: 800;
  color: #0f172a;
  display: block;
}

.khr-equiv {
  font-size: 0.75rem;
  color: #64748b;
  display: block;
  margin-top: 0.15rem;
}

.total-usd {
  font-size: 1.25rem;
  font-weight: 900;
  color: #ea580c;
}

/* Sheet Footer */
.sheet-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #e2e8f0;
  padding-top: 1.25rem;
}

.signature-col {
  text-align: center;
}

.stamp-seal {
  display: inline-block;
  border: 2px dashed #ea580c;
  border-radius: 50%;
  padding: 4px;
  transform: rotate(-10deg);
}

.seal-inner {
  width: 90px;
  height: 90px;
  border: 1px solid #ea580c;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #ea580c;
}

.seal-title {
  font-size: 0.55rem;
  font-weight: 900;
}

.seal-verified {
  font-size: 0.7rem;
  font-weight: 900;
  border-top: 1px solid #ea580c;
  border-bottom: 1px solid #ea580c;
  padding: 1px 0;
  margin: 2px 0;
}

.seal-loc {
  font-size: 0.5rem;
  font-weight: 700;
}

.sig-title {
  font-size: 0.7rem;
  color: #64748b;
  margin: 0.35rem 0 0 0;
  font-weight: 600;
}

.footer-note {
  max-width: 60%;
  text-align: right;
  font-size: 0.72rem;
  color: #64748b;
  line-height: 1.4;
}

.footer-note p {
  margin: 0 0 0.2rem 0;
}

/* Print CSS Rules */
@media print {
  body * {
    visibility: hidden;
  }

  .no-print {
    display: none !important;
  }

  .invoice-modal-backdrop {
    position: static;
    background: transparent;
    padding: 0;
  }

  .invoice-dialog {
    max-width: 100%;
    border: none;
    box-shadow: none;
    background: transparent;
  }

  #printable-invoice,
  #printable-invoice * {
    visibility: visible;
  }

  #printable-invoice {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 1.5cm;
    background: #ffffff !important;
    color: #000000 !important;
  }
}
</style>
