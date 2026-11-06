<?php

namespace App\Providers;

use App\Services\StoreStatus;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share store status + settings with every storefront page (layout,
        // the closed banner, and each content view that references them).
        View::composer([
            'layouts.storefront',
            'partials.store-banner',
            'menu.index',
            'menu.show',
            'cart.index',
            'checkout.index',
            'order.show',
            'my-order.index',
            'about',
            'account.index',
            'account.login',
            'account.register',
        ], function ($view) {
            $status = new StoreStatus();
            $view->with([
                'storeStatus' => $status,
                'storeSettings' => $status->settings(),
            ]);
        });
    }
}
