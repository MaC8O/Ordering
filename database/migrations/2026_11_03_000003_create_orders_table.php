<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('delivery_dorm');
            $table->string('delivery_room');
            $table->text('delivery_notes')->nullable();
            $table->time('preferred_time')->nullable();
            $table->enum('payment_method', ['qr', 'cash']);
            $table->string('payment_screenshot_path')->nullable();
            $table->enum('payment_status', ['pending', 'verified', 'failed'])->default('pending');
            $table->enum('order_status', [
                'pending',
                'confirmed',
                'preparing',
                'out_for_delivery',
                'completed',
                'cancelled',
            ])->default('pending');
            // Monetary values stored in minor units (integer).
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('total');
            $table->text('notes')->nullable();
            // Short admin-visible note shown to the customer (e.g. why a payment failed).
            $table->string('admin_note')->nullable();
            $table->timestamps();

            $table->index('order_status');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
