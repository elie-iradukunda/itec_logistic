<?php

declare(strict_types=1);

$dbName = 'logistics_mvc_seed_' . date('YmdHis');
putenv('LOGISTICS_DB_HOST=127.0.0.1');
putenv('LOGISTICS_DB_NAME=' . $dbName);
putenv('LOGISTICS_DB_USER=root');
putenv('LOGISTICS_DB_PASS=');

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];
$rootDsn = sprintf('mysql:host=%s;charset=%s', $db['host'], $db['charset']);
$dbDsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $dbName, $db['charset']);
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$root = new PDO($rootDsn, $db['user'], $db['pass'], $options);
$root->exec("DROP DATABASE IF EXISTS `{$dbName}`");
$pdo = null;

try {
    $schema = str_replace('logistics_mvc', $dbName, (string) file_get_contents(__DIR__ . '/../database/schema.sql'));
    $seed = str_replace('logistics_mvc', $dbName, (string) file_get_contents(__DIR__ . '/../database/seed.sql'));
    $root->exec($schema);
    $root->exec($seed);
    $root->exec($seed);

    require __DIR__ . '/../bootstrap.php';

    $pdo = new PDO($dbDsn, $db['user'], $db['pass'], $options);
    $failures = [];
    $assert = static function (bool $condition, string $message) use (&$failures): void {
        if (!$condition) {
            $failures[] = $message;
        }
    };

    $expectedCounts = [
        'roles' => 7,
        'users' => 7,
        'vehicles' => 10,
        'drivers' => 10,
        'warehouses' => 4,
        'suppliers' => 4,
        'trips' => 10,
        'transport_requests' => 4,
        'deliveries' => 4,
        'inventory_items' => 5,
        'maintenance_orders' => 4,
        'purchase_requests' => 4,
        'fuel_records' => 3,
        'expenses' => 4,
        'reports' => 9,
        'notifications' => 7,
        'audit_logs' => 1,
    ];

    foreach ($expectedCounts as $table => $expected) {
        $actual = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        $assert($actual === $expected, "{$table} has {$expected} seeded rows after reseeding");
    }

    $repository = new Models\UserRepository();
    $accounts = $repository->activeLoginAccounts();
    $assert(count($accounts) === 7, 'all seven seeded login accounts are active database users');

    $privileges = $pdo->query('SELECT email, prvg FROM users')->fetchAll(PDO::FETCH_KEY_PAIR);
    $assert((int) ($privileges['admin@itec.rw'] ?? 0) === 1, 'the seeded admin has privilege 1 (may switch roles)');
    $assert(count(array_filter($privileges, static fn ($prvg): bool => (int) $prvg !== 2)) === 1, 'every other seeded user keeps the default privilege 2');

    $allRoutes = ['dashboard', 'vehicles', 'trips', 'deliveries', 'requests', 'drivers', 'maintenance', 'fuel', 'expenses', 'warehouse', 'procurement', 'reports', 'users'];
    $moduleRoutes = ['vehicles', 'trips', 'deliveries', 'requests', 'drivers', 'maintenance', 'fuel', 'expenses', 'warehouse', 'procurement', 'reports', 'users'];

    foreach (role_definitions() as $roleKey => $definition) {
        $account = $accounts[$roleKey] ?? null;
        $assert($account !== null, "{$roleKey} has an active database user");
        if ($account === null) {
            continue;
        }

        $user = $repository->findActiveByEmail((string) $account['email']);
        $assert($user !== null, "{$roleKey} user can be loaded by email");
        $assert($user !== null && password_verify('password', $user['password_hash']), "{$roleKey} seeded password verifies");

        $_SESSION['logistics_authenticated'] = true;
        $_SESSION['logistics_role'] = $roleKey;
        $_SESSION['logistics_user_id'] = (int) $account['id'];
        $_SESSION['logistics_user_name'] = (string) $account['name'];
        $_SESSION['logistics_user_email'] = (string) $account['email'];

        $allowed = ($definition['routes'] ?? []) === '*' ? $allRoutes : (array) $definition['routes'];
        foreach ($allRoutes as $route) {
            $shouldAllow = in_array($route, $allowed, true);
            $assert(role_can($route) === $shouldAllow, "{$roleKey} route permission is correct for {$route}");
        }

        $notifications = current_notifications();
        $assert(count($notifications) >= 1, "{$roleKey} receives at least one seeded notification");
        $assert(unread_notification_count() >= 1, "{$roleKey} has an unread notification count");
        foreach ($notifications as $notification) {
            $route = (string) ($notification['link_route'] ?? '');
            $assert($route === '' || role_can($route), "{$roleKey} notification links to an allowed route");
        }

        foreach ($moduleRoutes as $moduleRoute) {
            if (!role_can($moduleRoute)) {
                continue;
            }

            $module = Models\LogisticsData::module($moduleRoute);
            $assert(isset($module['title'], $module['columns'], $module['rows']), "{$roleKey} can load {$moduleRoute} module data");
            $assert(count($module['rows']) >= 1, "{$moduleRoute} has seeded rows for {$roleKey}");
        }
    }

    $_SESSION['logistics_authenticated'] = true;
    $_SESSION['logistics_role'] = 'logistics_manager';
    $_SESSION['logistics_user_id'] = (int) $accounts['logistics_manager']['id'];
    $_SESSION['logistics_user_name'] = (string) $accounts['logistics_manager']['name'];
    $_SESSION['logistics_user_email'] = (string) $accounts['logistics_manager']['email'];

    Models\LogisticsData::save('requests', null, ['REQ-TST-100', 'Aline Mukamana', 'Kigali', 'Huye', '2026-09-24', 'High', 'Pending', 'Seed integrity workflow request.']);
    Models\LogisticsData::setStatus('requests', 'REQ-TST-100', 1);
    $requestStatus = (string) $pdo->query("SELECT status FROM transport_requests WHERE reference_code = 'REQ-TST-100'")->fetchColumn();
    $assert($requestStatus === 'approved', 'workflow request can be approved');

    Models\LogisticsData::save('trips', null, ['TRP-TST-100', 'Kigali', 'Huye', '2026-09-24 08:00', '', 'RAB 118K', 'Marie Uwase', 'Approved']);
    Models\LogisticsData::save('deliveries', null, ['DEL-TST-100', 'TRP-TST-100', 'Huye depot', 'Huye', 'Loading', '', '', ''], []);
    Models\LogisticsData::save('expenses', null, ['EXP-TST-100', 'Allowance', 'RAB 118K', 'TRP-TST-100', '45000', 'Aline Mukamana', 'Pending', '2026-09-24', 'Workflow test allowance.']);

    $workflowCounts = [
        'transport_requests' => "reference_code = 'REQ-TST-100'",
        'trips' => "reference_code = 'TRP-TST-100'",
        'deliveries' => "delivery_code = 'DEL-TST-100'",
        'expenses' => "reference_code = 'EXP-TST-100'",
    ];

    foreach ($workflowCounts as $table => $where) {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM {$table} WHERE {$where}")->fetchColumn();
        $assert($count === 1, "{$table} persisted workflow test record");
    }

    $auditCount = (int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action_name IN ('record.created', 'record.status_changed')")->fetchColumn();
    $assert($auditCount >= 5, 'workflow actions create audit log updates');

    if ($failures !== []) {
        foreach ($failures as $failure) {
            fwrite(STDERR, "FAIL: {$failure}\n");
        }
        exit(1);
    }

    echo "Seed integrity tests passed.\n";
} finally {
    $pdo = null;
    $root->exec("DROP DATABASE IF EXISTS `{$dbName}`");
}
