<?php

declare(strict_types=1);

namespace Models;

use Core\Database;

/**
 * Writes notifications when something actually happens, and lets a user clear
 * them. Before this existed the bell only ever showed seeded rows and the unread
 * count could never go down.
 */
final class Notifier
{
    /** Notify every user holding a role. */
    public static function toRole(string $roleKey, string $title, string $message, ?string $route = null, string $severity = 'info', ?string $entityType = null, ?string $entityId = null): void
    {
        self::insert(null, $roleKey, $title, $message, $route, $severity, $entityType, $entityId);
    }

    /** Notify one person. */
    public static function toUser(?int $userId, string $title, string $message, ?string $route = null, string $severity = 'info', ?string $entityType = null, ?string $entityId = null): void
    {
        if ($userId === null) {
            return;
        }

        self::insert($userId, null, $title, $message, $route, $severity, $entityType, $entityId);
    }

    /** @param list<string> $roleKeys */
    public static function toRoles(array $roleKeys, string $title, string $message, ?string $route = null, string $severity = 'info', ?string $entityType = null, ?string $entityId = null): void
    {
        foreach (array_unique($roleKeys) as $roleKey) {
            self::toRole($roleKey, $title, $message, $route, $severity, $entityType, $entityId);
        }
    }

    public static function markRead(int $userId, string $roleKey, string $notificationKey): void
    {
        try {
            $statement = Database::connection()->prepare(
                'UPDATE notifications
                    SET is_read = 1, read_at = NOW()
                  WHERE notification_key = ?
                    AND (user_id = ? OR (user_id IS NULL AND (role_key = ? OR role_key IS NULL)))'
            );
            $statement->execute([$notificationKey, $userId, $roleKey]);
        } catch (\Throwable) {
            // Never break navigation because a read receipt failed.
        }
    }

    public static function markAllRead(int $userId, string $roleKey): int
    {
        try {
            $statement = Database::connection()->prepare(
                'UPDATE notifications
                    SET is_read = 1, read_at = NOW()
                  WHERE is_read = 0
                    AND (user_id = ? OR (user_id IS NULL AND (role_key = ? OR role_key IS NULL)))'
            );
            $statement->execute([$userId, $roleKey]);

            return $statement->rowCount();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Operational alerts that are true right now rather than stored: licences and
     * vehicle documents about to expire, services due, stock below minimum and
     * invoices past their due date. Run on login so the bell is useful from the
     * first page.
     */
    public static function refreshOperationalAlerts(): void
    {
        try {
            $db = Database::connection();
            $licenceDays = Settings::int('licence_alert_days', 90);
            $documentDays = Settings::int('document_alert_days', 30);
            $serviceDays = Settings::int('service_alert_days', 14);

            $expiringLicences = $db->prepare(
                'SELECT full_name, license_expiry FROM drivers
                  WHERE deleted_at IS NULL AND license_expiry IS NOT NULL
                    AND license_expiry <= DATE_ADD(CURDATE(), INTERVAL ? DAY)'
            );
            $expiringLicences->execute([$licenceDays]);
            foreach ($expiringLicences->fetchAll() as $driver) {
                self::once(
                    'licence-' . md5($driver['full_name'] . $driver['license_expiry']),
                    'fleet_manager',
                    'Driver licence expiring',
                    sprintf('%s has a licence expiring on %s.', $driver['full_name'], $driver['license_expiry']),
                    'drivers',
                    'warning'
                );
            }

            $expiringDocuments = $db->prepare(
                'SELECT vd.document_code, vd.document_type, vd.expires_on, v.plate_number
                   FROM vehicle_documents vd
                   INNER JOIN vehicles v ON v.id = vd.vehicle_id
                  WHERE vd.deleted_at IS NULL AND vd.status <> "cancelled"
                    AND vd.expires_on <= DATE_ADD(CURDATE(), INTERVAL ? DAY)'
            );
            $expiringDocuments->execute([$documentDays]);
            foreach ($expiringDocuments->fetchAll() as $document) {
                self::once(
                    'vehdoc-' . md5((string) $document['document_code'] . $document['expires_on']),
                    'fleet_manager',
                    'Vehicle document expiring',
                    sprintf('%s for %s expires on %s.', ucfirst(str_replace('_', ' ', (string) $document['document_type'])), $document['plate_number'], $document['expires_on']),
                    'vehicle_documents',
                    'danger'
                );
            }

            $dueService = $db->prepare(
                'SELECT plate_number, next_service_date FROM vehicles
                  WHERE deleted_at IS NULL AND next_service_date IS NOT NULL
                    AND next_service_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)'
            );
            $dueService->execute([$serviceDays]);
            foreach ($dueService->fetchAll() as $vehicle) {
                self::once(
                    'service-' . md5($vehicle['plate_number'] . $vehicle['next_service_date']),
                    'fleet_manager',
                    'Service due',
                    sprintf('%s is due for service on %s.', $vehicle['plate_number'], $vehicle['next_service_date']),
                    'maintenance',
                    'warning'
                );
            }

            foreach ($db->query('SELECT sku, item_name, quantity, minimum_level FROM inventory_items WHERE deleted_at IS NULL AND quantity <= minimum_level')->fetchAll() as $item) {
                self::once(
                    'stock-' . md5((string) $item['sku'] . (string) $item['quantity']),
                    'warehouse_manager',
                    'Stock below minimum',
                    sprintf('%s is at %s against a minimum of %s.', $item['item_name'], rtrim(rtrim((string) $item['quantity'], '0'), '.'), rtrim(rtrim((string) $item['minimum_level'], '0'), '.')),
                    'warehouse',
                    'danger'
                );
            }

            $db->exec("UPDATE invoices SET status = 'overdue' WHERE deleted_at IS NULL AND status IN ('issued','partially_paid') AND due_date < CURDATE()");
            foreach ($db->query("SELECT invoice_number, due_date, (total_amount - amount_paid) AS balance FROM invoices WHERE deleted_at IS NULL AND status = 'overdue'")->fetchAll() as $invoice) {
                self::once(
                    'overdue-' . md5((string) $invoice['invoice_number']),
                    'finance',
                    'Invoice overdue',
                    sprintf('%s was due on %s with %s outstanding.', $invoice['invoice_number'], $invoice['due_date'], Settings::money((float) $invoice['balance'])),
                    'invoices',
                    'danger'
                );
            }
        } catch (\Throwable) {
            // Alerts are a convenience; a failure here must not block the dashboard.
        }
    }

    /** Inserts only if that key has never been raised, so an alert is not repeated every page load. */
    private static function once(string $key, string $roleKey, string $title, string $message, ?string $route, string $severity): void
    {
        $statement = Database::connection()->prepare(
            'INSERT IGNORE INTO notifications (notification_key, user_id, role_key, title, message, link_route, severity)
             VALUES (?, NULL, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$key, $roleKey, $title, $message, $route, $severity]);
    }

    private static function insert(?int $userId, ?string $roleKey, string $title, string $message, ?string $route, string $severity, ?string $entityType, ?string $entityId): void
    {
        try {
            $severity = in_array($severity, ['info', 'success', 'warning', 'danger'], true) ? $severity : 'info';
            $statement = Database::connection()->prepare(
                'INSERT INTO notifications (notification_key, user_id, role_key, title, message, link_route, entity_type, entity_id, severity)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                uniqid('n-', true),
                $userId,
                $roleKey,
                mb_substr($title, 0, 150),
                $message,
                $route,
                $entityType,
                $entityId,
                $severity,
            ]);
        } catch (\Throwable) {
            // A notification is never worth failing the user's action for.
        }
    }
}
