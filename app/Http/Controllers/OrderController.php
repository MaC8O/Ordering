<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StoreStatus;
use Illuminate\Http\Request;

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

    /**
     * Customer (re)uploads a payment receipt from the order page — e.g. after a
     * rejected transfer — without having to place a whole new order.
     */
    public function uploadReceipt(Request $request, Order $order)
    {
        if (! $order->canUploadReceipt()) {
            return redirect()
                ->route('order.show', $order->public_token)
                ->with('error', __('This order is no longer waiting for a receipt.'));
        }

        $request->validate([
            'payment_screenshot' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:' . config('ordering.screenshot_max_kb'),
            ],
        ], [
            'payment_screenshot.required' => __('Please choose a receipt image to upload.'),
            'payment_screenshot.max' => __('The screenshot must be 5 MB or smaller.'),
            'payment_screenshot.image' => __('The upload must be an image file.'),
        ]);

        $order->storeReceipt($request->file('payment_screenshot'));

        // A fresh receipt reopens verification: back to pending, clear the
        // rejection note so the customer isn't left looking at a stale reason.
        $order->update(['payment_status' => 'pending', 'admin_note' => null]);

        return redirect()
            ->route('order.show', $order->public_token)
            ->with('success', __('Receipt uploaded. We’ll verify your payment shortly.'));
    }
}
