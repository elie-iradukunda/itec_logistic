<?php

declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

/**
 * The stock ledger. `inventory_items.quantity` is no longer a number somebody
 * types over: it is the running balance of the movements recorded here, so every
 * change has a date, a reason, a source document and a person attached to it.
 */
final class StockLedger
{
    /**
     * Records one movement and moves the item balance with it.
     *
     * @param float $quantity always positive; the movement type decides the sign
     * @return string the movement reference
     */
    public static function record(
        int $itemId,
        string $movementType,
        float $quantity,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?string $referenceCode = null,
        ?string $movedAt = null,
        ?string $notes = null,
        ?int $performedBy = null,
        ?string $movementCode = null
    ): string {
        $db = Database::connection();
        $quantity = abs($quantity);
        $movedAt ??= date('Y-m-d H:i:s');
        $performedBy ??= \current_user_id();

        $item = self::item($itemId);
        if ($item === null) {
            throw new \RuntimeException('Stock item not found.');
        }

        $signed = Schema::movementAdds($movementType) ? $quantity : -$quantity;
        $balance = round((float) $item['quantity'] + $signed, 2);
        if ($balance < 0) {
            throw new \RuntimeException(sprintf(
                'This movement would take %s below zero (balance %s, requested %s).',
                $item['item_name'],
                self::number((float) $item['quantity']),
                self::number($quantity)
            ));
        }

        $movementCode = $movementCode !== null && trim($movementCode) !== ''
            ? trim($movementCode)
            : Reference::next('MOV', 'stock_movements', 'movement_code');

        $owning = !$db->inTransaction();
        if ($owning) {
            $db->beginTransaction();
        }

        try {
            $insert = $db->prepare(
                'INSERT INTO stock_movements
                    (movement_code, item_id, warehouse_id, movement_type, quantity, unit_cost, balance_after, reference_type, reference_code, performed_by, moved_at, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $movementCode,
                $itemId,
                (int) $item['warehouse_id'],
                $movementType,
                $quantity,
                $unitCost,
                $balance,
                $referenceType,
                $referenceCode,
                $performedBy,
                $movedAt,
                $notes,
            ]);

            self::setBalance($itemId, $balance);

            if ($owning) {
                $db->commit();
            }
        } catch (\Throwable $exception) {
            if ($owning && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }

        AuditLog::record('stock.moved', 'movements', $movementCode, $notes, [
            'item_id' => $itemId,
            'type' => $movementType,
            'quantity' => $quantity,
            'balance_after' => $balance,
        ]);

        return $movementCode;
    }

    /**
     * Rebuilds the item balance from a movement that was edited or removed, so the
     * ledger and the item never drift apart.
     */
    public static function recalculate(int $itemId): float
    {
        $statement = Database::connection()->prepare(
            "SELECT COALESCE(SUM(CASE WHEN movement_type IN ('stock_in','transfer_in','return','adjustment') THEN quantity ELSE -quantity END), 0)
             FROM stock_movements WHERE item_id = ?"
        );
        $statement->execute([$itemId]);
        $balance = round((float) $statement->fetchColumn(), 2);

        self::setBalance($itemId, max(0.0, $balance));

        return $balance;
    }

    /** Sets the balance and derives the stock status from the minimum level. */
    public static function setBalance(int $itemId, float $balance): void
    {
        $status = self::statusFor($itemId, $balance);
        $update = Database::connection()->prepare('UPDATE inventory_items SET quantity = ?, status = ? WHERE id = ?');
        $update->execute([$balance, $status, $itemId]);
    }

    public static function statusFor(int $itemId, float $balance): string
    {
        $item = self::item($itemId);
        $minimum = (float) ($item['minimum_level'] ?? 0);

        return match (true) {
            $balance <= 0 => 'out_of_stock',
            $balance <= $minimum => 'reorder',
            default => 'in_stock',
        };
    }

    /** Posts every line of a received purchase request into stock. */
    public static function receivePurchaseRequest(int $purchaseRequestId): int
    {
        $db = Database::connection();
        $statement = $db->prepare(
            'SELECT pr.request_code, pr.warehouse_id, l.id AS line_id, l.item_id, l.item_name, l.quantity, l.unit_price, l.received_quantity
               FROM purchase_requests pr
               LEFT JOIN purchase_request_lines l ON l.purchase_request_id = pr.id
              WHERE pr.id = ?'
        );
        $statement->execute([$purchaseRequestId]);
        $lines = $statement->fetchAll(PDO::FETCH_ASSOC);

        $posted = 0;
        foreach ($lines as $line) {
            $itemId = $line['item_id'] !== null ? (int) $line['item_id'] : null;
            $outstanding = (float) $line['quantity'] - (float) $line['received_quantity'];
            if ($itemId === null || $outstanding <= 0) {
                continue;
            }

            self::record(
                $itemId,
                'stock_in',
                $outstanding,
                $line['unit_price'] !== null ? (float) $line['unit_price'] : null,
                'purchase',
                (string) $line['request_code'],
                null,
                'Goods received against ' . $line['request_code'],
            );

            $mark = $db->prepare('UPDATE purchase_request_lines SET received_quantity = quantity WHERE id = ?');
            $mark->execute([(int) $line['line_id']]);
            $posted++;
        }

        return $posted;
    }

    private static function item(int $itemId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, item_name, warehouse_id, quantity, minimum_level FROM inventory_items WHERE id = ? LIMIT 1'
        );
        $statement->execute([$itemId]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
