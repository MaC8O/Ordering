<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Optional exact delivery date for the preorder batch. When set, it
            // overrides the weekly fulfillment-weekday calculation. Null = use
            // the recurring weekday schedule as before.
            $table->date('preorder_fulfillment_date')->nullable()->after('preorder_fulfillment_day');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('preorder_fulfillment_date');
        });
    }
};
