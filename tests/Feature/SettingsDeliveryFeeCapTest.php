<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsDeliveryFeeCapTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): Admin
    {
        $admin = Admin::create([
            'username' => 'owner',
            'password_hash' => Hash::make('secret'),
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * Valid base settings payload; override individual fields per test.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'restaurant_name' => 'Campus Kitchen',
            'phone' => '+66123456789',
            'address' => 'Somewhere',
            'account_name' => 'Campus Kitchen',
            'cutoff_minutes_before_close' => 30,
            'default_delivery_fee' => 10,  // ฿10 per item
            'delivery_fee_cap' => 50,      // ฿50 cap
            'dorms' => "Dorm A\nDorm B",
        ], $overrides);
    }

    public function test_cap_below_the_per_item_fee_is_rejected_and_nothing_is_saved(): void
    {
        $this->actingAsAdmin();

        // Seed a known starting cap so we can prove it is left untouched.
        Setting::current()->update(['default_delivery_fee' => 1000, 'delivery_fee_cap' => 5000]);

        $response = $this->patch(route('admin.settings.update'), $this->payload([
            'default_delivery_fee' => 10, // ฿10
            'delivery_fee_cap' => 5,      // ฿5 — below the per-item fee
        ]));

        $response->assertSessionHasErrors('delivery_fee_cap');

        // Unchanged: the invalid submit must not persist either field.
        $settings = Setting::current()->fresh();
        $this->assertSame(1000, $settings->default_delivery_fee);
        $this->assertSame(5000, $settings->delivery_fee_cap);
    }

    public function test_cap_equal_to_the_per_item_fee_is_accepted(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('admin.settings.update'), $this->payload([
            'default_delivery_fee' => 10,
            'delivery_fee_cap' => 10,
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(1000, Setting::current()->fresh()->delivery_fee_cap);
    }

    public function test_cap_above_the_per_item_fee_is_accepted(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('admin.settings.update'), $this->payload([
            'default_delivery_fee' => 10,
            'delivery_fee_cap' => 80,
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(8000, Setting::current()->fresh()->delivery_fee_cap);
    }

    public function test_cap_of_zero_is_accepted_as_disabled(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('admin.settings.update'), $this->payload([
            'default_delivery_fee' => 10,
            'delivery_fee_cap' => 0,
        ]));

        $response->assertSessionHasNoErrors();
        $this->assertSame(0, Setting::current()->fresh()->delivery_fee_cap);
    }
}
