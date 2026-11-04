<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('delivery_dorm');
            // Room number is no longer collected (orders drop at the lobby/monitor),
            // but keep the column nullable so historical orders stay intact.
            $table->string('delivery_room')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
            $table->string('delivery_room')->nullable(false)->change();
        });
    }
};
