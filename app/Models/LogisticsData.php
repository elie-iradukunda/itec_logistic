<?php

namespace Models;

class LogisticsData
{
    public static function fieldOptions(string $key, string $column): array
    {
        $options = [
            'vehicles' => ['Type' => ['Delivery truck', 'Pickup', 'Box truck', 'Van', 'Motorcycle'], 'Assigned driver' => ['Samuel N.', 'Marie U.', 'Eric M.', 'Aurore K.', 'Jean P.', 'None'], 'Status' => ['Available', 'On trip', 'Maintenance', 'Inactive']],
            'trips' => ['Route' => ['Kigali - Huye', 'Kigali - Musanze', 'Kigali - Rubavu', 'Kigali - Rusizi', 'Kigali - Nyagatare'], 'Vehicle' => ['RAC 482D', 'RAB 118K', 'RAC 901P', 'RAC 774F', 'RAB 640C'], 'Status' => ['Requested', 'Approved', 'Loading', 'In transit', 'Delivered', 'Cancelled']],
            'drivers' => ['Assigned vehicle' => ['RAC 482D', 'RAB 118K', 'RAC 901P', 'RAB 332M', 'None'], 'Status' => ['Available', 'On trip', 'Off duty', 'Inactive']],
            'maintenance' => ['Vehicle' => ['RAC 482D', 'RAB 118K', 'RAC 901P', 'RAB 332M'], 'Priority' => ['Urgent', 'High', 'Normal', 'Low']],
            'requests' => ['Requester' => ['Operations', 'Warehouse', 'Procurement', 'Finance', 'Management'], 'Route' => ['Kigali - Huye', 'Kigali - Musanze', 'Kigali - Rubavu', 'Kigali - Rusizi'], 'Priority' => ['Urgent', 'High', 'Normal', 'Low'], 'Status' => ['Pending approval', 'Approved', 'Assigned', 'Rejected']],
            'fuel' => ['Vehicle' => ['RAC 482D', 'RAB 118K', 'RAC 901P', 'RAC 774F'], 'Station' => ['SP Kigali', 'SP Remera', 'Kobil Gikondo', 'SP Nyabugogo']],
            'expenses' => ['Category' => ['Fuel', 'Toll', 'Repair', 'Allowance', 'Parking', 'Insurance'], 'Vehicle / trip' => ['RAC 482D', 'RAB 118K', 'RAC 901P', 'TRP-0247'], 'Status' => ['Pending', 'Approved', 'Rejected']],
            'warehouse' => ['Warehouse' => ['Kigali central', 'Huye depot', 'Musanze depot', 'Rubavu depot'], 'Status' => ['In stock', 'Reorder', 'Out of stock']],
            'deliveries' => ['Trip' => ['TRP-0248', 'TRP-0247', 'TRP-0246', 'TRP-0245'], 'Destination' => ['Huye', 'Musanze', 'Rubavu', 'Rusizi'], 'Status' => ['Loading', 'In transit', 'Delivered', 'Failed'], 'Proof' => ['Pending', 'Signed', 'Uploaded']],
            'procurement' => ['Supplier' => ['Kigali Auto Care', 'Secure Rwanda', 'Lubricants Ltd', 'Fleet Workshop'], 'Status' => ['Draft', 'Quotation', 'Approved', 'Received', 'Rejected']],
            'users' => ['Role' => ['Super Admin', 'Logistics Manager', 'Fleet Manager', 'Warehouse Manager', 'Driver', 'Finance', 'Management'], 'Department' => ['Operations', 'Fleet', 'Warehouse', 'Finance', 'Management'], 'Status' => ['Active', 'Inactive']],
        ];
        return $options[$key][$column] ?? [];
    }

    public static function module(string $key): array
    {
        $modules = [
            'vehicles' => ['title' => 'Vehicle Fleet', 'kicker' => 'Fleet registry', 'description' => 'Track vehicle health, assignment and availability in one place.', 'button' => 'Register vehicle', 'columns' => ['Vehicle', 'Type', 'Assigned driver', 'Status', 'Next service'], 'rows' => [['RAC 482D', 'Delivery truck', 'Samuel N.', 'On trip', '12 Oct 2026'], ['RAB 118K', 'Pickup', 'Marie U.', 'Available', '18 Oct 2026'], ['RAC 901P', 'Box truck', 'Eric M.', 'Maintenance', 'Today'], ['RAB 332M', 'Delivery truck', 'None', 'Available', '22 Sep 2026'], ['RAC 774F', 'Van', 'Jean P.', 'On trip', '04 Nov 2026'], ['RAB 640C', 'Pickup', 'Aurore K.', 'Available', '28 Sep 2026'], ['RAC 208L', 'Box truck', 'David H.', 'Inactive', '15 Dec 2026'], ['RAB 519T', 'Motorcycle', 'Patrick R.', 'On trip', '30 Oct 2026'], ['RAC 863N', 'Delivery truck', 'None', 'Available', '07 Nov 2026'], ['RAB 407G', 'Van', 'Claudine M.', 'Maintenance', 'Today']]],
            'trips' => ['title' => 'Trips and Deliveries', 'kicker' => 'Transport planning', 'description' => 'Move requests from approval to proof of delivery.', 'button' => 'Create trip', 'columns' => ['Reference', 'Route', 'Pickup', 'Vehicle', 'Status'], 'rows' => [['TRP-0248', 'Kigali - Huye', '18 Sep, 08:30', 'RAC 482D', 'In transit'], ['TRP-0247', 'Kigali - Musanze', '18 Sep, 07:00', 'RAB 118K', 'Delivered'], ['TRP-0246', 'Kigali - Rubavu', '18 Sep, 10:15', 'RAC 901P', 'Loading'], ['TRP-0245', 'Kigali - Rusizi', '18 Sep, 06:45', 'RAC 774F', 'Approved'], ['TRP-0244', 'Kigali - Nyagatare', '17 Sep, 09:00', 'RAB 640C', 'Delivered'], ['TRP-0243', 'Huye - Kigali', '17 Sep, 14:30', 'RAB 332M', 'Cancelled'], ['TRP-0242', 'Kigali - Muhanga', '16 Sep, 11:00', 'RAB 519T', 'Delivered'], ['TRP-0241', 'Kigali - Kayonza', '16 Sep, 08:00', 'RAC 208L', 'In transit'], ['TRP-0240', 'Musanze - Kigali', '15 Sep, 13:15', 'RAC 863N', 'Delivered'], ['TRP-0239', 'Kigali - Rwamagana', '15 Sep, 07:30', 'RAB 407G', 'Loading']]],
            'drivers' => ['title' => 'Drivers', 'kicker' => 'People and compliance', 'description' => 'Manage driver availability, licenses, assignments and trip history.', 'button' => 'Add driver', 'columns' => ['Driver', 'License', 'Phone', 'Assigned vehicle', 'Status', 'License expiry'], 'rows' => [['Marie U.', 'RWA-DL-1028', '+250 788 120 442', 'RAB 118K', 'Available', '18 Mar 2027'], ['Samuel N.', 'RWA-DL-0912', '+250 788 632 119', 'RAC 482D', 'On trip', '04 Dec 2026'], ['Eric M.', 'RWA-DL-0744', '+250 783 210 084', 'RAC 901P', 'Off duty', '29 Jan 2027']]],
            'maintenance' => ['title' => 'Maintenance', 'kicker' => 'Fleet health', 'description' => 'Schedule preventive work, repairs, spare parts and service providers.', 'button' => 'Create work order', 'columns' => ['Work order', 'Vehicle', 'Service', 'Provider', 'Priority', 'Due date'], 'rows' => [['MNT-0081', 'RAC 901P', 'Brake inspection', 'Kigali Auto Care', 'Urgent', 'Today'], ['MNT-0080', 'RAB 332M', 'Oil and filter', 'Fleet workshop', 'Normal', '22 Sep 2026'], ['MNT-0079', 'RAC 482D', 'Preventive service', 'Fleet workshop', 'Normal', '12 Oct 2026']]],
            'requests' => ['title' => 'Transport requests', 'kicker' => 'Demand and approvals', 'description' => 'Review requests, approve routes and assign vehicles and drivers.', 'button' => 'New request', 'columns' => ['Request', 'Requester', 'Route', 'Required date', 'Priority', 'Status'], 'rows' => [['REQ-0312', 'Procurement', 'Kigali - Huye', '20 Sep 2026', 'High', 'Pending approval'], ['REQ-0311', 'Warehouse', 'Kigali - Rubavu', '21 Sep 2026', 'Normal', 'Approved'], ['REQ-0310', 'Operations', 'Kigali - Musanze', '22 Sep 2026', 'Normal', 'Assigned']]],
            'fuel' => ['title' => 'Fuel management', 'kicker' => 'Consumption control', 'description' => 'Record fuel purchases, mileage and consumption by vehicle.', 'button' => 'Record fuel', 'columns' => ['Reference', 'Vehicle', 'Station', 'Litres', 'Amount', 'Date'], 'rows' => [['FUE-0198', 'RAC 482D', 'SP Kigali', '82 L', 'RWF 126,280', '18 Sep 2026'], ['FUE-0197', 'RAB 118K', 'Kobil Remera', '54 L', 'RWF 83,160', '18 Sep 2026'], ['FUE-0196', 'RAC 901P', 'SP Nyabugogo', '68 L', 'RWF 104,720', '17 Sep 2026']]],
            'expenses' => ['title' => 'Logistics expenses', 'kicker' => 'Cost control', 'description' => 'Track fuel, repairs, allowances, tolls and cost per trip.', 'button' => 'Add expense', 'columns' => ['Reference', 'Category', 'Vehicle / trip', 'Amount', 'Submitted by', 'Status'], 'rows' => [['EXP-0441', 'Fuel', 'RAC 482D', 'RWF 126,280', 'Aline M.', 'Approved'], ['EXP-0440', 'Toll', 'TRP-0247', 'RWF 8,000', 'Marie U.', 'Pending'], ['EXP-0439', 'Repair', 'RAC 901P', 'RWF 245,000', 'Eric M.', 'Approved']]],
            'warehouse' => ['title' => 'Warehouse & inventory', 'kicker' => 'Stock control', 'description' => 'Manage stock in, stock out, transfers and minimum stock alerts.', 'button' => 'Add item', 'columns' => ['Item', 'SKU', 'Warehouse', 'On hand', 'Minimum level', 'Status'], 'rows' => [['Brake pads', 'SP-BRK-001', 'Kigali central', '24', '10', 'In stock'], ['Engine oil 15W40', 'SP-OIL-015', 'Kigali central', '08', '12', 'Reorder'], ['Safety vest', 'EQ-VST-004', 'Huye depot', '146', '50', 'In stock']]],
            'reports' => ['title' => 'Reports', 'kicker' => 'Insights and exports', 'description' => 'Review utilization, fuel, delivery, maintenance, driver and expense reports.', 'button' => 'Export report', 'columns' => ['Report', 'Period', 'Owner', 'Last generated', 'Format', 'Action'], 'rows' => [['Vehicle utilization', 'September 2026', 'Fleet manager', 'Today, 09:42', 'PDF', 'View'], ['Fuel consumption', 'September 2026', 'Finance', 'Yesterday, 16:20', 'Excel', 'View'], ['Delivery performance', 'Q3 2026', 'Operations', '15 Sep 2026', 'PDF', 'View']]],
            'deliveries' => ['title' => 'Deliveries', 'kicker' => 'Proof of delivery', 'description' => 'Monitor delivery progress, recipients, signatures and delivery documents.', 'button' => 'Create delivery', 'columns' => ['Delivery', 'Trip', 'Recipient', 'Destination', 'Status', 'Proof'], 'rows' => [['DEL-0218', 'TRP-0248', 'Huye depot', 'Huye', 'In transit', 'Pending'], ['DEL-0217', 'TRP-0247', 'Musanze warehouse', 'Musanze', 'Delivered', 'Signed'], ['DEL-0216', 'TRP-0246', 'Rubavu branch', 'Rubavu', 'Loading', 'Pending']]],
            'procurement' => ['title' => 'Procurement', 'kicker' => 'Purchasing workflow', 'description' => 'Manage suppliers, quotations, purchase orders and goods received.', 'button' => 'New purchase request', 'columns' => ['Request', 'Description', 'Supplier', 'Amount', 'Requested by', 'Status'], 'rows' => [['PR-0091', 'Brake pads and filters', 'Kigali Auto Care', 'RWF 680,000', 'Fleet manager', 'Quotation'], ['PR-0090', 'Safety equipment', 'Secure Rwanda', 'RWF 420,000', 'Operations', 'Approved'], ['PR-0089', 'Engine oil', 'Lubricants Ltd', 'RWF 310,000', 'Warehouse', 'Received']]],
            'users' => ['title' => 'Users & permissions', 'kicker' => 'Access control', 'description' => 'Manage system users, roles, access permissions and account status.', 'button' => 'Add user', 'columns' => ['User', 'Email', 'Role', 'Department', 'Last login', 'Status'], 'rows' => [['Aline Mukamana', 'aline@itec.rw', 'Logistics manager', 'Operations', 'Today, 08:42', 'Active'], ['Samuel Niyonzima', 'samuel@itec.rw', 'Driver', 'Fleet', 'Today, 06:18', 'Active'], ['Eric Murenzi', 'eric@itec.rw', 'Fleet manager', 'Fleet', '17 Sep 2026', 'Active']]],
        ];

        if (!isset($modules[$key])) {
            return [];
        }

        $module = $modules[$key];
        $module['rows'] = self::records($key, $module['rows']);
        return $module;
    }

    public static function find(string $key, string $id): ?array
    {
        foreach (self::module($key)['rows'] as $row) {
            if ((string) $row[0] === $id) {
                return $row;
            }
        }
        return null;
    }

    public static function save(string $key, ?string $id, array $values): string
    {
        $rows = self::module($key)['rows'];
        $values = array_values(array_slice($values, 0, count(self::module($key)['columns'])));
        if ($id !== null) {
            foreach ($rows as $index => $row) {
                if ((string) $row[0] === $id) {
                    $rows[$index] = $values;
                    $_SESSION['logistics_records_v2_' . $key] = $rows;
                    self::audit('Updated ' . $id . ' in ' . $key);
                    return $id;
                }
            }
        }
        $newId = strtoupper(substr($key, 0, 3)) . '-' . str_pad((string) (count($rows) + 1), 4, '0', STR_PAD_LEFT);
        $values[0] = $values[0] ?: $newId;
        $rows[] = $values;
        $_SESSION['logistics_records_v2_' . $key] = $rows;
        self::audit(($id ? 'Updated ' . $id : 'Created ' . $values[0]) . ' in ' . $key);
        return $values[0];
    }

    public static function delete(string $key, string $id, string $reason = ''): void
    {
        $_SESSION['logistics_records_v2_' . $key] = array_values(array_filter(self::module($key)['rows'], static fn (array $row): bool => (string) $row[0] !== $id));
        self::audit('Deleted ' . $id . ' from ' . $key . '. Reason: ' . ($reason ?: 'No reason provided'));
    }

    public static function setStatus(string $key, string $id, int $status): void
    {
        $module = self::module($key);
        $statusIndex = array_search('Status', $module['columns'], true);
        if ($statusIndex === false) {
            return;
        }
        $rows = $module['rows'];
        foreach ($rows as $index => $row) {
            if ((string) $row[0] === $id) {
                $row[$statusIndex] = $status === 1 ? 'Active' : 'Inactive';
                $rows[$index] = $row;
                self::audit(($status ? 'Activated ' : 'Deactivated ') . $id . ' in ' . $key);
            }
        }
        $_SESSION['logistics_records_v2_' . $key] = $rows;
    }

    public static function auditLog(): array
    {
        return $_SESSION['logistics_audit'] ?? [];
    }

    private static function audit(string $message): void
    {
        $_SESSION['logistics_audit'][] = date('Y-m-d H:i:s') . ' - ' . $message;
    }

    private static function records(string $key, array $seed): array
    {
        $sessionKey = 'logistics_records_v2_' . $key;
        if (!isset($_SESSION[$sessionKey])) {
            while (count($seed) < 10) {
                $row = $seed[(count($seed) - 1) % max(count($seed), 1)];
                $row = self::enrichRow($key, $row, count($seed));
                $seed[] = $row;
            }
            $_SESSION[$sessionKey] = $seed;
        }
        return $_SESSION[$sessionKey];
    }

    private static function enrichRow(string $key, array $row, int $index): array
    {
        $variants = [
            'drivers' => ['Aurore K.', 'Jean P.', 'Patrick R.', 'Claudine M.', 'David H.', 'Nadine T.', 'Emmanuel S.'],
            'maintenance' => ['Tire rotation', 'Transmission check', 'AC repair', 'Battery replacement', 'Body inspection', 'Engine diagnostics', 'Lights inspection'],
            'requests' => ['Kigali - Rusizi', 'Kigali - Nyagatare', 'Huye - Kigali', 'Kigali - Muhanga', 'Kigali - Kayonza', 'Musanze - Kigali', 'Kigali - Rwamagana'],
            'fuel' => ['SP Remera', 'Kobil Kimihurura', 'SP Nyabugogo', 'Kobil Gikondo', 'SP Kicukiro', 'Rubis Muhima', 'SP Kimironko'],
            'expenses' => ['Allowance', 'Toll', 'Repair', 'Parking', 'Loading', 'Insurance', 'Communication'],
            'warehouse' => ['Air filters', 'Reflective jackets', 'First aid kits', 'Tire valves', 'Gloves', 'Warning triangles', 'Coolant 5L'],
            'reports' => ['Maintenance cost', 'Driver performance', 'Inventory movement', 'Trip profitability', 'Open requests', 'Expense summary', 'Delivery SLA'],
            'deliveries' => ['Kigali depot', 'Nyagatare branch', 'Muhanga warehouse', 'Kayonza branch', 'Rusizi depot', 'Rwamagana store', 'Kicukiro client'],
            'procurement' => ['Tires and tubes', 'GPS tracking units', 'Workshop tools', 'PPE equipment', 'Spare batteries', 'Warehouse shelving', 'Fuel cards'],
            'users' => ['Nadine Tuyisenge', 'Emmanuel Safari', 'Claudine Mukamana', 'David Habimana', 'Aurore Kayitesi', 'Patrick Rukundo', 'Jean Pierre'],
        ];
        $row[0] = strtoupper(substr($key, 0, 3)) . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
        if (isset($variants[$key])) {
            $row[1] = $variants[$key][($index - 3) % count($variants[$key])];
        }
        return $row;
    }
}
