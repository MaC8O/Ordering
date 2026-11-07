<?php

return [
    /*
    | Currency symbol shown throughout the storefront and admin. Prices are
    | stored as integers in the minor unit (e.g. sen/cents) — 100 minor = 1 major.
    */
    'currency' => env('ORDERING_CURRENCY', '฿'),

    /*
    | PromptPay ID used to build the dynamic QR at checkout (with the exact
    | amount embedded). A Thai mobile number, National ID, or e-Wallet ID.
    | Leave null to fall back to the static QR image uploaded in settings.
    */
    'promptpay_id' => env('ORDERING_PROMPTPAY_ID', '0628197477'),

    // Max payment screenshot upload size in kilobytes (5 MB).
    'screenshot_max_kb' => 5120,

    // Max menu image upload size in kilobytes.
    'menu_image_max_kb' => 5120,

    // Quantity bounds for a single line item.
    'min_qty' => 1,
    'max_qty' => 20,

    // Flat fee for a fried / half-boiled egg add-on, in minor units (10 THB).
    'egg_addon_fee' => (int) env('ORDERING_EGG_ADDON_FEE', 1000),

    // Supported interface languages (English / Thai).
    'locales' => ['en', 'th'],

    /*
    | Loyalty reward (registered accounts only): every this-many DELIVERED
    | orders earns ONE free delivery, which is then redeemed and the count
    | resets — a recurring punch-card, not free-forever.
    */
    'loyalty' => [
        'free_delivery_after' => (int) env('ORDERING_FREE_DELIVERY_AFTER', 7),
    ],
];
