<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Partner Garages
        Schema::create('partner_garages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable(); // Cloudflare R2
            $table->json('images')->nullable(); // Array of R2 URLs
            
            // Contact
            $table->string('phone');
            $table->string('telegram_username')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            
            // Location
            $table->string('address');
            $table->string('city')->default('Phnom Penh');
            $table->string('province')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('google_maps_link')->nullable();
            
            // Operating Hours
            $table->json('operating_hours')->nullable(); // { mon: "8am-6pm", ... }
            
            // Services
            $table->json('service_capabilities')->nullable(); // ["motorcycle_tuning", "car_bodykit", ...]
            
            // B2B
            $table->boolean('is_b2b_partner')->default(false);
            $table->string('business_tax_id')->nullable();
            $table->string('patent_number')->nullable();
            $table->json('documents')->nullable(); // Verification docs on R2
            
            // Status
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->boolean('is_featured')->default(false);
            
            // Metrics
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('total_installations')->default(0);
            
            // Commission
            $table->decimal('commission_rate', 5, 2)->default(0); // %
            
            $table->timestamps();
            $table->softDeletes();
        });

        // Installation Bookings
        Schema::create('garage_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique(); // MCB-2024-001
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('partner_garage_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            
            // Vehicle
            $table->foreignId('user_garage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vehicle_description')->nullable(); // Manual entry
            
            // Booking Details
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->text('services_requested')->nullable();
            $table->text('notes')->nullable();
            
            // Status
            $table->enum('status', [
                'scheduled', 'vehicle_arrived', 'in_progress', 'completed', 'cancelled', 'no_show'
            ])->default('scheduled');
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Service
            $table->decimal('service_fee', 10, 2)->nullable();
            $table->decimal('garage_commission', 10, 2)->nullable();
            $table->enum('payout_status', ['pending', 'paid'])->default('pending');
            
            $table->timestamps();
        });

        // B2B Client Profiles
        Schema::create('b2b_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('business_tax_id')->nullable();
            $table->string('patent_number')->nullable();
            $table->string('business_type')->nullable(); // Garage, Depot, Reseller
            $table->string('contact_person');
            $table->string('phone');
            $table->string('address');
            $table->json('documents')->nullable(); // Verification docs on R2
            
            // Credit & Tier
            $table->enum('tier_level', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->decimal('credit_limit', 10, 2)->default(0);
            $table->decimal('current_credit_used', 10, 2)->default(0);
            $table->integer('credit_term_days')->default(30); // Net 30
            
            // Status
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });

        // RFQ (Request for Quote)
        Schema::create('rfq_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number')->unique(); // RFQ-2024-001
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable(); // if product no longer exists
            $table->integer('quantity');
            $table->text('requirements')->nullable();
            $table->string('target_price')->nullable();
            $table->json('attachments')->nullable(); // R2 URLs
            
            // Response
            $table->enum('status', ['pending', 'quoted', 'accepted', 'rejected', 'converted'])->default('pending');
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->decimal('quoted_total', 10, 2)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); // After conversion
            
            $table->timestamps();
        });

        // Reviews
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('rating'); // 1-5
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('images')->nullable(); // R2 URLs
            $table->enum('status', ['pending', 'approved', 'flagged', 'rejected'])->default('pending');
            $table->timestamps();
        });

        // Garage Reviews
        Schema::create('garage_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_garage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garage_booking_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('rating');
            $table->text('body')->nullable();
            $table->enum('status', ['pending', 'approved', 'flagged'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garage_reviews');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('rfq_requests');
        Schema::dropIfExists('b2b_profiles');
        Schema::dropIfExists('garage_bookings');
        Schema::dropIfExists('partner_garages');
    }
};
