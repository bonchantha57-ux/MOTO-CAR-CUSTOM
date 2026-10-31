<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vehicle Types (Motorcycle / Car)
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Motorcycle, Car
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Vehicle Makes / Brands
        Schema::create('vehicle_makes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Honda, Yamaha, Toyota
            $table->string('slug')->unique();
            $table->string('logo')->nullable(); // Cloudflare R2
            $table->string('country_of_origin')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Vehicle Models
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_make_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // CB500F, Civic, Camry
            $table->string('slug')->unique();
            $table->string('generation')->nullable(); // 2nd Gen
            $table->string('body_style')->nullable(); // Sedan, SUV, Sport
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Vehicle Production Years
        Schema::create('vehicle_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_model_id')->constrained()->cascadeOnDelete();
            $table->integer('year_start');
            $table->integer('year_end')->nullable();
            $table->string('trim_level')->nullable(); // Base, Sport, Premium
            $table->string('engine_cc')->nullable(); // 500cc, 1800cc
            $table->timestamps();
        });

        // User's My Garage (saved vehicles)
        Schema::create('user_garages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_make_id')->constrained();
            $table->foreignId('vehicle_model_id')->constrained();
            $table->foreignId('vehicle_year_id')->constrained('vehicle_years');
            $table->string('nickname')->nullable(); // "My Honda CB500"
            $table->string('color')->nullable();
            $table->string('plate_number')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_garages');
        Schema::dropIfExists('vehicle_years');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_makes');
        Schema::dropIfExists('vehicle_types');
    }
};
