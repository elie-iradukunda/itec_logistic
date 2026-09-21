<?php

declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

/**
 * The operational rules that used to be missing entirely: which status a record
 * may move to, what must be true before it moves, and what else changes when it
 * does.
 *
 * Dispatching a trip now sets the vehicle and the driver to "on trip", stamps the
 * dispatch time, pushes the shipments and deliveries into transit, moves the
 * originating request to "assigned" and tells the driver — instead of leaving a
 * person to remember all six edits.
 */
final class Workflow
{
    /** module => action => [from statuses, to status, verb used in the audit trail] */
    private const TRANSITIONS = [
        'requests' => [
            'approve' => ['from' => ['pending'], 'to' => 'approved'],
            'reject' => ['from' => ['pending', 'approved'], 'to' => 'rejected', 'needs_reason' => true],
            // No "assign" transition: a request becomes assigned because a trip
            // was created for it, not because somebody pressed a button and then
            // walked away from the form.
        ],
        'trips' => [
            'dispatch' => ['from' => ['requested', 'approved', 'loading'], 'to' => 'in_transit'],
            'complete' => ['from' => ['loading', 'in_transit'], 'to' => 'delivered'],
            'reject' => ['from' => ['requested', 'approved', 'loading'], 'to' => 'cancelled', 'needs_reason' => true],
        ],
        'deliveries' => [
            'complete' => ['from' => ['loading', 'in_transit'], 'to' => 'delivered'],
            'fail' => ['from' => ['loading', 'in_transit'], 'to' => 'failed', 'needs_reason' => true],
        ],
        'expenses' => [
            'approve' => ['from' => ['pending'], 'to' => 'approved'],
            'reject' => ['from' => ['pending'], 'to' => 'rejected', 'needs_reason' => true],
        ],
        'procurement' => [
            'approve' => ['from' => ['draft', 'quotation'], 'to' => 'approved'],
            'reject' => ['from' => ['draft', 'quotation', 'approved'], 'to' => 'rejected', 'needs_reason' => true],
            'receive' => ['from' => ['approved'], 'to' => 'received'],
        ],
        'maintenance' => [
            'approve' => ['from' => ['open', 'scheduled', 'in_progress'], 'to' => 'completed'],
            'reject' => ['from' => ['open', 'scheduled', 'in_progress'], 'to' => 'cancelled', 'needs_reason' => true],
        ],
        'invoices' => [
            'approve' => ['from' => ['draft'], 'to' => 'issued'],
            'reject' => ['from' => ['draft', 'issued'], 'to' => 'cancelled', 'needs_reason' => true],
        ],
    ];

    private const STATUS_COLUMN = [
        'requests' => ['transport_requests', 'status'],
        'trips' => ['trips', 'status'],
        'deliveries' => ['deliveries', 'status'],
        'expenses' => ['expenses', 'status'],
        'procurement' => ['purchase_requests', 'status'],
        'maintenance' => ['maintenance_orders', 'status'],
        'invoices' => ['invoices', 'status'],
    ];

    /** Actions available for a record right now, given its status. */
    public static function availableActions(string $module, array $record): array
    {
        $current = (string) ($record['status'] ?? '');
        $available = [];

        foreach (self::TRANSITIONS[$module] ?? [] as $action => $rule) {
            if (in_array($current, $rule['from'], true)) {
                $available[$action] = $rule;
            }
        }

        return $available;
    }

    public static function needsReason(string $module, string $action): bool
    {
        return (bool) (self::TRANSITIONS[$module][$action]['needs_reason'] ?? false);
    }

    /**
     * Runs one workflow action.
     *
     * @return array{ok: bool, message: string}
     */
    public static function apply(string $module, int $id, string $action, string $reason = ''): array
    {
        $rule = self::TRANSITIONS[$module][$action] ?? null;
        if ($rule === null) {
            return ['ok' => false, 'message' => 'That action does not exist for this module.'];
        }

        [$table, $statusColumn] = self::STATUS_COLUMN[$module];
        $record = self::record($table, $id);
        if ($record === null) {
            return ['ok' => false, 'message' => 'The record no longer exists.'];
        }

        $current = (string) $record[$statusColumn];
        if (!in_array($current, $rule['from'], true)) {
            return ['ok' => false, 'message' => sprintf('A record that is %s cannot be %sd.', Schema::label($current), $action)];
        }

        $reason = trim($reason);
        if (!empty($rule['needs_reason']) && $reason === '') {
            return ['ok' => false, 'message' => 'A reason is required for this action.'];
        }

        $guard = self::guard($module, $action, $record);
        if ($guard !== null) {
            return ['ok' => false, 'message' => $guard];
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $update = $db->prepare("UPDATE {$table} SET {$statusColumn} = ? WHERE id = ?");
            $update->execute([$rule['to'], $id]);

            $message = self::effects($module, $action, $rule['to'], $record, $reason);

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        AuditLog::record(
            "workflow.{$module}.{$action}",
            $module,
            (string) ($record[self::codeColumn($module)] ?? $id),
            $reason === '' ? null : $reason,
            ['from' => $current, 'to' => $rule['to']]
        );

        return ['ok' => true, 'message' => $message];
    }

    // ----------------------------------------------------------------- guards

    /** Returns an error message when the action must not happen, or null when it may. */
    private static function guard(string $module, string $action, array $record): ?string
    {
        if ($module === 'trips' && $action === 'dispatch') {
            return self::guardDispatch($record);
        }

        if ($module === 'trips' && $action === 'complete' && empty($record['driver_id'])) {
            return 'A trip cannot be completed without a driver on it.';
        }

        if ($module === 'procurement' && $action === 'receive' && empty($record['warehouse_id'])) {
            return 'Set the receiving warehouse before marking this request received.';
        }

        if ($module === 'invoices' && $action === 'approve' && (float) $record['total_amount'] <= 0) {
            return 'Add at least one invoice line before issuing this invoice.';
        }

        return null;
    }

    private static function guardDispatch(array $trip): ?string
    {
        $db = Database::connection();
        $vehicleId = $trip['vehicle_id'] !== null ? (int) $trip['vehicle_id'] : null;
        $driverId = $trip['driver_id'] !== null ? (int) $trip['driver_id'] : null;

        if ($vehicleId === null || $driverId === null) {
            return 'Assign both a vehicle and a driver before dispatching.';
        }

        $vehicle = self::record('vehicles', $vehicleId);
        if ($vehicle === null || $vehicle['deleted_at'] !== null) {
            return 'The assigned vehicle no longer exists.';
        }
        if (in_array($vehicle['status'], ['maintenance', 'inactive'], true)) {
            return sprintf('%s is %s and cannot be dispatched.', $vehicle['plate_number'], Schema::label((string) $vehicle['status']));
        }

        $driver = self::record('drivers', $driverId);
        if ($driver === null || $driver['deleted_at'] !== null) {
            return 'The assigned driver no longer exists.';
        }
        if ($driver['status'] === 'inactive') {
            return sprintf('%s is inactive and cannot be dispatched.', $driver['full_name']);
        }
        if ($driver['license_expiry'] !== null && $driver['license_expiry'] < date('Y-m-d')) {
            return sprintf('The licence for %s expired on %s.', $driver['full_name'], $driver['license_expiry']);
        }

        // Double-booking: the same vehicle or driver already out on another trip.
        $clash = $db->prepare(
            "SELECT reference_code FROM trips
              WHERE id <> ? AND deleted_at IS NULL
                AND status IN ('loading','in_transit')
                AND (vehicle_id = ? OR driver_id = ?)
              LIMIT 1"
        );
        $clash->execute([(int) $trip['id'], $vehicleId, $driverId]);
        $clashing = $clash->fetchColumn();
        if ($clashing !== false) {
            return sprintf('That vehicle or driver is already out on trip %s.', (string) $clashing);
        }

        // Cargo the vehicle is not fit to carry.
        $cargo = $db->prepare(
            'SELECT COALESCE(SUM(weight_kg), 0) AS weight,
                    SUM(CASE WHEN cargo_type IN ("cold_chain","perishable") THEN 1 ELSE 0 END) AS cold,
                    SUM(is_hazardous) AS hazardous
               FROM shipments WHERE trip_id = ? AND deleted_at IS NULL'
        );
        $cargo->execute([(int) $trip['id']]);
        $totals = $cargo->fetch() ?: ['weight' => 0, 'cold' => 0, 'hazardous' => 0];

        if ((int) $totals['cold'] > 0 && (int) $vehicle['has_cooling_unit'] !== 1) {
            return sprintf('%s has no cooling unit and this trip carries cold-chain cargo.', $vehicle['plate_number']);
        }

        $capacity = $vehicle['capacity_kg'] !== null ? (float) $vehicle['capacity_kg'] : null;
        if ($capacity !== null && $capacity > 0 && (float) $totals['weight'] > $capacity) {
            return sprintf(
                'The load is %s kg but %s carries %s kg.',
                number_format((float) $totals['weight'], 0),
                $vehicle['plate_number'],
                number_format($capacity, 0)
            );
        }

        return null;
    }

    // ---------------------------------------------------------------- effects

    /** Applies everything else that must change, and returns the message for the user. */
    private static function effects(string $module, string $action, string $to, array $record, string $reason): string
    {
        $db = Database::connection();
        $id = (int) $record['id'];
        $actor = \current_user_id();
        $code = (string) ($record[self::codeColumn($module)] ?? $id);

        return match (true) {
            $module === 'requests' && $action === 'approve' => self::approveRequest($db, $id, $code, $actor),
            $module === 'requests' && $action === 'reject' => self::rejectRequest($db, $id, $code, $actor, $reason),
            $module === 'trips' && $action === 'dispatch' => self::dispatchTrip($db, $record, $code),
            $module === 'trips' && $action === 'complete' => self::completeTrip($db, $record, $code),
            $module === 'trips' && $action === 'reject' => self::cancelTrip($db, $record, $code, $reason),

            $module === 'deliveries' && $action === 'complete' => self::completeDelivery($db, $record, $code, $actor),
            $module === 'deliveries' && $action === 'fail' => self::failDelivery($db, $record, $code, $reason),

            $module === 'expenses' && $action === 'approve' => self::approveExpense($db, $record, $code, $actor),
            $module === 'expenses' && $action === 'reject' => self::rejectExpense($db, $record, $code, $actor, $reason),

            $module === 'procurement' && $action === 'approve' => self::approvePurchase($db, $record, $code, $actor),
            $module === 'procurement' && $action === 'reject' => self::stampRejection($db, 'purchase_requests', $id, $actor, $reason, sprintf('Purchase request %s was rejected.', $code)),
            $module === 'procurement' && $action === 'receive' => self::receivePurchase($db, $record, $code),

            $module === 'maintenance' && $action === 'approve' => self::completeMaintenance($db, $record, $code, $actor),
            $module === 'maintenance' && $action === 'reject' => sprintf('Work order %s was cancelled.', $code),

            $module === 'invoices' && $action === 'approve' => self::issueInvoice($db, $record, $code, $actor),
            $module === 'invoices' && $action === 'reject' => self::cancelInvoice($id, $code),

            default => 'Done.',
        };
    }

    private static function approveRequest(PDO $db, int $id, string $code, ?int $actor): string
    {
        $stamp = $db->prepare('UPDATE transport_requests SET approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?');
        $stamp->execute([$actor, $id]);

        Notifier::toRole('logistics_manager', 'Transport request approved', sprintf('%s is approved and ready to be planned into a trip.', $code), 'requests', 'success', 'requests', $code);

        return sprintf('Request %s approved. Plan a trip for it next.', $code);
    }

    private static function rejectRequest(PDO $db, int $id, string $code, ?int $actor, string $reason): string
    {
        $stamp = $db->prepare('UPDATE transport_requests SET approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?');
        $stamp->execute([$actor, mb_substr($reason, 0, 255), $id]);

        $requester = $db->prepare('SELECT requester_id FROM transport_requests WHERE id = ?');
        $requester->execute([$id]);
        Notifier::toUser($requester->fetchColumn() ?: null, 'Transport request rejected', sprintf('%s was rejected: %s', $code, $reason), 'requests', 'danger', 'requests', $code);

        return sprintf('Request %s was rejected.', $code);
    }

    private static function dispatchTrip(PDO $db, array $trip, string $code): string
    {
        $id = (int) $trip['id'];

        $db->prepare('UPDATE trips SET dispatched_at = NOW(), departure_at = COALESCE(departure_at, NOW()) WHERE id = ?')->execute([$id]);
        $db->prepare("UPDATE vehicles SET status = 'on_trip' WHERE id = ?")->execute([(int) $trip['vehicle_id']]);
        $db->prepare("UPDATE drivers SET status = 'on_trip' WHERE id = ?")->execute([(int) $trip['driver_id']]);
        $db->prepare("UPDATE shipments SET status = 'in_transit' WHERE trip_id = ? AND deleted_at IS NULL AND status IN ('draft','booked','loaded')")->execute([$id]);
        $db->prepare("UPDATE deliveries SET status = 'in_transit' WHERE trip_id = ? AND deleted_at IS NULL AND status = 'loading'")->execute([$id]);

        if (!empty($trip['request_id'])) {
            $db->prepare("UPDATE transport_requests SET status = 'assigned', trip_id = ? WHERE id = ? AND status IN ('approved', 'assigned')")->execute([$id, (int) $trip['request_id']]);
        }

        $driverUser = $db->prepare('SELECT user_id FROM drivers WHERE id = ?');
        $driverUser->execute([(int) $trip['driver_id']]);
        Notifier::toUser(
            $driverUser->fetchColumn() ?: null,
            'Trip dispatched to you',
            sprintf('%s from %s to %s is now in transit.', $code, $trip['pickup_location'], $trip['destination']),
            'trips',
            'info',
            'trips',
            $code
        );

        return sprintf('%s dispatched. The vehicle and driver are now marked on trip.', $code);
    }

    private static function completeTrip(PDO $db, array $trip, string $code): string
    {
        $id = (int) $trip['id'];

        $db->prepare('UPDATE trips SET completed_at = NOW(), arrival_at = COALESCE(arrival_at, NOW()) WHERE id = ?')->execute([$id]);
        if (!empty($trip['vehicle_id'])) {
            $db->prepare("UPDATE vehicles SET status = 'available' WHERE id = ? AND status = 'on_trip'")->execute([(int) $trip['vehicle_id']]);
        }
        if (!empty($trip['driver_id'])) {
            $db->prepare("UPDATE drivers SET status = 'available' WHERE id = ? AND status = 'on_trip'")->execute([(int) $trip['driver_id']]);
        }
        $db->prepare("UPDATE shipments SET status = 'delivered' WHERE trip_id = ? AND deleted_at IS NULL AND status IN ('loaded','in_transit')")->execute([$id]);

        $onTime = self::onTimeVerdict($trip);
        Notifier::toRole('logistics_manager', 'Trip completed', sprintf('%s arrived at %s. %s', $code, $trip['destination'], $onTime), 'trips', 'success', 'trips', $code);

        return sprintf('%s completed. Vehicle and driver released. %s', $code, $onTime);
    }

    private static function cancelTrip(PDO $db, array $trip, string $code, string $reason): string
    {
        $id = (int) $trip['id'];

        $db->prepare('UPDATE trips SET notes = TRIM(CONCAT(COALESCE(notes, ""), "\nCancelled: ", ?)) WHERE id = ?')->execute([$reason, $id]);
        if (!empty($trip['vehicle_id'])) {
            $db->prepare("UPDATE vehicles SET status = 'available' WHERE id = ? AND status = 'on_trip'")->execute([(int) $trip['vehicle_id']]);
        }
        if (!empty($trip['driver_id'])) {
            $db->prepare("UPDATE drivers SET status = 'available' WHERE id = ? AND status = 'on_trip'")->execute([(int) $trip['driver_id']]);
        }
        $db->prepare("UPDATE shipments SET status = 'cancelled' WHERE trip_id = ? AND deleted_at IS NULL AND status NOT IN ('delivered','returned')")->execute([$id]);
        if (!empty($trip['request_id'])) {
            $db->prepare("UPDATE transport_requests SET status = 'approved', trip_id = NULL WHERE id = ? AND status = 'assigned'")->execute([(int) $trip['request_id']]);
        }

        return sprintf('%s cancelled and its vehicle and driver released.', $code);
    }

    private static function completeDelivery(PDO $db, array $delivery, string $code, ?int $actor): string
    {
        $id = (int) $delivery['id'];

        $db->prepare("UPDATE deliveries SET delivered_at = COALESCE(delivered_at, NOW()), delivered_by = ?, failure_reason = 'none', failure_notes = NULL WHERE id = ?")
           ->execute([$actor, $id]);

        if (!empty($delivery['shipment_id'])) {
            $db->prepare("UPDATE shipments SET status = 'delivered' WHERE id = ?")->execute([(int) $delivery['shipment_id']]);
        }

        $warning = empty($delivery['proof_file'])
            ? ' Proof of delivery is still missing; upload it on the delivery record.'
            : '';

        Notifier::toRole('logistics_manager', 'Delivery completed', sprintf('%s was delivered to %s.', $code, $delivery['recipient_name']), 'deliveries', 'success', 'deliveries', $code);

        return sprintf('%s marked delivered.%s', $code, $warning);
    }

    private static function failDelivery(PDO $db, array $delivery, string $code, string $reason): string
    {
        $id = (int) $delivery['id'];
        $knownReasons = ['recipient_absent', 'address_wrong', 'goods_damaged', 'goods_refused', 'vehicle_breakdown', 'access_denied', 'weather', 'other'];
        $slug = strtolower(str_replace(' ', '_', trim($reason)));
        $code_reason = in_array($slug, $knownReasons, true) ? $slug : 'other';

        $db->prepare('UPDATE deliveries SET failure_reason = ?, failure_notes = ? WHERE id = ?')
           ->execute([$code_reason, mb_substr($reason, 0, 255), $id]);

        Notifier::toRole('logistics_manager', 'Delivery failed', sprintf('%s to %s failed: %s', $code, $delivery['recipient_name'], $reason), 'deliveries', 'danger', 'deliveries', $code);

        return sprintf('%s recorded as failed. Create a new attempt when it is rescheduled.', $code);
    }

    private static function approveExpense(PDO $db, array $expense, string $code, ?int $actor): string
    {
        $db->prepare('UPDATE expenses SET approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?')
           ->execute([$actor, (int) $expense['id']]);

        Posting::tryPost('expense', (int) $expense['id']);

        Notifier::toUser(
            $expense['submitted_by'] !== null ? (int) $expense['submitted_by'] : null,
            'Expense approved',
            sprintf('%s for %s was approved.', $code, Settings::money((float) $expense['amount'])),
            'expenses',
            'success',
            'expenses',
            $code
        );

        return sprintf('Expense %s approved for %s.', $code, Settings::money((float) $expense['amount']));
    }

    private static function rejectExpense(PDO $db, array $expense, string $code, ?int $actor, string $reason): string
    {
        $db->prepare('UPDATE expenses SET approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?')
           ->execute([$actor, mb_substr($reason, 0, 255), (int) $expense['id']]);

        Posting::unpost('expense', (int) $expense['id']);

        Notifier::toUser(
            $expense['submitted_by'] !== null ? (int) $expense['submitted_by'] : null,
            'Expense rejected',
            sprintf('%s was rejected: %s', $code, $reason),
            'expenses',
            'danger',
            'expenses',
            $code
        );

        return sprintf('Expense %s rejected.', $code);
    }

    private static function approvePurchase(PDO $db, array $request, string $code, ?int $actor): string
    {
        $db->prepare('UPDATE purchase_requests SET approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?')
           ->execute([$actor, (int) $request['id']]);

        Notifier::toRole('warehouse_manager', 'Purchase request approved', sprintf('%s is approved and can be ordered.', $code), 'procurement', 'success', 'procurement', $code);

        return sprintf('Purchase request %s approved.', $code);
    }

    private static function receivePurchase(PDO $db, array $request, string $code): string
    {
        $db->prepare('UPDATE purchase_requests SET received_at = NOW() WHERE id = ?')->execute([(int) $request['id']]);

        $posted = StockLedger::receivePurchaseRequest((int) $request['id']);
        Posting::tryPost('purchase', (int) $request['id']);

        Notifier::toRole('warehouse_manager', 'Goods received', sprintf('%s was received and %d stock line(s) were posted.', $code, $posted), 'warehouse', 'success', 'procurement', $code);

        return $posted > 0
            ? sprintf('%s received. %d stock line(s) posted into inventory.', $code, $posted)
            : sprintf('%s received. No line was linked to a stock item, so nothing was posted into inventory.', $code);
    }

    private static function completeMaintenance(PDO $db, array $order, string $code, ?int $actor): string
    {
        $id = (int) $order['id'];

        $parts = $db->prepare('SELECT COALESCE(SUM(line_total), 0) FROM maintenance_parts WHERE maintenance_id = ?');
        $parts->execute([$id]);
        $partsTotal = (float) $parts->fetchColumn();

        $db->prepare('UPDATE maintenance_orders SET completed_at = COALESCE(completed_at, NOW()), approved_by = ?, approved_at = NOW(), actual_cost = COALESCE(NULLIF(?, 0), actual_cost, estimated_cost) WHERE id = ?')
           ->execute([$actor, $partsTotal, $id]);

        if (!empty($order['vehicle_id'])) {
            $db->prepare("UPDATE vehicles SET status = 'available' WHERE id = ? AND status = 'maintenance'")->execute([(int) $order['vehicle_id']]);
        }

        $final = $db->prepare('SELECT actual_cost FROM maintenance_orders WHERE id = ?');
        $final->execute([$id]);
        $cost = (float) ($final->fetchColumn() ?: 0);

        Posting::tryPost('maintenance', $id);

        $variance = $cost - (float) ($order['estimated_cost'] ?? 0);
        $note = abs($variance) < 0.01
            ? 'Cost matched the estimate.'
            : sprintf('%s %s the estimate.', Settings::money(abs($variance)), $variance > 0 ? 'over' : 'under');

        return sprintf('Work order %s completed at %s. %s', $code, Settings::money($cost), $note);
    }

    private static function issueInvoice(PDO $db, array $invoice, string $code, ?int $actor): string
    {
        $db->prepare('UPDATE invoices SET issued_by = ?, issue_date = COALESCE(issue_date, CURDATE()) WHERE id = ?')
           ->execute([$actor, (int) $invoice['id']]);

        Posting::tryPost('invoice', (int) $invoice['id']);

        Notifier::toRole('finance', 'Invoice issued', sprintf('%s for %s is issued and due on %s.', $code, Settings::money((float) $invoice['total_amount']), $invoice['due_date']), 'invoices', 'info', 'invoices', $code);

        return sprintf('Invoice %s issued for %s.', $code, Settings::money((float) $invoice['total_amount']));
    }

    /** Cancelling an invoice takes its entry back out of the ledger. */
    private static function cancelInvoice(int $id, string $code): string
    {
        Posting::unpost('invoice', $id);

        return sprintf('Invoice %s was cancelled and its ledger entry removed.', $code);
    }

    private static function stampRejection(PDO $db, string $table, int $id, ?int $actor, string $reason, string $message): string
    {
        $db->prepare("UPDATE {$table} SET approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?")
           ->execute([$actor, mb_substr($reason, 0, 255), $id]);

        return $message;
    }

    // ---------------------------------------------------------------- helpers

    private static function onTimeVerdict(array $trip): string
    {
        if (empty($trip['planned_arrival_at'])) {
            return 'No planned arrival was set, so on-time performance was not measured.';
        }

        $grace = Settings::int('on_time_grace_minutes', 30);
        $deadline = strtotime((string) $trip['planned_arrival_at']) + ($grace * 60);
        $late = time() - $deadline;

        return $late <= 0
            ? 'Arrived on time.'
            : sprintf('Arrived %d minute(s) after the agreed arrival time.', (int) ceil($late / 60));
    }

    private static function codeColumn(string $module): string
    {
        return Schema::has($module) ? Schema::get($module)['code'] : 'id';
    }

    private static function record(string $table, int $id): ?array
    {
        $allowed = ['transport_requests', 'trips', 'deliveries', 'expenses', 'purchase_requests', 'maintenance_orders', 'invoices', 'vehicles', 'drivers'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('Unknown workflow table.');
        }

        $statement = Database::connection()->prepare("SELECT * FROM {$table} WHERE id = ? LIMIT 1");
        $statement->execute([$id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
