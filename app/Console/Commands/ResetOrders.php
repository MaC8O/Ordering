<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Wipes all orders and their line items — intended for clearing test orders
 * before going live, so revenue figures start from a clean slate. Menu,
 * settings, categories and customer accounts are left untouched.
 */
class ResetOrders extends Command
{
    protected $signature = 'orders:reset
        {--force : Skip the confirmation prompt}
        {--keep-screenshots : Keep uploaded payment screenshot files}';

    protected $description = 'Delete ALL orders and order items (clears test data before launch).';

    public function handle(): int
    {
        $count = Order::count();

        if ($count === 0) {
            $this->info('No orders to delete — nothing to do.');

            return self::SUCCESS;
        }

        $this->warn("This permanently deletes {$count} order(s) and all their line items.");
        $this->line('Menu, settings and customer accounts are NOT affected.');

        if (! $this->option('force') && ! $this->confirm('Proceed? This cannot be undone.')) {
            $this->info('Aborted — nothing was deleted.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            // order_items first (orders is referenced by its FK), then orders.
            DB::table('order_items')->delete();
            DB::table('orders')->delete();
        });

        $removed = 0;
        if (! $this->option('keep-screenshots')) {
            $dir = public_path('uploads/payments');
            if (is_dir($dir)) {
                foreach ((array) glob($dir.'/*') as $file) {
                    if (is_file($file) && ! in_array(basename($file), ['.htaccess', '.gitignore'], true)) {
                        @unlink($file);
                        $removed++;
                    }
                }
            }
        }

        $this->info("Deleted {$count} order(s)"
            . ($removed ? " and {$removed} payment screenshot(s)" : '')
            . '. Revenue totals are now clean.');

        return self::SUCCESS;
    }
}
