<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->json('items')->nullable();
            $table->string('promo_code')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->json('address_snapshot');
            $table->string('status', 30)->default('pending');
            $table->string('payment_method', 20)->default('cod');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'failed'])->default('unpaid');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('driver_fee', 10, 2)->default(0);
            $table->decimal('distance_km', 6, 2)->default(0);
            $table->decimal('estimated_minutes', 5, 1)->nullable();
            $table->decimal('prep_time_minutes', 5, 1)->nullable();
            $table->string('promo_code', 40)->nullable();
            $table->string('client_note')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->string('reject_reason')->nullable();
            $table->string('delivery_otp', 10)->nullable();
            $table->string('proof_photo_path')->nullable();
            $table->decimal('rating_merchant', 3, 2)->nullable();
            $table->decimal('rating_driver', 3, 2)->nullable();
            $table->text('review_merchant')->nullable();
            $table->text('review_driver')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['merchant_id', 'status']);
            $table->index(['driver_id', 'status']);
            $table->index('user_id');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->json('product_name');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('options_price', 10, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->json('selected_options')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained();
            $table->enum('assignment_type', ['auto', 'manual'])->default('auto');
            $table->enum('state', ['offered', 'accepted_offer', 'declined', 'assigned', 'completed', 'cancelled'])->default('assigned');
            $table->timestamp('offered_at')->nullable();
            $table->timestamp('offer_expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('arrived_merchant_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('tip', 10, 2)->default(0);
            $table->string('decline_reason')->nullable();
            $table->timestamps();
            $table->index(['driver_id', 'state']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20)->default('cod');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_given', 12, 2)->nullable();
            $table->decimal('change_due', 12, 2)->nullable();
            $table->enum('status', ['pending', 'settled', 'refunded', 'failed'])->default('pending');
            $table->string('gateway_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('commission_deducted', 12, 2);
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cash_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'completed', 'disputed'])->default('pending');
            $table->text('note')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('merchant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('target', ['merchant', 'driver']);
            $table->decimal('rating', 3, 2);
            $table->text('comment')->nullable();
            $table->text('merchant_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'target']);
        });
    }

    public function down(): void
    {
        foreach (['reviews','cash_settlements','payouts','payments','deliveries','order_status_history','order_items','orders','carts'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
