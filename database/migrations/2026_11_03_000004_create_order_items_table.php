<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Nullable + nullOnDelete so order history survives if a menu item is hard-deleted.
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            // Snapshot of the item name and price at the moment the order was placed.
            $table->string('item_name_snapshot');
            $table->unsignedInteger('item_price_snapshot');
            $table->string('item_note')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
