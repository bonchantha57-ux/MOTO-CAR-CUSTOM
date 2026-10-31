<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Super Admin (only create if missing)
        if (!User::where('email', 'admin@motocar.com')->exists()) {
            User::create([
                'name' => 'MotoCar Director (Admin)',
                'email' => 'admin@motocar.com',
                'password' => Hash::make('admin1234'),
                'phone' => '+855 23 888 999',
                'role' => 'super_admin',
                'user_mode' => 'b2c',
                'status' => 'active',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&h=200&q=80',
            ]);
        }

        // 4. Categories
        $categories = [
            ['id' => 1, 'name' => 'Exhaust Systems', 'slug' => 'exhaust-systems', 'description' => 'Full titanium & stainless racing exhausts', 'image' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=600&q=80', 'sort_order' => 1, 'is_active' => true, 'show_in_home' => true],
            ['id' => 2, 'name' => 'Performance Brakes', 'slug' => 'performance-brakes', 'description' => 'Monobloc radial calipers, floating discs & racing pads', 'image' => 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=600&q=80', 'sort_order' => 2, 'is_active' => true, 'show_in_home' => true],
            ['id' => 3, 'name' => 'Suspension & Coilovers', 'slug' => 'suspension-coilovers', 'description' => 'MotoGP spec shocks, inverted forks & track coilovers', 'image' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?auto=format&fit=crop&w=600&q=80', 'sort_order' => 3, 'is_active' => true, 'show_in_home' => true],
            ['id' => 4, 'name' => 'Forged Wheels', 'slug' => 'forged-wheels', 'description' => 'Ultralight aerospace aluminum & magnesium forged rims', 'image' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=600&q=80', 'sort_order' => 4, 'is_active' => true, 'show_in_home' => true],
            ['id' => 5, 'name' => 'Carbon Aerodynamics', 'slug' => 'carbon-aerodynamics', 'description' => 'Dry carbon fiber wings, diffusers & bodywork', 'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=600&q=80', 'sort_order' => 5, 'is_active' => true, 'show_in_home' => true],
        ];
        foreach ($categories as $cat) {
            DB::table('categories')->updateOrInsert(['id' => $cat['id']], array_merge($cat, ['created_at' => now(), 'updated_at' => now()]));
        }

        // 5. Brands
        $brands = [
            ['id' => 1, 'name' => 'Akrapovič', 'slug' => 'akrapovic', 'country' => 'Slovenia', 'logo' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=200&q=80', 'description' => 'World champion titanium exhaust manufacturer', 'is_active' => true],
            ['id' => 2, 'name' => 'Brembo', 'slug' => 'brembo', 'country' => 'Italy', 'logo' => 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=200&q=80', 'description' => 'Legendary high performance brake technology', 'is_active' => true],
            ['id' => 3, 'name' => 'Öhlins Racing', 'slug' => 'ohlins', 'country' => 'Sweden', 'logo' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?auto=format&fit=crop&w=200&q=80', 'description' => 'The golden standard in racing suspension', 'is_active' => true],
            ['id' => 4, 'name' => 'Yoshimura', 'slug' => 'yoshimura', 'country' => 'Japan', 'logo' => 'https://images.unsplash.com/photo-1568772585407-9361f9bf3a87?auto=format&fit=crop&w=200&q=80', 'description' => 'Japanese precision racing performance since 1954', 'is_active' => true],
            ['id' => 5, 'name' => 'GTS Forged Wheels', 'slug' => 'gts-forged', 'country' => 'Germany', 'logo' => '/images/forged-wheel.jpg', 'description' => 'Aerospace grade custom forged monoblock and modular wheel systems', 'is_active' => true],
        ];
        foreach ($brands as $brand) {
            DB::table('brands')->updateOrInsert(['id' => $brand['id']], array_merge($brand, ['created_at' => now(), 'updated_at' => now()]));
        }

        // 6. Products (preserve user products; only insert default if not existing)
        $products = [
            [
                'id' => 1,
                'category_id' => 1,
                'brand_id' => 1,
                'name' => 'Akrapovic Racing Line Titanium Full Exhaust System',
                'slug' => 'akrapovic-racing-line-titanium-full-exhaust-system',
                'sku' => 'MCC-EXH-001',
                'short_description' => 'Ultra-lightweight titanium full system with carbon fiber end-cap. +8.5 HP dyno proven.',
                'description' => 'Engineered for maximum power output and dramatic weight reduction. Crafted from proprietary high-grade titanium alloy with handcrafted carbon fiber heat shield. Guaranteed 100% bolt-on fitment for Honda CB650R, CBR650R, Yamaha MT-09 and Kawasaki ZX-6R.',
                'thumbnail' => '/images/exhaust-akrapovic.jpg',
                'b2c_price' => 1280.00,
                'b2b_price' => 990.00,
                'compare_at_price' => 1450.00,
                'stock_quantity' => 12,
                'status' => 'active',
                'is_featured' => true,
                'is_new_arrival' => true,
                'rating_average' => 4.9,
                'rating_count' => 42,
            ],
            [
                'id' => 2,
                'category_id' => 2,
                'brand_id' => 2,
                'name' => 'Brembo Racing Monobloc Stylema Radial Brake Caliper Kit',
                'slug' => 'brembo-racing-monobloc-stylema-radial-calipers',
                'sku' => 'MCC-BRK-002',
                'short_description' => 'Top-tier 100mm radial mounting calipers with 4x30mm pistons and cooling duct technology.',
                'description' => 'Machined from a single block of alloy for extreme rigidity and thermal dissipation under aggressive track braking conditions. Includes sintered race compound pads and titanium hardware.',
                'thumbnail' => '/images/brembo-brakes.jpg',
                'b2c_price' => 890.00,
                'b2b_price' => 720.00,
                'compare_at_price' => 1050.00,
                'stock_quantity' => 20,
                'status' => 'active',
                'is_featured' => true,
                'is_new_arrival' => false,
                'rating_average' => 5.0,
                'rating_count' => 28,
            ],
            [
                'id' => 3,
                'category_id' => 3,
                'brand_id' => 3,
                'name' => 'Öhlins TTX GP Multi-Adjustable Rear Shock Absorber',
                'slug' => 'ohlins-ttx-gp-multi-adjustable-rear-shock',
                'sku' => 'MCC-SUS-003',
                'short_description' => 'MotoGP twin-tube technology with independent compression and rebound adjusters.',
                'description' => 'Eliminates cavitation under high-frequency bumps, giving superior rear traction, feedback and tire longevity on street and circuit. 100% bolt-on with remote preload adjuster.',
                'thumbnail' => '/images/ohlins-suspension.jpg',
                'b2c_price' => 1450.00,
                'b2b_price' => 1180.00,
                'compare_at_price' => 1650.00,
                'stock_quantity' => 8,
                'status' => 'active',
                'is_featured' => true,
                'is_new_arrival' => true,
                'rating_average' => 4.9,
                'rating_count' => 31,
            ],
            [
                'id' => 4,
                'category_id' => 4,
                'brand_id' => 5,
                'name' => 'GTS Ultra-Lightweight Concave Forged Monoblock Wheel',
                'slug' => 'gts-ultra-lightweight-concave-forged-monoblock-wheel',
                'sku' => 'MCC-WHL-004',
                'short_description' => 'Aerospace-grade 6061-T6 forged aluminum concave wheels with custom PCD and offset.',
                'description' => 'Custom engineered forged monoblock wheel providing extreme weight savings (-4.2 kg per corner) and maximum brake clearance. Available in Satin Bronze, Gloss Black, and Brushed Titanium.',
                'thumbnail' => '/images/forged-wheel.jpg',
                'b2c_price' => 2400.00,
                'b2b_price' => 1850.00,
                'compare_at_price' => 2800.00,
                'stock_quantity' => 6,
                'status' => 'active',
                'is_featured' => true,
                'is_new_arrival' => true,
                'rating_average' => 5.0,
                'rating_count' => 19,
            ],
            [
                'id' => 5,
                'category_id' => 5,
                'brand_id' => 4,
                'name' => 'Yoshimura Dry Carbon Fiber Aerodynamic Heat Shield Kit',
                'slug' => 'yoshimura-dry-carbon-fiber-heat-shield-kit',
                'sku' => 'YOSH-CF-H125-01',
                'short_description' => '100% dry autoclave 3K twill carbon fiber, UV-resistant gloss finish, -1.8 kg weight reduction.',
                'description' => 'Crafted from aerospace-grade 3K twill pre-preg carbon fiber cured in high-pressure autoclave. Coated with triple-layer automotive UV protective clear coat withstanding up to 300°C.',
                'thumbnail' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=800&q=80',
                'b2c_price' => 180.00,
                'b2b_price' => 145.00,
                'compare_at_price' => 220.00,
                'stock_quantity' => 15,
                'status' => 'active',
                'is_featured' => true,
                'is_new_arrival' => false,
                'rating_average' => 4.8,
                'rating_count' => 14,
            ],
        ];

        foreach ($products as $prod) {
            DB::table('products')->updateOrInsert(['id' => $prod['id']], array_merge($prod, ['created_at' => now(), 'updated_at' => now()]));
        }
    }
}
