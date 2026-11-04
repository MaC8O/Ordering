<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StoreStatus;

class OrderController extends Controller
{
    public function show(Order $order, StoreStatus $status)
    {
        // Bound by its unguessable public_token (see routes/web.php),
        // so the page can't be reached by guessing the order number.
        $order->load('items');

        return view('order.show', [
            'order' => $order,
            'settings' => $status->settings(),
        ]);
    }
}
