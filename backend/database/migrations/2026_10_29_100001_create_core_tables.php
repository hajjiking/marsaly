<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('icon')->nullable();
            $table->string('slug')->unique();
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('phone', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->foreignId('role_id')->constrained();
            $table->string('locale', 5)->default('ar');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('code', 10);
            $table->string('purpose', 30)->default('login');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['phone', 'purpose', 'expires_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->timestamps();
        });

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('city')->default('El Marsa');
            $table->decimal('base_fee', 8, 2);
            $table->decimal('per_km_fee', 8, 2);
            $table->decimal('minimum_order', 8, 2)->default(0);
            $table->decimal('service_radius_km', 6, 2)->default(8);
            $table->decimal('center_lat', 10, 7);
            $table->decimal('center_lng', 10, 7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->foreignId('delivery_zone_id')->nullable()->constrained();
            $table->string('business_name');
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('neighborhood')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])->default('pending');
            $table->string('licence_number')->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('licence_document_path')->nullable();
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->boolean('is_busy')->default(false);
            $table->boolean('is_temporarily_closed')->default(false);
            $table->unsignedSmallInteger('prep_time_minutes')->default(20);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'category_id']);
        });

        Schema::create('merchant_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->unique(['merchant_id', 'day_of_week']);
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])->default('pending');
            $table->enum('availability', ['offline', 'online', 'busy'])->default('offline');
            $table->enum('vehicle_type', ['motorbike', 'car', 'bicycle', 'on_foot'])->default('motorbike');
            $table->string('vehicle_model')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('id_document_path')->nullable();
            $table->string('vehicle_document_path')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->decimal('cash_collected_total', 12, 2)->default(0);
            $table->decimal('cash_owed_to_platform', 12, 2)->default(0);
            $table->unsignedInteger('deliveries_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'availability']);
        });

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('accuracy', 6, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['driver_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        foreach (['driver_locations','drivers','merchant_hours','merchants','delivery_zones','settings','otp_codes','users','categories','permission_role','permissions','roles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
