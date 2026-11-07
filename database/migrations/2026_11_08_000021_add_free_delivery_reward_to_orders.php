<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Marks an order that redeemed a loyalty free-delivery reward, so each
            // earned reward is consumed exactly once (punch-card, not free forever).
            $table->boolean('is_free_delivery_reward')->default(false)->after('is_preorder');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('is_free_delivery_reward');
        });
    }
};
