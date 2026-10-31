<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('name_km')->nullable(); // Khmer name
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable(); // Cloudflare R2
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_in_home')->default(false);
            $table->timestamps();
        });

        // Aftermarket Brands
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable(); // Cloudflare R2
            $table->string('country')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_km')->nullable();
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('thumbnail')->nullable(); // Cloudflare R2
            
            // Pricing
            $table->decimal('b2c_price', 10, 2); // Retail price
            $table->decimal('compare_at_price', 10, 2)->nullable(); // Original price (for sale)
            $table->decimal('cost_price', 10, 2)->nullable(); // Internal cost
            
            // Stock
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(10);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('allow_backorder')->default(false);
            
            // Product Type
            $table->enum('type', ['physical', 'service', 'bundle'])->default('physical');
            $table->boolean('requires_installation')->default(false);
            $table->boolean('is_universal_fit')->default(false);
            $table->string('installation_tag')->nullable();
            
            // B2B Settings
            $table->boolean('available_for_b2b')->default(true);
            $table->integer('min_order_qty_b2b')->default(1);
            
            // Shipping
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'draft', 'archived'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->boolean('is_on_sale')->default(false);
            
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('sold_count')->default(0);
            $table->integer('view_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });

        // Product Images (Cloudflare R2)
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('url'); // Cloudflare R2 URL
            $table->string('alt_text')->nullable();
            $table->enum('type', ['image', 'installation_diagram', 'video_thumbnail'])->default('image');
            $table->string('video_url')->nullable(); // YouTube or R2 video
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Product Specifications
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Material, Color, Finish
            $table->string('value'); // Aluminum, Red, Glossy
            $table->string('unit')->nullable(); // mm, kg
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // B2B Wholesale Tier Pricing
        Schema::create('b2b_tier_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->string('tier_name')->nullable(); // Bronze, Silver, Gold
            $table->timestamps();
        });

        // Product Vehicle Fitment (Many-to-Many)
        Schema::create('product_vehicle_fitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_make_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_year_id')->nullable()->constrained('vehicle_years')->cascadeOnDelete();
            $table->boolean('is_universal')->default(false);
            $table->text('fitment_notes')->nullable();
            $table->timestamps();
        });

        // Compatible Accessories (Cross-sell)
        Schema::create('product_accessories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accessory_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_accessories');
        Schema::dropIfExists('product_vehicle_fitments');
        Schema::dropIfExists('b2b_tier_prices');
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
