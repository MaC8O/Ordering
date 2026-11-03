<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name')->default('Campus Kitchen');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('account_name')->nullable();
            $table->string('qr_image_path')->nullable();
            // Stop accepting orders this many minutes before closing time.
            $table->unsignedInteger('cutoff_minutes_before_close')->default(30);
            // Delivery fee in minor units.
            $table->unsignedInteger('default_delivery_fee')->default(0);
            // Manual master switch: when false, ordering is closed regardless of schedule.
            $table->boolean('accepting_orders')->default(true);
            // Admin-editable list of dorms/buildings, stored as JSON.
            $table->json('dorms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
