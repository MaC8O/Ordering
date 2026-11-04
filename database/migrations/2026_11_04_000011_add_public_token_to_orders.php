<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Unguessable public identifier used in customer-facing URLs,
            // so order pages can't be enumerated via the sequential number.
            $table->string('public_token', 32)->nullable()->unique()->after('order_number');
        });

        // Backfill any existing orders with a random token.
        DB::table('orders')->select('id')->whereNull('public_token')->orderBy('id')
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('orders')->where('id', $row->id)
                        ->update(['public_token' => Str::random(32)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
