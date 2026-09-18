<?php

declare(strict_types=1);

$dbName = 'logistics_mvc_test_' . date('YmdHis');
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

try {
    $schema = str_replace('logistics_mvc', $dbName, (string) file_get_contents(__DIR__ . '/../database/schema.sql'));
    $seed = str_replace('logistics_mvc', $dbName, (string) file_get_contents(__DIR__ . '/../database/seed.sql'));
    $root->exec($schema);
    $root->exec($seed);

    require __DIR__ . '/../bootstrap.php';

    $pdo = new PDO($dbDsn, $db['user'], $db['pass'], $options);
    $failures = [];
    $assert = static function (bool $condition, string $message) use (&$failures): void {
        if (!$condition) {
            $failures[] = $message;
        }
    };

    $users = new Models\UserRepository();
    $admin = $users->findActiveByEmail('admin@itec.rw');
    $assert($admin !== null, 'admin demo user exists');
    $assert(password_verify('password', $admin['password_hash'] ?? ''), 'admin password verifies');

    $loginAccounts = demo_accounts();
    $assert(isset($loginAccounts['logistics_manager']), 'login account list reads logistics manager from database');
    $assert(($loginAccounts['logistics_manager']['email'] ?? '') === 'aline@itec.rw', 'login account email comes from seeded database user');

    foreach (role_definitions() as $roleKey => $definition) {
        $_SESSION['logistics_authenticated'] = true;
        $_SESSION['logistics_role'] = $roleKey;
        $assert(role_can('dashboard'), "{$roleKey} can access dashboard");
        if (($definition['routes'] ?? null) === '*') {
            $assert(role_can('users'), 'super admin can access users');
        }
    }

    $errors = Models\LogisticsData::validate('vehicles', ['', '', '', '', '']);
    $assert($errors !== [], 'vehicle validation catches missing fields');

    $created = Models\LogisticsData::save('vehicles', null, ['RZZ 999Z', 'Pickup', 'None', 'Available', '2026-12-31']);
    $assert($created === 'RZZ 999Z', 'vehicle create returns public id');
    $vehicleCount = (int) $pdo->query("SELECT COUNT(*) FROM vehicles WHERE plate_number = 'RZZ 999Z'")->fetchColumn();
    $assert($vehicleCount === 1, 'vehicle create persisted to database');

    Models\LogisticsData::setStatus('vehicles', 'RZZ 999Z', 0);
    $status = (string) $pdo->query("SELECT status FROM vehicles WHERE plate_number = 'RZZ 999Z'")->fetchColumn();
    $assert($status === 'inactive', 'vehicle status toggle persisted');

    $reports = Models\LogisticsData::module('reports');
    $assert(count($reports['rows']) >= 1, 'reports module reads database rows');

    Models\AuditLog::record('tests.smoke', 'tests', 'smoke');
    $auditCount = (int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action_name = 'tests.smoke'")->fetchColumn();
    $assert($auditCount === 1, 'audit log persisted');

    if ($failures !== []) {
        foreach ($failures as $failure) {
            fwrite(STDERR, "FAIL: {$failure}\n");
        }
        exit(1);
    }

    echo "Smoke tests passed.\n";
} finally {
    $root->exec("DROP DATABASE IF EXISTS `{$dbName}`");
}
