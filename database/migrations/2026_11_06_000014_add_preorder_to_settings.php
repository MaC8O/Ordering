<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Master switch for the weekly preorder batch.
            $table->boolean('preorder_enabled')->default(false)->after('accepting_orders');

            // Collection window: from (open day/time) until (cutoff day/time).
            // Weekdays follow Carbon's dayOfWeek: 0 = Sunday … 6 = Saturday.
            $table->unsignedTinyInteger('preorder_open_day')->default(6);      // Saturday
            $table->string('preorder_open_time', 5)->default('20:00');
            $table->unsignedTinyInteger('preorder_cutoff_day')->default(0);    // Sunday
            $table->string('preorder_cutoff_time', 5)->default('09:00');

            // Fulfillment: one batch cooked and delivered on this day, in this window.
            $table->unsignedTinyInteger('preorder_fulfillment_day')->default(1); // Monday
            $table->string('preorder_window_start', 5)->default('10:00');
            $table->string('preorder_window_end', 5)->default('12:00');

            // Flat delivery fee for the shared batch run (minor units; 0 = free).
            $table->unsignedInteger('preorder_delivery_fee')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'preorder_enabled',
                'preorder_open_day',
                'preorder_open_time',
                'preorder_cutoff_day',
                'preorder_cutoff_time',
                'preorder_fulfillment_day',
                'preorder_window_start',
                'preorder_window_end',
                'preorder_delivery_fee',
            ]);
        });
    }
};
