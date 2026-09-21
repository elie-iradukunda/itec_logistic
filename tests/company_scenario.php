<?php

declare(strict_types=1);

/**
 * The Kigali Fresh Foods walkthrough: one request-to-delivery movement that
 * every role can follow from their own screens, now including the customer,
 * the shipment on board, the route stops and the invoice raised for it.
 */

require __DIR__ . '/support.php';

[$pdo, $root, $dbName] = test_database('logistics_mvc_scenario', true);

try {
    require __DIR__ . '/../bootstrap.php';

    $test = new TestRun('Company scenario tests');

    $scalar = static function (string $sql) use ($pdo): string {
        $value = $pdo->query($sql)->fetchColumn();
        return $value === false || $value === null ? '' : (string) $value;
    };

    // ----------------------------------------------------- the KFF movement
    $request = $pdo->query(
        "SELECT r.status, r.priority, u.email requester
           FROM transport_requests r
           INNER JOIN users u ON u.id = r.requester_id
          WHERE r.reference_code = 'REQ-KFF-001'"
    )->fetch();
    $test->assert($request !== false, 'the KFF transport request exists');
    $test->same('nadine@itec.rw', $request['requester'] ?? '', 'the warehouse manager owns the request');
    $test->same('urgent', $request['priority'] ?? '', 'the request is urgent');
    $test->same('assigned', $request['status'] ?? '', 'the request has been assigned to a trip');

    $trip = $pdo->query(
        "SELECT t.status, t.planned_arrival_at, v.plate_number, v.has_cooling_unit, d.full_name driver_name, u.email driver_email
           FROM trips t
           INNER JOIN vehicles v ON v.id = t.vehicle_id
           INNER JOIN drivers d ON d.id = t.driver_id
           LEFT JOIN users u ON u.id = d.user_id
          WHERE t.reference_code = 'TRP-KFF-001'"
    )->fetch();
    $test->assert($trip !== false, 'the KFF trip exists');
    $test->same('RAC 482D', $trip['plate_number'] ?? '', 'the trip uses RAC 482D');
    $test->same(1, (int) ($trip['has_cooling_unit'] ?? 0), 'the assigned vehicle has a cooling unit for the cold-chain load');
    $test->same('Samuel Niyonzima', $trip['driver_name'] ?? '', 'Samuel is the assigned driver');
    $test->same('samuel@itec.rw', $trip['driver_email'] ?? '', 'Samuel driver profile is linked to his login');
    $test->same('delivered', $trip['status'] ?? '', 'the trip is delivered');

    $delivery = $pdo->query(
        "SELECT d.status, d.proof_file, d.recipient_signature, t.reference_code
           FROM deliveries d
           INNER JOIN trips t ON t.id = d.trip_id
          WHERE d.delivery_code = 'DEL-KFF-001'"
    )->fetch();
    $test->assert($delivery !== false, 'the KFF delivery exists');
    $test->same('TRP-KFF-001', $delivery['reference_code'] ?? '', 'the delivery is linked to the KFF trip');
    $test->same('delivered', $delivery['status'] ?? '', 'the delivery is marked delivered');
    $test->assert(($delivery['proof_file'] ?? '') !== '' && ($delivery['recipient_signature'] ?? '') !== '', 'the delivery carries proof and signature files');

    $scenarioChecks = [
        'warehouse stock item KFF-COOL-001' => "SELECT COUNT(*) FROM inventory_items WHERE sku = 'KFF-COOL-001'",
        'warehouse reorder item KFF-FLOUR-001' => "SELECT COUNT(*) FROM inventory_items WHERE sku = 'KFF-FLOUR-001' AND status = 'reorder'",
        'maintenance order MNT-KFF-001' => "SELECT COUNT(*) FROM maintenance_orders WHERE work_order_code = 'MNT-KFF-001' AND status = 'completed'",
        'fuel record FUE-KFF-001' => "SELECT COUNT(*) FROM fuel_records WHERE reference_code = 'FUE-KFF-001'",
        'expense EXP-KFF-001' => "SELECT COUNT(*) FROM expenses WHERE reference_code = 'EXP-KFF-001' AND status = 'approved'",
        'purchase request PR-KFF-001' => "SELECT COUNT(*) FROM purchase_requests WHERE request_code = 'PR-KFF-001' AND status = 'received'",
        'management report' => "SELECT COUNT(*) FROM reports WHERE report_name = 'KFF Huye delivery performance'",
        'scenario audit log' => "SELECT COUNT(*) FROM audit_logs WHERE action_name = 'scenario.loaded' AND entity_id = 'KFF-2026-09-20'",
    ];
    foreach ($scenarioChecks as $label => $sql) {
        $test->same(1, (int) $scalar($sql), "{$label} exists exactly once after reseeding");
    }

    $test->same(7, (int) $scalar("SELECT COUNT(*) FROM notifications WHERE notification_key LIKE 'NOTIF-KFF-%'"), 'every role receives a scenario notification');

    // ------------------------------------------------ the commercial picture
    $test->assert((int) $scalar("SELECT COUNT(*) FROM customers WHERE status <> 'inactive'") >= 4, 'the company has customers on file');
    $test->assert((int) $scalar("SELECT COUNT(*) FROM shipments WHERE cargo_type IN ('cold_chain','perishable')") >= 2, 'cold-chain shipments are on record');
    $test->assert((int) $scalar('SELECT COUNT(*) FROM trip_stops') >= 4, 'at least one trip has a multi-stop route');
    $test->assert((int) $scalar("SELECT COUNT(*) FROM vehicle_documents WHERE status IN ('expiring','expired')") >= 1, 'a vehicle document needing attention is on record');
    $test->assert((int) $scalar('SELECT COUNT(*) FROM stock_movements') >= 10, 'the stock ledger has movements');
    $test->assert((int) $scalar("SELECT COUNT(*) FROM invoices WHERE status = 'overdue'") >= 1, 'an overdue invoice is on record for finance to chase');
    $test->assert((float) $scalar('SELECT COALESCE(SUM(total_amount), 0) FROM invoices') > 0, 'revenue exists, so trip profitability can be calculated');

    // -------------------------------------------- each role does its own job
    $accounts = (new Models\UserRepository())->activeLoginAccounts();
    $test->same(7, count($accounts), 'the scenario uses the seven seeded company users');

    $roleJobs = [
        'super_admin' => ['email' => 'admin@itec.rw', 'notification' => 'NOTIF-KFF-ADMIN', 'sees' => ['users' => 'admin@itec.rw', 'customers' => 'Rwanda Education Board', 'invoices' => 'INV-2026-0002']],
        'logistics_manager' => ['email' => 'aline@itec.rw', 'notification' => 'NOTIF-KFF-LOGISTICS', 'sees' => ['requests' => 'REQ-KFF-001', 'trips' => 'TRP-KFF-001', 'deliveries' => 'DEL-KFF-001', 'shipments' => 'SHP-2026-0001']],
        'fleet_manager' => ['email' => 'eric@itec.rw', 'notification' => 'NOTIF-KFF-FLEET', 'sees' => ['vehicles' => 'RAC 482D', 'drivers' => 'Samuel Niyonzima', 'maintenance' => 'MNT-KFF-001', 'fuel' => 'FUE-KFF-001', 'vehicle_documents' => 'DOC-2026-0001']],
        'warehouse_manager' => ['email' => 'nadine@itec.rw', 'notification' => 'NOTIF-KFF-WAREHOUSE', 'sees' => ['warehouse' => 'KFF-COOL-001', 'procurement' => 'PR-KFF-001', 'requests' => 'REQ-KFF-001', 'movements' => 'MOV-2026-0009']],
        'driver' => ['email' => 'samuel@itec.rw', 'notification' => 'NOTIF-KFF-DRIVER', 'sees' => ['trips' => 'TRP-KFF-001', 'deliveries' => 'DEL-KFF-001']],
        'finance' => ['email' => 'emmanuel@itec.rw', 'notification' => 'NOTIF-KFF-FINANCE', 'sees' => ['fuel' => 'FUE-KFF-001', 'expenses' => 'EXP-KFF-001', 'procurement' => 'PR-KFF-001', 'invoices' => 'INV-2026-0002', 'rates' => 'RATE-2026-0001']],
        'management' => ['email' => 'jeanpierre@itec.rw', 'notification' => 'NOTIF-KFF-MANAGEMENT', 'sees' => ['customers' => 'Rwanda Education Board', 'invoices' => 'INV-2026-0002']],
    ];

    foreach ($roleJobs as $role => $job) {
        $account = $accounts[$role] ?? null;
        $test->assert($account !== null && $account['email'] === $job['email'], "{$role} has the expected company user");
        if ($account === null) {
            continue;
        }

        test_sign_in($account, $role, $role === 'super_admin' ? 1 : 2);

        $keys = array_column(current_notifications(20), 'notification_key');
        $test->assert(in_array($job['notification'], $keys, true), "{$role} receives its scenario notification");

        foreach ($job['sees'] as $moduleKey => $needle) {
            $test->assert(role_can($moduleKey), "{$role} may open {$moduleKey}");
            $listing = Models\LogisticsData::listing($moduleKey, ['q' => $needle, 'per_page' => 25], current_context());
            $test->assert($listing['total'] >= 1, "{$role} finds {$needle} in {$moduleKey}");
        }
    }

    // ------------------------------------------- reports management will read
    test_sign_in($accounts['management'], 'management');
    foreach (Models\ReportData::availableFor('management') as $reportKey => $label) {
        $report = Models\ReportData::run($reportKey, '2026-01-01', '2026-12-31');
        $test->assert(isset($report['rows']), "management can run the {$label} report");
    }

    test_sign_in($accounts['super_admin'], 'super_admin', 1);
    foreach (Models\ReportData::keys() as $reportKey) {
        $report = Models\ReportData::run($reportKey, '2026-01-01', '2026-12-31');
        $test->assert($report['rows'] !== [], "the {$reportKey} report has data for the seeded year");
    }

    // The driver sees their own work and nothing else.
    test_sign_in($accounts['driver'], 'driver');
    $driverTrips = Models\LogisticsData::listing('trips', ['per_page' => 50], current_context());
    $test->assert($driverTrips['total'] >= 1, 'the driver sees the KFF trip');
    $samuelId = (int) $scalar("SELECT d.id FROM drivers d INNER JOIN users u ON u.id = d.user_id WHERE u.email = 'samuel@itec.rw'");
    foreach ($driverTrips['rows'] as $row) {
        $owner = (int) $scalar("SELECT driver_id FROM trips WHERE id = {$row['id']}");
        $test->same($samuelId, $owner, 'every trip the driver sees is their own');
    }

    $test->finish();
} finally {
    $pdo = null;
    $root->exec("DROP DATABASE IF EXISTS `{$dbName}`");
}
