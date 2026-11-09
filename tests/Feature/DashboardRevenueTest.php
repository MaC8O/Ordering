<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardRevenueTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $admin = Admin::create([
            'username' => 'owner',
            'password_hash' => Hash::make('secret'),
        ]);

        $this->actingAs($admin, 'admin');
    }

    private function makeOrder(string $number, string $paymentStatus, array $lines): Order
    {
        $food = array_sum(array_map(fn ($l) => $l['line_total'], $lines));

        $order = Order::create([
            'order_number' => $number,
            'customer_name' => 'Test',
            'customer_phone' => '0123456789',
            'delivery_dorm' => 'Block A',
            'delivery_room' => '1',
            'payment_method' => 'qr',
            'payment_status' => $paymentStatus,
            'order_status' => 'confirmed',
            'subtotal' => $food,
            'delivery_fee' => 1000,
            'total' => $food + 1000,
        ]);

        foreach ($lines as $l) {
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $l['menu_item_id'],
                'item_name_snapshot' => $l['name'],
                'item_price_snapshot' => $l['line_total'] / $l['qty'],
                'quantity' => $l['qty'],
                'line_total' => $l['line_total'],
            ]);
        }

        return $order;
    }

    public function test_dashboard_shows_revenue_grouped_by_category(): void
    {
        $this->actingAsAdmin();

        $noodles = Category::create(['name' => 'Noodles', 'display_order' => 0, 'is_active' => true]);
        $rice = Category::create(['name' => 'Rice', 'display_order' => 1, 'is_active' => true]);

        $padThai = MenuItem::create(['category_id' => $noodles->id, 'name' => 'Pad Thai', 'price' => 5000, 'is_available' => true]);
        $friedRice = MenuItem::create(['category_id' => $rice->id, 'name' => 'Fried Rice', 'price' => 5000, 'is_available' => true]);

        $this->makeOrder('A-1', 'verified', [
            ['menu_item_id' => $padThai->id, 'name' => 'Pad Thai', 'qty' => 2, 'line_total' => 10000],
        ]);
        $this->makeOrder('A-2', 'pending', [
            ['menu_item_id' => $friedRice->id, 'name' => 'Fried Rice', 'qty' => 1, 'line_total' => 5000],
        ]);

        // A cancelled order must NOT count toward revenue.
        $cancelled = $this->makeOrder('A-3', 'verified', [
            ['menu_item_id' => $padThai->id, 'name' => 'Pad Thai', 'qty' => 5, 'line_total' => 25000],
        ]);
        $cancelled->update(['order_status' => 'cancelled']);

        $response = $this->get(route('admin.dashboard', ['period' => 'all']));

        $response->assertOk();
        $response->assertSee('Revenue breakdown');
        $response->assertSee('Revenue by category');
        $response->assertSee('Noodles');
        $response->assertSee('Rice');
        // Food revenue = 10000 + 5000 = 15000 (cancelled 25000 excluded) => 150.00
        $response->assertSee('150.00');
    }

    public function test_orders_reset_command_clears_all_orders(): void
    {
        $this->makeOrder('B-1', 'verified', [
            ['menu_item_id' => null, 'name' => 'Something', 'qty' => 1, 'line_total' => 5000],
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);

        $this->artisan('orders:reset --force')->assertSuccessful();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }
}
