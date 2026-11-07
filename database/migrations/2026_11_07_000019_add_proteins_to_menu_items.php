<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Choosable protein variants, e.g. ["Pork","Chicken","Seafood"].
            // Null / empty = the dish has no protein choice.
            $table->json('proteins')->nullable()->after('has_spice_note');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('proteins');
        });
    }
};
