<?php

declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

final class LogisticsData
{
    private static ?PDO $db = null;

    /** users.prvg values: 1 may switch into any role, 2 (the default) may not. */
    private const PRIVILEGES = [1 => '1 - Can switch roles', 2 => '2 - Standard'];

    private static function db(): PDO
    {
        if (self::$db instanceof PDO) {
            return self::$db;
        }

        self::$db = Database::connection();
        return self::$db;
    }

    public static function fieldOptions(string $key, string $column): array
    {
        return match (true) {
            $key === 'vehicles' && $column === 'Assigned driver' => array_merge(['None'], self::columnValues('drivers', 'full_name')),
            $key === 'drivers' && $column === 'Assigned vehicle' => array_merge(['None'], self::columnValues('vehicles', 'plate_number')),
            in_array($column, ['Vehicle'], true) && $key !== 'vehicles' => self::columnValues('vehicles', 'plate_number'),
            $column === 'Driver' => self::columnValues('drivers', 'full_name'),
            in_array($column, ['Requester', 'Submitted by', 'Requested by'], true) => self::columnValues('users', 'full_name'),
            $column === 'Trip' => self::columnValues('trips', 'reference_code'),
            $column === 'Warehouse' => self::columnValues('warehouses', 'warehouse_name'),
            $column === 'Supplier' => self::columnValues('suppliers', 'supplier_name'),
            $column === 'Role' => self::columnValues('roles', 'role_name'),
            $column === 'Privilege' => array_values(self::PRIVILEGES),
            $column === 'Department' => ['Operations', 'Fleet', 'Warehouse', 'Finance', 'Management', 'Administration'],
            $column === 'Category' => ['Fuel', 'Toll', 'Repair', 'Allowance', 'Parking', 'Insurance'],
            $column === 'Priority' => ['Urgent', 'High', 'Normal', 'Low'],
            $column === 'Status' => self::statusOptions($key),
            default => [],
        };
    }

    public static function module(string $key): array
    {
        $module = self::definition($key);
        $module['rows'] = self::rows($key);
        return $module;
    }

    public static function find(string $key, string $id): ?array
    {
        foreach (self::rows($key) as $row) {
            if ((string) $row[0] === $id) {
                return $row;
            }
        }

        return null;
    }

    public static function validate(string $key, array $values, array $files = []): array
    {
        $definition = self::definition($key);
        $errors = [];

        foreach ($definition['columns'] as $index => $column) {
            $value = trim((string) ($values[$index] ?? ''));
            if (self::isRequiredColumn($key, $column) && $value === '') {
                $errors[] = "{$column} is required.";
                continue;
            }

            $options = self::fieldOptions($key, $column);
            if ($value !== '' && $options !== [] && !in_array($value, $options, true)) {
                $errors[] = "{$column} has an invalid value.";
            }

            if ($value !== '' && self::isNumericColumn($column) && !is_numeric(str_replace(',', '', $value))) {
                $errors[] = "{$column} must be numeric.";
            }

            if ($value !== '' && self::isDateColumn($column) && strtotime($value) === false) {
                $errors[] = "{$column} must be a valid date or date/time.";
            }
        }

        foreach (self::fileColumns($key) as $index => $column) {
            $field = 'field_' . $index;
            if (!isset($files[$field]) || ($files[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (($files[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors[] = "{$column} could not be uploaded.";
                continue;
            }

            if (($files[$field]['size'] ?? 0) > 5 * 1024 * 1024) {
                $errors[] = "{$column} must be 5 MB or smaller.";
            }

            $extension = strtolower(pathinfo((string) $files[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx'], true)) {
                $errors[] = "{$column} must be a PDF, image, Word, or Excel file.";
            }
        }

        return $errors;
    }

    public static function save(string $key, ?string $id, array $values, array $files = []): string
    {
        $values = array_values($values);
        $publicId = match ($key) {
            'vehicles' => self::saveVehicle($id, $values),
            'drivers' => self::saveDriver($id, $values),
            'trips' => self::saveTrip($id, $values),
            'requests' => self::saveRequest($id, $values),
            'deliveries' => self::saveDelivery($id, $values, $files),
            'fuel' => self::saveFuel($id, $values, $files),
            'expenses' => self::saveExpense($id, $values),
            'warehouse' => self::saveInventoryItem($id, $values),
            'maintenance' => self::saveMaintenance($id, $values),
            'procurement' => self::savePurchaseRequest($id, $values),
            'users' => self::saveUser($id, $values),
            'reports' => self::saveReport($id, $values),
            default => throw new \InvalidArgumentException('Unknown module.'),
        };

        AuditLog::record($id === null ? 'record.created' : 'record.updated', $key, $publicId);
        return $publicId;
    }

    public static function delete(string $key, string $id, string $reason = ''): void
    {
        [$table, $column] = self::deleteTarget($key);
        $statement = self::db()->prepare("DELETE FROM {$table} WHERE {$column} = ?");
        $statement->execute([$id]);
        AuditLog::record('record.deleted', $key, $id, $reason);
    }

    public static function setStatus(string $key, string $id, int $status): void
    {
        $target = self::statusTarget($key);
        if ($target === null) {
            return;
        }

        [$table, $identityColumn, $statusColumn, $onValue, $offValue] = $target;
        $statement = self::db()->prepare("UPDATE {$table} SET {$statusColumn} = ? WHERE {$identityColumn} = ?");
        $statement->execute([$status === 1 ? $onValue : $offValue, $id]);
        AuditLog::record('record.status_changed', $key, $id, null, ['status' => $status === 1 ? $onValue : $offValue]);
    }

    public static function auditLog(): array
    {
        $statement = self::db()->query(
            'SELECT audit_logs.*, users.full_name
             FROM audit_logs
             LEFT JOIN users ON users.id = audit_logs.user_id
             ORDER BY audit_logs.id DESC
             LIMIT 20'
        );

        return array_map(static function (array $row): string {
            $actor = $row['full_name'] ?: 'System';
            return sprintf('%s - %s %s %s', $row['created_at'], $actor, $row['action_name'], $row['entity_id'] ?? '');
        }, $statement->fetchAll());
    }

    public static function isFileColumn(string $key, string $column): bool
    {
        return in_array($column, array_values(self::fileColumns($key)), true);
    }

    public static function isRequiredColumn(string $key, string $column): bool
    {
        $definition = self::definition($key);
        return in_array($column, $definition['required'] ?? [], true);
    }

    public static function isDateColumn(string $column): bool
    {
        $lower = strtolower($column);
        return str_contains($lower, 'date') ||
            str_contains($lower, 'expiry') ||
            str_contains($lower, 'service') ||
            str_contains($lower, 'due') ||
            str_contains($lower, ' at') ||
            in_array($column, ['Pickup', 'Last login'], true);
    }

    public static function isNumericColumn(string $column): bool
    {
        return in_array($column, ['Amount', 'Litres', 'Unit price', 'Mileage', 'On hand', 'Minimum level', 'Unit cost', 'Estimated cost'], true);
    }

    private static function definition(string $key): array
    {
        $definitions = [
            'vehicles' => ['title' => 'Vehicle Fleet', 'kicker' => 'Fleet registry', 'description' => 'Track vehicle health, assignment and availability in one place.', 'button' => 'Register vehicle', 'columns' => ['Vehicle', 'Type', 'Assigned driver', 'Status', 'Next service date'], 'required' => ['Vehicle', 'Type', 'Status']],
            'trips' => ['title' => 'Trips and Deliveries', 'kicker' => 'Transport planning', 'description' => 'Move requests from approval to proof of delivery.', 'button' => 'Create trip', 'columns' => ['Reference', 'Pickup location', 'Destination', 'Departure at', 'Arrival at', 'Vehicle', 'Driver', 'Status'], 'required' => ['Reference', 'Pickup location', 'Destination', 'Departure at', 'Vehicle', 'Driver', 'Status']],
            'drivers' => ['title' => 'Drivers', 'kicker' => 'People and compliance', 'description' => 'Manage driver availability, licenses, assignments and trip history.', 'button' => 'Add driver', 'columns' => ['Driver', 'License', 'Phone', 'Assigned vehicle', 'Status', 'License expiry date'], 'required' => ['Driver', 'License', 'Status', 'License expiry date']],
            'maintenance' => ['title' => 'Maintenance', 'kicker' => 'Fleet health', 'description' => 'Schedule preventive work, repairs, spare parts and service providers.', 'button' => 'Create work order', 'columns' => ['Work order', 'Vehicle', 'Service', 'Provider', 'Priority', 'Estimated cost', 'Status', 'Due date', 'Completed at'], 'required' => ['Work order', 'Vehicle', 'Service', 'Priority', 'Estimated cost', 'Status', 'Due date']],
            'requests' => ['title' => 'Transport requests', 'kicker' => 'Demand and approvals', 'description' => 'Review requests, approve routes and assign vehicles and drivers.', 'button' => 'New request', 'columns' => ['Request', 'Requester', 'Pickup location', 'Destination', 'Required date', 'Priority', 'Status', 'Notes'], 'required' => ['Request', 'Pickup location', 'Destination', 'Required date', 'Priority', 'Status']],
            'fuel' => ['title' => 'Fuel management', 'kicker' => 'Consumption control', 'description' => 'Record fuel purchases, mileage and consumption by vehicle.', 'button' => 'Record fuel', 'columns' => ['Reference', 'Vehicle', 'Station', 'Litres', 'Unit price', 'Mileage', 'Purchased at', 'Receipt file'], 'required' => ['Reference', 'Vehicle', 'Station', 'Litres', 'Unit price', 'Mileage', 'Purchased at']],
            'expenses' => ['title' => 'Logistics expenses', 'kicker' => 'Cost control', 'description' => 'Track fuel, repairs, allowances, tolls and cost per trip.', 'button' => 'Add expense', 'columns' => ['Reference', 'Category', 'Vehicle', 'Trip', 'Amount', 'Submitted by', 'Status', 'Expense date', 'Notes'], 'required' => ['Reference', 'Category', 'Amount', 'Status', 'Expense date']],
            'warehouse' => ['title' => 'Warehouse & inventory', 'kicker' => 'Stock control', 'description' => 'Manage stock in, stock out, transfers and minimum stock alerts.', 'button' => 'Add item', 'columns' => ['Item', 'SKU', 'Warehouse', 'On hand', 'Minimum level', 'Unit cost', 'Status'], 'required' => ['Item', 'SKU', 'Warehouse', 'On hand', 'Minimum level', 'Unit cost', 'Status']],
            'reports' => ['title' => 'Reports', 'kicker' => 'Insights and exports', 'description' => 'Review utilization, fuel, delivery, maintenance, driver and expense reports.', 'button' => 'Add report', 'columns' => ['Report', 'Period', 'Owner', 'Last generated', 'Format', 'Action'], 'required' => ['Report', 'Period', 'Owner', 'Format', 'Action']],
            'deliveries' => ['title' => 'Deliveries', 'kicker' => 'Proof of delivery', 'description' => 'Monitor delivery progress, recipients, signatures and delivery documents.', 'button' => 'Create delivery', 'columns' => ['Delivery', 'Trip', 'Recipient', 'Destination', 'Status', 'Proof file', 'Signature file', 'Delivered at'], 'required' => ['Delivery', 'Recipient', 'Destination', 'Status']],
            'procurement' => ['title' => 'Procurement', 'kicker' => 'Purchasing workflow', 'description' => 'Manage suppliers, quotations, purchase orders and goods received.', 'button' => 'New purchase request', 'columns' => ['Request', 'Description', 'Supplier', 'Amount', 'Requested by', 'Status'], 'required' => ['Request', 'Description', 'Amount', 'Status']],
            'users' => ['title' => 'Users & permissions', 'kicker' => 'Access control', 'description' => 'Manage system users, roles, access permissions and account status.', 'button' => 'Add user', 'columns' => ['User', 'Email', 'Role', 'Department', 'Phone', 'Last login', 'Status', 'Privilege'], 'required' => ['User', 'Email', 'Role', 'Status']],
        ];

        if (!isset($definitions[$key])) {
            throw new \InvalidArgumentException('Unknown module.');
        }

        return $definitions[$key];
    }

    private static function rows(string $key): array
    {
        return match ($key) {
            'vehicles' => self::fetchRows('SELECT v.plate_number, v.vehicle_type, COALESCE(d.full_name, "None") assigned_driver, v.status, v.next_service_date FROM vehicles v LEFT JOIN drivers d ON d.id = v.assigned_driver_id ORDER BY v.id', static fn ($row) => [$row['plate_number'], $row['vehicle_type'], $row['assigned_driver'], self::label($row['status']), self::formatDate($row['next_service_date'])]),
            'drivers' => self::fetchRows('SELECT d.full_name, d.license_number, d.phone, COALESCE(v.plate_number, "None") assigned_vehicle, d.status, d.license_expiry FROM drivers d LEFT JOIN vehicles v ON v.assigned_driver_id = d.id ORDER BY d.id', static fn ($row) => [$row['full_name'], $row['license_number'], $row['phone'], $row['assigned_vehicle'], self::label($row['status']), self::formatDate($row['license_expiry'])]),
            'trips' => self::fetchRows('SELECT t.reference_code, t.pickup_location, t.destination, t.departure_at, t.arrival_at, COALESCE(v.plate_number, "") vehicle, COALESCE(d.full_name, "") driver, t.status FROM trips t LEFT JOIN vehicles v ON v.id = t.vehicle_id LEFT JOIN drivers d ON d.id = t.driver_id ORDER BY t.id DESC', static fn ($row) => [$row['reference_code'], $row['pickup_location'], $row['destination'], self::formatDateTime($row['departure_at']), self::formatDateTime($row['arrival_at']), $row['vehicle'], $row['driver'], self::label($row['status'])]),
            'requests' => self::fetchRows('SELECT r.reference_code, COALESCE(u.full_name, "") requester, r.pickup_location, r.destination, r.required_date, r.priority, r.status, r.notes FROM transport_requests r LEFT JOIN users u ON u.id = r.requester_id ORDER BY r.id DESC', static fn ($row) => [$row['reference_code'], $row['requester'], $row['pickup_location'], $row['destination'], self::formatDate($row['required_date']), self::label($row['priority']), self::label($row['status']), $row['notes']]),
            'deliveries' => self::fetchRows('SELECT d.delivery_code, COALESCE(t.reference_code, "") trip, d.recipient_name, d.destination, d.status, d.proof_file, d.recipient_signature, d.delivered_at FROM deliveries d LEFT JOIN trips t ON t.id = d.trip_id ORDER BY d.id DESC', static fn ($row) => [$row['delivery_code'], $row['trip'], $row['recipient_name'], $row['destination'], self::label($row['status']), $row['proof_file'], $row['recipient_signature'], self::formatDateTime($row['delivered_at'])]),
            'fuel' => self::fetchRows('SELECT f.id, f.reference_code, COALESCE(v.plate_number, "") vehicle, f.station_name, f.litres, f.unit_price, f.mileage, f.purchased_at, f.receipt_file FROM fuel_records f LEFT JOIN vehicles v ON v.id = f.vehicle_id ORDER BY f.id DESC', static fn ($row) => [$row['reference_code'] ?: 'FUE-' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT), $row['vehicle'], $row['station_name'], self::decimal($row['litres']), self::decimal($row['unit_price']), (string) $row['mileage'], self::formatDateTime($row['purchased_at']), $row['receipt_file']]),
            'expenses' => self::fetchRows('SELECT e.reference_code, e.category, COALESCE(v.plate_number, "") vehicle, COALESCE(t.reference_code, "") trip, e.amount, COALESCE(u.full_name, "") submitted_by, e.status, e.expense_date, e.notes FROM expenses e LEFT JOIN vehicles v ON v.id = e.vehicle_id LEFT JOIN trips t ON t.id = e.trip_id LEFT JOIN users u ON u.id = e.submitted_by ORDER BY e.id DESC', static fn ($row) => [$row['reference_code'], $row['category'], $row['vehicle'], $row['trip'], self::decimal($row['amount']), $row['submitted_by'], self::label($row['status']), self::formatDate($row['expense_date']), $row['notes']]),
            'warehouse' => self::fetchRows('SELECT i.item_name, i.sku, w.warehouse_name, i.quantity, i.minimum_level, i.unit_cost, i.status FROM inventory_items i INNER JOIN warehouses w ON w.id = i.warehouse_id ORDER BY i.id DESC', static fn ($row) => [$row['item_name'], $row['sku'], $row['warehouse_name'], self::decimal($row['quantity']), self::decimal($row['minimum_level']), self::decimal($row['unit_cost']), self::label($row['status'])]),
            'maintenance' => self::fetchRows('SELECT m.work_order_code, COALESCE(v.plate_number, "") vehicle, m.service_name, m.provider_name, m.priority, m.estimated_cost, m.status, m.due_date, m.completed_at FROM maintenance_orders m LEFT JOIN vehicles v ON v.id = m.vehicle_id ORDER BY m.id DESC', static fn ($row) => [$row['work_order_code'], $row['vehicle'], $row['service_name'], $row['provider_name'], self::label($row['priority']), self::decimal($row['estimated_cost']), self::label($row['status']), self::formatDate($row['due_date']), self::formatDateTime($row['completed_at'])]),
            'procurement' => self::fetchRows('SELECT p.request_code, p.description, COALESCE(s.supplier_name, "") supplier, p.amount, COALESCE(u.full_name, "") requested_by, p.status FROM purchase_requests p LEFT JOIN suppliers s ON s.id = p.supplier_id LEFT JOIN users u ON u.id = p.requested_by ORDER BY p.id DESC', static fn ($row) => [$row['request_code'], $row['description'], $row['supplier'], self::decimal($row['amount']), $row['requested_by'], self::label($row['status'])]),
            'users' => self::fetchRows('SELECT u.full_name, u.email, r.role_name, u.department, u.phone, u.last_login_at, u.status, u.prvg FROM users u INNER JOIN roles r ON r.id = u.role_id ORDER BY u.id DESC', static fn ($row) => [$row['full_name'], $row['email'], $row['role_name'], $row['department'], $row['phone'], self::formatDateTime($row['last_login_at']), self::label($row['status']), self::PRIVILEGES[(int) $row['prvg']] ?? self::PRIVILEGES[2]]),
            'reports' => self::fetchRows('SELECT report_name, period_label, owner_name, last_generated_at, format_label, action_label FROM reports ORDER BY id', static fn ($row) => [$row['report_name'], $row['period_label'], $row['owner_name'], self::formatDateTime($row['last_generated_at']), $row['format_label'], $row['action_label']]),
            default => [],
        };
    }

    private static function saveVehicle(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO vehicles (plate_number, vehicle_type, assigned_driver_id, status, next_service_date) VALUES (?, ?, ?, ?, ?)'
            : 'UPDATE vehicles SET plate_number = ?, vehicle_type = ?, assigned_driver_id = ?, status = ?, next_service_date = ? WHERE plate_number = ?';
        self::execute($sql, [$values[0], $values[1], self::idBy('drivers', 'full_name', $values[2] ?? ''), self::enum($values[3]), self::nullable($values[4] ?? ''), ...($id === null ? [] : [$id])]);
        $driverId = self::idBy('drivers', 'full_name', $values[2] ?? '');
        if ($driverId !== null) {
            self::execute('UPDATE vehicles SET assigned_driver_id = NULL WHERE assigned_driver_id = ? AND plate_number <> ?', [$driverId, $values[0]]);
        }
        return $values[0];
    }

    private static function saveDriver(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO drivers (full_name, license_number, phone, status, license_expiry) VALUES (?, ?, ?, ?, ?)'
            : 'UPDATE drivers SET full_name = ?, license_number = ?, phone = ?, status = ?, license_expiry = ? WHERE full_name = ?';
        self::execute($sql, [$values[0], $values[1], self::nullable($values[2] ?? ''), self::enum($values[4]), self::nullable($values[5] ?? ''), ...($id === null ? [] : [$id])]);
        $driverId = self::idBy('drivers', 'license_number', $values[1]);
        if ($driverId !== null) {
            self::execute('UPDATE vehicles SET assigned_driver_id = NULL WHERE assigned_driver_id = ?', [$driverId]);
            if (!empty($values[3]) && $values[3] !== 'None') {
                self::execute('UPDATE vehicles SET assigned_driver_id = ? WHERE plate_number = ?', [$driverId, $values[3]]);
            }
        }
        return $values[0];
    }

    private static function saveTrip(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO trips (reference_code, pickup_location, destination, departure_at, arrival_at, vehicle_id, driver_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            : 'UPDATE trips SET reference_code = ?, pickup_location = ?, destination = ?, departure_at = ?, arrival_at = ?, vehicle_id = ?, driver_id = ?, status = ? WHERE reference_code = ?';
        self::execute($sql, [$values[0], $values[1], $values[2], self::nullable($values[3] ?? ''), self::nullable($values[4] ?? ''), self::idBy('vehicles', 'plate_number', $values[5] ?? ''), self::idBy('drivers', 'full_name', $values[6] ?? ''), self::enum($values[7]), ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function saveRequest(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO transport_requests (reference_code, requester_id, pickup_location, destination, required_date, priority, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            : 'UPDATE transport_requests SET reference_code = ?, requester_id = ?, pickup_location = ?, destination = ?, required_date = ?, priority = ?, status = ?, notes = ? WHERE reference_code = ?';
        self::execute($sql, [$values[0], self::idBy('users', 'full_name', $values[1] ?? ''), $values[2], $values[3], $values[4], self::enum($values[5]), self::enum($values[6]), self::nullable($values[7] ?? ''), ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function saveDelivery(?string $id, array $values, array $files): string
    {
        $proof = self::uploadedPath('deliveries', 5, $files, $values[5] ?? '');
        $signature = self::uploadedPath('deliveries', 6, $files, $values[6] ?? '');
        $sql = $id === null
            ? 'INSERT INTO deliveries (delivery_code, trip_id, recipient_name, destination, status, proof_file, recipient_signature, delivered_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            : 'UPDATE deliveries SET delivery_code = ?, trip_id = ?, recipient_name = ?, destination = ?, status = ?, proof_file = ?, recipient_signature = ?, delivered_at = ? WHERE delivery_code = ?';
        self::execute($sql, [$values[0], self::idBy('trips', 'reference_code', $values[1] ?? ''), $values[2], $values[3], self::enum($values[4]), self::nullable($proof), self::nullable($signature), self::nullable($values[7] ?? ''), ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function saveFuel(?string $id, array $values, array $files): string
    {
        $receipt = self::uploadedPath('fuel', 7, $files, $values[7] ?? '');
        $sql = $id === null
            ? 'INSERT INTO fuel_records (reference_code, vehicle_id, station_name, litres, unit_price, mileage, purchased_at, receipt_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            : 'UPDATE fuel_records SET reference_code = ?, vehicle_id = ?, station_name = ?, litres = ?, unit_price = ?, mileage = ?, purchased_at = ?, receipt_file = ? WHERE reference_code = ?';
        self::execute($sql, [$values[0], self::idBy('vehicles', 'plate_number', $values[1] ?? ''), $values[2], self::number($values[3]), self::number($values[4]), self::nullable($values[5] ?? ''), $values[6], self::nullable($receipt), ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function saveExpense(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO expenses (reference_code, category, vehicle_id, trip_id, amount, submitted_by, status, expense_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            : 'UPDATE expenses SET reference_code = ?, category = ?, vehicle_id = ?, trip_id = ?, amount = ?, submitted_by = ?, status = ?, expense_date = ?, notes = ? WHERE reference_code = ?';
        self::execute($sql, [$values[0], $values[1], self::idBy('vehicles', 'plate_number', $values[2] ?? ''), self::idBy('trips', 'reference_code', $values[3] ?? ''), self::number($values[4]), self::idBy('users', 'full_name', $values[5] ?? ''), self::enum($values[6]), $values[7], self::nullable($values[8] ?? ''), ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function saveInventoryItem(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO inventory_items (item_name, sku, warehouse_id, quantity, minimum_level, unit_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
            : 'UPDATE inventory_items SET item_name = ?, sku = ?, warehouse_id = ?, quantity = ?, minimum_level = ?, unit_cost = ?, status = ? WHERE item_name = ?';
        self::execute($sql, [$values[0], $values[1], self::idBy('warehouses', 'warehouse_name', $values[2]), self::number($values[3]), self::number($values[4]), self::nullable($values[5] ?? ''), self::enum($values[6]), ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function saveMaintenance(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO maintenance_orders (work_order_code, vehicle_id, service_name, provider_name, priority, estimated_cost, status, due_date, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            : 'UPDATE maintenance_orders SET work_order_code = ?, vehicle_id = ?, service_name = ?, provider_name = ?, priority = ?, estimated_cost = ?, status = ?, due_date = ?, completed_at = ? WHERE work_order_code = ?';
        self::execute($sql, [$values[0], self::idBy('vehicles', 'plate_number', $values[1]), $values[2], self::nullable($values[3] ?? ''), self::enum($values[4]), self::nullable($values[5] ?? ''), self::enum($values[6]), self::nullable($values[7] ?? ''), self::nullable($values[8] ?? ''), ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function savePurchaseRequest(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO purchase_requests (request_code, description, supplier_id, amount, requested_by, status) VALUES (?, ?, ?, ?, ?, ?)'
            : 'UPDATE purchase_requests SET request_code = ?, description = ?, supplier_id = ?, amount = ?, requested_by = ?, status = ? WHERE request_code = ?';
        self::execute($sql, [$values[0], $values[1], self::idBy('suppliers', 'supplier_name', $values[2] ?? ''), self::nullable($values[3] ?? ''), self::idBy('users', 'full_name', $values[4] ?? ''), self::enum($values[5]), ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function saveUser(?string $id, array $values): string
    {
        $roleId = self::idBy('roles', 'role_name', $values[2]);
        // Only a privileged user (prvg 1) may grant or change privileges; everyone else leaves it untouched (new users get 2).
        $prvg = \current_prvg() === 1 ? (array_search($values[7] ?? '', self::PRIVILEGES, true) ?: 2) : null;
        $params = [$values[0], $values[1], $roleId, self::nullable($values[3] ?? ''), self::nullable($values[4] ?? ''), self::nullable($values[5] ?? ''), self::enum($values[6])];
        if ($id === null) {
            $sql = 'INSERT INTO users (full_name, email, role_id, department, phone, last_login_at, status, prvg, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $params[] = $prvg ?? 2;
            $params[] = password_hash('password', PASSWORD_DEFAULT);
        } else {
            $sql = 'UPDATE users SET full_name = ?, email = ?, role_id = ?, department = ?, phone = ?, last_login_at = ?, status = ?' . ($prvg !== null ? ', prvg = ?' : '') . ' WHERE full_name = ?';
            if ($prvg !== null) {
                $params[] = $prvg;
            }
            $params[] = $id;
        }
        self::execute($sql, $params);
        return $values[0];
    }

    private static function saveReport(?string $id, array $values): string
    {
        $sql = $id === null
            ? 'INSERT INTO reports (report_name, period_label, owner_name, last_generated_at, format_label, action_label) VALUES (?, ?, ?, ?, ?, ?)'
            : 'UPDATE reports SET report_name = ?, period_label = ?, owner_name = ?, last_generated_at = ?, format_label = ?, action_label = ? WHERE report_name = ?';
        self::execute($sql, [$values[0], $values[1], $values[2], self::nullable($values[3] ?? ''), $values[4], $values[5], ...($id === null ? [] : [$id])]);
        return $values[0];
    }

    private static function fetchRows(string $sql, callable $map): array
    {
        $statement = self::db()->query($sql);
        return array_map($map, $statement->fetchAll());
    }

    private static function execute(string $sql, array $parameters): void
    {
        $statement = self::db()->prepare($sql);
        $statement->execute($parameters);
    }

    private static function columnValues(string $table, string $column): array
    {
        $allowed = [
            'drivers' => ['full_name'],
            'vehicles' => ['plate_number'],
            'users' => ['full_name'],
            'trips' => ['reference_code'],
            'warehouses' => ['warehouse_name'],
            'suppliers' => ['supplier_name'],
            'roles' => ['role_name'],
        ];
        if (!isset($allowed[$table]) || !in_array($column, $allowed[$table], true)) {
            return [];
        }

        $statement = self::db()->query("SELECT {$column} FROM {$table} ORDER BY {$column}");
        return array_values(array_filter(array_column($statement->fetchAll(), $column), static fn ($value): bool => $value !== null && $value !== ''));
    }

    private static function idBy(string $table, string $column, string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || $value === 'None') {
            return null;
        }
        $statement = self::db()->prepare("SELECT id FROM {$table} WHERE {$column} = ? LIMIT 1");
        $statement->execute([$value]);
        $id = $statement->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private static function deleteTarget(string $key): array
    {
        return match ($key) {
            'vehicles' => ['vehicles', 'plate_number'],
            'drivers' => ['drivers', 'full_name'],
            'trips' => ['trips', 'reference_code'],
            'requests' => ['transport_requests', 'reference_code'],
            'deliveries' => ['deliveries', 'delivery_code'],
            'fuel' => ['fuel_records', 'reference_code'],
            'expenses' => ['expenses', 'reference_code'],
            'warehouse' => ['inventory_items', 'item_name'],
            'maintenance' => ['maintenance_orders', 'work_order_code'],
            'procurement' => ['purchase_requests', 'request_code'],
            'users' => ['users', 'full_name'],
            'reports' => ['reports', 'report_name'],
            default => throw new \InvalidArgumentException('Unknown module.'),
        };
    }

    private static function statusTarget(string $key): ?array
    {
        return match ($key) {
            'vehicles' => ['vehicles', 'plate_number', 'status', 'available', 'inactive'],
            'drivers' => ['drivers', 'full_name', 'status', 'available', 'inactive'],
            'trips' => ['trips', 'reference_code', 'status', 'approved', 'cancelled'],
            'requests' => ['transport_requests', 'reference_code', 'status', 'approved', 'cancelled'],
            'deliveries' => ['deliveries', 'delivery_code', 'status', 'in_transit', 'failed'],
            'expenses' => ['expenses', 'reference_code', 'status', 'approved', 'rejected'],
            'warehouse' => ['inventory_items', 'item_name', 'status', 'in_stock', 'out_of_stock'],
            'maintenance' => ['maintenance_orders', 'work_order_code', 'status', 'open', 'cancelled'],
            'procurement' => ['purchase_requests', 'request_code', 'status', 'approved', 'rejected'],
            'users' => ['users', 'full_name', 'status', 'active', 'inactive'],
            default => null,
        };
    }

    private static function statusOptions(string $key): array
    {
        return match ($key) {
            'vehicles' => ['Available', 'On trip', 'Maintenance', 'Inactive'],
            'drivers' => ['Available', 'On trip', 'Off duty', 'Inactive'],
            'trips' => ['Requested', 'Approved', 'Loading', 'In transit', 'Delivered', 'Cancelled'],
            'requests' => ['Pending', 'Approved', 'Assigned', 'Rejected', 'Cancelled'],
            'deliveries' => ['Loading', 'In transit', 'Delivered', 'Failed'],
            'expenses' => ['Pending', 'Approved', 'Rejected'],
            'warehouse' => ['In stock', 'Reorder', 'Out of stock'],
            'maintenance' => ['Open', 'Scheduled', 'In progress', 'Completed', 'Cancelled'],
            'procurement' => ['Draft', 'Quotation', 'Approved', 'Received', 'Rejected'],
            'users' => ['Active', 'Inactive', 'Locked'],
            default => [],
        };
    }

    private static function fileColumns(string $key): array
    {
        return match ($key) {
            'deliveries' => [5 => 'Proof file', 6 => 'Signature file'],
            'fuel' => [7 => 'Receipt file'],
            default => [],
        };
    }

    private static function uploadedPath(string $module, int $index, array $files, string $existing): string
    {
        $field = 'field_' . $index;
        if (!isset($files[$field]) || ($files[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existing;
        }

        $extension = strtolower(pathinfo((string) $files[$field]['name'], PATHINFO_EXTENSION));
        $directory = dirname(__DIR__, 2) . '/storage/uploads/' . $module;
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $target = $directory . '/' . $filename;
        if (!move_uploaded_file((string) $files[$field]['tmp_name'], $target)) {
            throw new \RuntimeException('Uploaded file could not be saved.');
        }

        return 'storage/uploads/' . $module . '/' . $filename;
    }

    private static function enum(string $label): string
    {
        return strtolower(str_replace(' ', '_', trim($label)));
    }

    private static function label(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return ucwords(str_replace('_', ' ', $value));
    }

    private static function formatDate(?string $value): string
    {
        return $value ? date('Y-m-d', strtotime($value)) : '';
    }

    private static function formatDateTime(?string $value): string
    {
        return $value ? date('Y-m-d H:i', strtotime($value)) : '';
    }

    private static function decimal(mixed $value): string
    {
        return $value === null || $value === '' ? '' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private static function number(string $value): string
    {
        return str_replace(',', '', trim($value));
    }

    private static function nullable(string $value): ?string
    {
        $value = trim($value);
        return $value === '' || $value === 'None' ? null : $value;
    }
}
