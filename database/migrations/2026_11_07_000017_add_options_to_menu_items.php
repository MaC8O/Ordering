<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Large-size price in minor units; null = no size choice (single price).
            $table->unsignedInteger('large_price')->nullable()->after('price');
            // Whether a fried / half-boiled egg add-on (+fee) is offered.
            $table->boolean('has_egg_addon')->default(false)->after('large_price');
            // Whether the optional "how spicy?" free-text note is offered.
            $table->boolean('has_spice_note')->default(false)->after('has_egg_addon');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['large_price', 'has_egg_addon', 'has_spice_note']);
        });
    }
};
