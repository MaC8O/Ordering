<?php

return [
    /*
    | Currency symbol shown throughout the storefront and admin. Prices are
    | stored as integers in the minor unit (e.g. sen/cents) — 100 minor = 1 major.
    */
    'currency' => env('ORDERING_CURRENCY', '฿'),

    // Max payment screenshot upload size in kilobytes (5 MB).
    'screenshot_max_kb' => 5120,

    // Max menu image upload size in kilobytes.
    'menu_image_max_kb' => 5120,

    // Quantity bounds for a single line item.
    'min_qty' => 1,
    'max_qty' => 20,
];
