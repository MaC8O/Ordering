<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time correction for the timezone switch from Asia/Kuala_Lumpur (UTC+8)
 * to Asia/Bangkok (UTC+7).
 *
 * Laravel stores timestamps as wall-clock time in the app timezone with no UTC
 * conversion, so every existing order's created_at / updated_at / eta_at was
 * recorded in UTC+8. Read back under the new UTC+7 timezone they would display
 * one hour ahead of when the order really happened. Shift them back one hour so
 * historical records match real Thailand time. New orders are already correct.
 */
return new class extends Migration
{
    /** Datetime columns on `orders` that hold a real moment (not a plain date). */
    protected array $columns = ['created_at', 'updated_at', 'eta_at'];

    public function up(): void
    {
        $this->shift('-');
    }

    public function down(): void
    {
        $this->shift('+');
    }

    protected function shift(string $sign): void
    {
        $driver = DB::getDriverName();

        foreach ($this->columns as $col) {
            if (! Schema::hasColumn('orders', $col)) {
                continue;
            }

            if ($driver === 'sqlite') {
                DB::statement(
                    "UPDATE orders SET {$col} = datetime({$col}, '{$sign}1 hour') WHERE {$col} IS NOT NULL"
                );
            } else {
                // MySQL / MariaDB
                $op = $sign === '-' ? 'DATE_SUB' : 'DATE_ADD';
                DB::statement(
                    "UPDATE orders SET {$col} = {$op}({$col}, INTERVAL 1 HOUR) WHERE {$col} IS NOT NULL"
                );
            }
        }
    }
};
