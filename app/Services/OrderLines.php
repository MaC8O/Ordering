<?php

namespace App\Services;

use App\Models\MenuItem;

/**
 * Turns a parsed cart (from CartParser) into order-line snapshots, computing
 * every price server-side so the client can never set its own prices. Size and
 * egg options are validated against each menu item's real configuration.
 */
class OrderLines
{
    /**
     * @param  array<int, array{id:int, qty:int, note:string, size:?string, egg:string}>  $cart
     * @return array{missing:bool, lines?:array<int,array<string,mixed>>, subtotal?:int}
     */
    public static function build(array $cart): array
    {
        $ids = array_values(array_unique(array_map(fn ($l) => $l['id'], $cart)));

        $items = MenuItem::query()
            ->available()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $missing = array_diff($ids, $items->keys()->all());
        if (! empty($missing)) {
            return ['missing' => true];
        }

        $maxQty = (int) config('ordering.max_qty', 20);
        $eggFee = (int) config('ordering.egg_addon_fee');

        $lines = [];
        $subtotal = 0;

        foreach ($cart as $line) {
            /** @var MenuItem $menuItem */
            $menuItem = $items[$line['id']];
            $qty = max(1, min($maxQty, (int) $line['qty']));

            // Size — only honour "large" when the item actually offers it.
            $size = ($line['size'] === 'large' && $menuItem->hasSizes()) ? 'large' : 'normal';
            $unit = $size === 'large' ? (int) $menuItem->large_price : (int) $menuItem->price;

            // Egg add-on — only when the item allows it.
            $egg = (in_array($line['egg'], ['fried', 'half-boiled'], true) && $menuItem->has_egg_addon)
                ? $line['egg']
                : 'none';
            if ($egg !== 'none') {
                $unit += $eggFee;
            }

            // Protein — only honour a value the item actually offers.
            $protein = ($menuItem->hasProteins() && in_array($line['protein'], $menuItem->proteins, true))
                ? $line['protein']
                : null;

            $lineTotal = $unit * $qty;
            $subtotal += $lineTotal;

            $opts = [];
            if ($protein !== null) {
                $opts[] = $protein;
            }
            if ($size === 'large') {
                $opts[] = 'Large';
            }
            if ($egg === 'fried') {
                $opts[] = 'Fried egg';
            } elseif ($egg === 'half-boiled') {
                $opts[] = 'Half-boiled egg';
            }

            $lines[] = [
                'menu_item_id' => $menuItem->id,
                'item_name_snapshot' => $menuItem->name,
                'options_snapshot' => $opts ? implode(' · ', $opts) : null,
                'item_price_snapshot' => $unit,
                'item_note' => $line['note'] !== '' ? mb_substr($line['note'], 0, 200) : null,
                'quantity' => $qty,
                'line_total' => $lineTotal,
            ];
        }

        return ['missing' => false, 'lines' => $lines, 'subtotal' => $subtotal];
    }
}
