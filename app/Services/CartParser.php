<?php

namespace App\Services;

/**
 * Normalises the client cart JSON (sent from localStorage) into a clean,
 * server-trusted shape. Shared by the checkout and preorder flows.
 */
class CartParser
{
    /**
     * @return array<int, array{qty:int, note:string}> keyed by menu item id
     */
    public static function parse(?string $json): array
    {
        $decoded = json_decode((string) $json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $cart = [];
        foreach ($decoded as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }
            $id = (int) $row['id'];
            $qty = (int) ($row['qty'] ?? 0);
            if ($id <= 0 || $qty <= 0) {
                continue;
            }
            $note = isset($row['note']) ? trim((string) $row['note']) : '';

            // Merge duplicate ids.
            if (isset($cart[$id])) {
                $cart[$id]['qty'] += $qty;
            } else {
                $cart[$id] = ['qty' => $qty, 'note' => $note];
            }
        }

        return $cart;
    }
}
