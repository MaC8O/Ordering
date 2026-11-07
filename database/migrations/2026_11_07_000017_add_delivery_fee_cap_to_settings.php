<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Ceiling for the per-item delivery fee (minor units; 0 = no cap).
            // Stops large orders from being gouged. Default ฿50.
            $table->unsignedInteger('delivery_fee_cap')->default(5000)->after('default_delivery_fee');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('delivery_fee_cap');
        });
    }
};
