<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule', function (Blueprint $table) {
            $table->id();
            // 0 = Sunday ... 6 = Saturday (matches PHP's date('w')).
            $table->unsignedTinyInteger('weekday')->unique();
            $table->boolean('is_open')->default(true);
            $table->time('open_time')->default('10:00');
            $table->time('close_time')->default('21:00');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule');
    }
};
