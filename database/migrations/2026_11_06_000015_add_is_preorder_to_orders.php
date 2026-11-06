<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Marks an order placed through the weekly preorder batch.
            $table->boolean('is_preorder')->default(false)->after('order_status');
            $table->index(['is_preorder', 'delivery_date']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['is_preorder', 'delivery_date']);
            $table->dropColumn('is_preorder');
        });
    }
};
