<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    /**
     * Generate a daily-reset, human-readable order number: ORD-YYYYMMDD-NNN.
     * Counts existing orders created today and increments.
     *
     * NOTE: this is best-effort only. Under concurrent checkouts two requests
     * can compute the same number (neither is committed yet, so both pass the
     * probe below), so the generated number MUST be inserted via createOrder(),
     * which retries on the resulting unique-constraint collision. Calling this
     * and inserting yourself risks silently losing an order to that race.
     */
    public function generate(): string
    {
        $today = Carbon::today();
        $datePart = $today->format('Ymd');

        $countToday = Order::whereDate('created_at', $today)->count();

        // Guard against collisions (e.g. concurrent inserts) by probing.
        do {
            $countToday++;
            $candidate = sprintf('ORD-%s-%03d', $datePart, $countToday);
        } while (Order::where('order_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Create an order (and its line items) with a guaranteed-unique order
     * number, atomically. If two orders are placed in the same instant and the
     * generated numbers collide, the losing insert hits the unique constraint
     * on order_number; we catch that, regenerate, and retry — so an order is
     * never silently lost to the race.
     *
     * @param  array<string, mixed>  $attributes  Order attributes WITHOUT order_number.
     * @param  array<int, array<string, mixed>>  $lines  Line items for createMany().
     */
    public function createOrder(array $attributes, array $lines): Order
    {
        $maxAttempts = 5;

        for ($attempt = 1; ; $attempt++) {
            try {
                return DB::transaction(function () use ($attributes, $lines) {
                    $order = Order::create($attributes + ['order_number' => $this->generate()]);
                    $order->items()->createMany($lines);

                    return $order;
                });
            } catch (QueryException $e) {
                if ($attempt < $maxAttempts && $this->isDuplicateOrderNumber($e)) {
                    // Brief jitter so racing requests don't keep colliding.
                    usleep(random_int(1000, 10000));

                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * Whether the query exception is a unique-constraint violation on the
     * order_number column (MySQL 1062 / generic SQLSTATE 23000).
     */
    protected function isDuplicateOrderNumber(QueryException $e): bool
    {
        return $e->getCode() === '23000'
            && stripos($e->getMessage(), 'order_number') !== false;
    }
}
