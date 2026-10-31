<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Addresses
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable(); // Home, Office, Garage
            $table->string('full_name');
            $table->string('phone');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city')->default('Phnom Penh');
            $table->string('province')->nullable();
            $table->string('country')->default('Cambodia');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // MCO-2024-001234
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            
            // Order Type
            $table->enum('order_type', ['b2c', 'b2b'])->default('b2c');
            
            // Order Status
            $table->enum('status', [
                'pending', 'confirmed', 'processing', 
                'shipped', 'delivered', 'cancelled', 'refunded'
            ])->default('pending');
            
            // Pricing
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('USD'); // USD or KHR
            
            // Payment
            $table->enum('payment_method', ['khqr', 'cod', 'b2b_credit', 'bank_transfer'])->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Coupon
            $table->string('coupon_code')->nullable();
            
            // Shipping
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // B2B specific
            $table->integer('b2b_credit_term_days')->nullable(); // Net 30, Net 60
            $table->timestamp('b2b_due_date')->nullable();
            $table->boolean('b2b_invoice_generated')->default(false);
            $table->string('b2b_invoice_number')->nullable();
            
            // Installation
            $table->boolean('requires_installation')->default(false);
            $table->foreignId('garage_booking_id')->nullable()->constrained('garage_bookings')->nullOnDelete();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Order Items
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name'); // Snapshot
            $table->string('product_sku');  // Snapshot
            $table->string('product_image')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->enum('price_type', ['b2c', 'b2b_tier'])->default('b2c');
            $table->json('specifications')->nullable();
            $table->timestamps();
        });

        // Coupons
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed_amount', 'free_shipping'])->default('percentage');
            $table->decimal('value', 10, 2); // 20 = 20% or $20
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->integer('per_user_limit')->default(1);
            $table->enum('applies_to', ['all', 'b2c', 'b2b'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Cart (persistent)
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable(); // Guest cart
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->json('options')->nullable();
            $table->timestamps();
        });

        // Wishlist
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
    }
};
