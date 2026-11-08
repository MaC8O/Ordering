<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Admin override: when on (and preorders enabled), the collection
            // window is open right now regardless of the weekly schedule, until
            // the admin turns it off again. Lets the kitchen open a batch on demand.
            $table->boolean('preorder_force_open')->default(false)->after('preorder_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('preorder_force_open');
        });
    }
};
