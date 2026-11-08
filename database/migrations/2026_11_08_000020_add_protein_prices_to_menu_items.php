<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Per-protein prices, keyed by protein name, e.g.
            //   {"Pork": {"normal": 3000, "large": 4000}, "Seafood": {"normal": 4000, "large": 5000}}
            // Prices are in the smallest currency unit (same as `price`). A null
            // "large" means that protein has no large size. Null / empty for the
            // whole column = the dish has no per-protein pricing and falls back to
            // the dish-level `price` / `large_price`.
            $table->json('protein_prices')->nullable()->after('proteins');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('protein_prices');
        });
    }
};
