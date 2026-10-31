# 🏍️ MotoCar Custom - Full E-Commerce Platform

> **Full-Stack Motorcycle & Car Custom Parts E-Commerce**  
> Backend: Laravel 10 API | Frontend: Vue.js 3 + TypeScript | Storage: Cloudflare R2 + D1

---

## 📦 Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend API** | Laravel 10 (PHP 8.1+) |
| **Frontend** | Vue.js 3 + TypeScript + Vite |
| **State Management** | Pinia |
| **Routing** | Vue Router 4 |
| **UI Framework** | Custom CSS + Headless |
| **Image Storage** | Cloudflare R2 (S3-compatible) |
| **Database Cache** | Cloudflare D1 (SQLite edge) |
| **Primary Database** | MySQL (via XAMPP / PlanetScale) |
| **Auth** | Laravel Sanctum (JWT tokens) |
| **Payment** | KHQR (Bakong) Mock + Cash on Delivery |
| **HTTP Client** | Axios |

---

## 📁 Project Structure

```
Project E-Commer Website/
├── backend/          # Laravel 10 API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── Auth/
│   │   │   ├── Shop/
│   │   │   ├── Order/
│   │   │   ├── B2B/
│   │   │   ├── Garage/
│   │   │   └── Admin/
│   │   ├── Models/
│   │   └── Services/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/api.php
│
├── frontend/         # Vue.js 3 + TypeScript
│   ├── src/
│   │   ├── views/
│   │   │   ├── Home/
│   │   │   ├── Shop/
│   │   │   ├── Product/
│   │   │   ├── Cart/
│   │   │   ├── Checkout/
│   │   │   ├── Account/
│   │   │   ├── Auth/
│   │   │   └── Admin/
│   │   ├── components/
│   │   ├── stores/ (Pinia)
│   │   ├── router/
│   │   ├── composables/
│   │   └── services/ (API calls)
│   └── public/
│
└── README.md
```

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+ with Composer
- Node.js 18+ with npm
- MySQL / XAMPP
- Cloudflare Account (R2 + D1)

### Backend Setup
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8000
```

### Frontend Setup
```bash
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

---

## 🔧 Environment Variables

### Backend (.env)
```env
APP_NAME="MotoCar Custom"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=motocar_custom
DB_USERNAME=root
DB_PASSWORD=

# Cloudflare R2 (Image Storage)
CLOUDFLARE_R2_ACCESS_KEY_ID=your_key
CLOUDFLARE_R2_SECRET_ACCESS_KEY=your_secret
CLOUDFLARE_R2_BUCKET=motocar-images
CLOUDFLARE_R2_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://pub-YOUR_HASH.r2.dev

# Cloudflare D1 (Edge Database)
CLOUDFLARE_D1_DATABASE_ID=your_d1_id
CLOUDFLARE_ACCOUNT_ID=your_account_id
CLOUDFLARE_API_TOKEN=your_api_token

# KHQR/Bakong (Mock for now)
BAKONG_API_URL=https://api-bakong.nbc.gov.kh
BAKONG_API_TOKEN=mock_token
```

### Frontend (.env.local)
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_NAME="MotoCar Custom"
VITE_CLOUDFLARE_R2_PUBLIC_URL=https://pub-YOUR_HASH.r2.dev
```

---

## 🗺️ Features Overview

### Customer Portal
- ✅ Home with Hero Banner, Categories, Promotions
- ✅ Shop with Vehicle Compatibility Filter
- ✅ Product Detail with B2C/B2B Pricing
- ✅ Wishlist / My Garage
- ✅ Shopping Cart with Promo Codes
- ✅ Checkout with KHQR / Cash / B2B Credit
- ✅ Order Tracking
- ✅ Partner Garage Directory & Booking
- ✅ My Account Dashboard
- ✅ B2B Registration & Approval

### Admin Portal
- ✅ Dashboard with KPI Analytics
- ✅ Vehicle Fitment Master Database
- ✅ Product Management (B2C + B2B Pricing)
- ✅ Order Management (B2C + B2B Wholesale)
- ✅ B2B & RFQ Management
- ✅ Partner Garage Network
- ✅ Customer Directory
- ✅ Payment & Finance
- ✅ Promotions & Marketing
- ✅ CMS Content Management
- ✅ Reports & Business Intelligence
- ✅ Roles & Permissions

---

## 💳 Payment Methods
1. **KHQR (Bakong)** - QR Code payment (Mock simulation)
2. **Cash on Delivery (COD)** - Pay on delivery
3. **B2B Credit Term** - Net 30/60 days for wholesale clients

---

## 🏍️ Vehicle Compatibility System
- Filter by: Type (Motorcycle/Car) → Make → Model → Year
- Universal Fit vs Direct Bolt-on products
- Fitment search logging for analytics

---

*Built with ❤️ for MotoCar Custom Cambodia*
