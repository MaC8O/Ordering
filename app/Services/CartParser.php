<?php

namespace App\Services;

/**
 * Normalises the client cart JSON (sent from localStorage) into a clean,
 * server-trusted list of lines. Shared by the checkout and preorder flows.
 *
 * Lines are identified by item id + size + egg, so the same dish in two sizes
 * stays separate; exact duplicates are merged.
 */
class CartParser
{
    /**
     * @return array<int, array{id:int, qty:int, note:string, protein:?string, size:?string, egg:string}>
     */
    public static function parse(?string $json): array
    {
        $decoded = json_decode((string) $json, true);

        if (! is_array($decoded)) {
            return [];
        }

        $lines = [];
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
            $protein = isset($row['protein']) && is_string($row['protein']) && $row['protein'] !== ''
                ? mb_substr(trim($row['protein']), 0, 40)
                : null;
            $size = ($row['size'] ?? null) === 'large' ? 'large' : null;
            $egg = in_array($row['egg'] ?? null, ['fried', 'half-boiled'], true) ? $row['egg'] : 'none';

            $key = $id . '|' . ($protein ?? 'x') . '|' . ($size ?? 'n') . '|' . $egg;

            if (isset($lines[$key])) {
                $lines[$key]['qty'] += $qty;
            } else {
                $lines[$key] = [
                    'id' => $id,
                    'qty' => $qty,
                    'note' => $note,
                    'protein' => $protein,
                    'size' => $size,
                    'egg' => $egg,
                ];
            }
        }

        return array_values($lines);
    }
}
