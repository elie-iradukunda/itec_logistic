<?php

declare(strict_types=1);

/**
 * A fresh install: scripts/migrate.php builds the whole database from nothing,
 * and running it a second time changes nothing.
 */

$dbName = 'logistics_mvc_migrate_' . date('YmdHis');
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

$cleanup = new PDO($rootDsn, $db['user'], $db['pass'], $options);
$cleanup->exec("DROP DATABASE IF EXISTS `{$dbName}`");
$pdo = null;

try {
    ob_start();
    require __DIR__ . '/../scripts/migrate.php';
    $firstRun = (string) ob_get_clean();

    $pdo = new PDO($dbDsn, $db['user'], $db['pass'], $options);

    $tables = $pdo->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN);
    $snapshot = static function () use ($pdo, $tables): array {
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        }
        return $counts;
    };
    $afterFirst = $snapshot();

    ob_start();
    require __DIR__ . '/../scripts/migrate.php';
    $secondRun = (string) ob_get_clean();
    $afterSecond = $snapshot();

    $failures = [];
    $assert = static function (bool $condition, string $message) use (&$failures): void {
        if (!$condition) {
            $failures[] = $message;
        }
    };

    $assert(str_contains($firstRun, 'Imported database/schema.sql'), 'a fresh run imports the schema');
    foreach (['seed.sql', 'seed_company_scenario.sql', 'seed_extended.sql'] as $seed) {
        $assert(str_contains($firstRun, "Loaded database/{$seed}"), "a fresh run loads {$seed}");
    }

    $migrationFiles = glob(__DIR__ . '/../database/migrations/*.sql') ?: [];
    foreach ($migrationFiles as $migration) {
        $name = basename($migration);
        $assert(str_contains($firstRun, "Applied {$name}"), "a fresh run applies {$name}");
        $assert(str_contains($secondRun, "Skipped {$name}"), "a second run skips {$name}");
    }

    $assert((int) $pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn() === count($migrationFiles), 'every migration is recorded');

    // The tables the depth migrations introduce must exist on a fresh install.
    foreach ([
        'customers', 'shipments', 'trip_stops', 'vehicle_documents', 'maintenance_parts',
        'stock_movements', 'purchase_request_lines', 'rate_cards', 'invoices', 'invoice_lines',
        'payments', 'permissions', 'role_permissions', 'company_settings', 'login_attempts', 'password_resets',
    ] as $table) {
        $assert(in_array($table, $tables, true), "a fresh install creates the {$table} table");
    }

    $assert((int) $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn() === 7, 'seven roles are created');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 7, 'seven users are created');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn() >= 20, 'the permission catalogue is populated');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM role_permissions')->fetchColumn() >= 60, 'the role matrix is populated');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM company_settings')->fetchColumn() >= 15, 'company settings are populated');
    $assert((int) $pdo->query('SELECT COUNT(*) FROM reports WHERE report_key IS NOT NULL')->fetchColumn() >= 8, 'the saved reports point at live queries');

    // Every table holds the same rows on a second run, which is what makes the
    // seeds safe to re-run. The audit trail is the exception: it is a log, so a
    // second run legitimately adds the entry recording that it happened.
    foreach ($afterFirst as $table => $count) {
        if ($table === 'audit_logs') {
            $assert(($afterSecond[$table] ?? 0) >= $count, 'the audit trail records the second run rather than being rewritten');
            continue;
        }

        $assert(($afterSecond[$table] ?? -1) === $count, "{$table} keeps {$count} rows on a second run");
    }

    // The ledger is posted as part of setting up, so the books are not empty the
    // first time anyone opens them.
    $assert(str_contains($firstRun, 'to the ledger') || str_contains($firstRun, 'already up to date'), 'a fresh run posts the seeded operations to the ledger');
    $assert((int) $pdo->query("SELECT COUNT(*) FROM gl_journal_entries WHERE deleted_at IS NULL")->fetchColumn() > 1, 'the ledger has entries after setup');

    $sides = $pdo->query(
        "SELECT COALESCE(SUM(l.debit), 0) d, COALESCE(SUM(l.credit), 0) c
           FROM gl_journal_lines l
           INNER JOIN gl_journal_entries e ON e.id = l.entry_id
          WHERE e.status = 'posted' AND e.deleted_at IS NULL"
    )->fetch();
    $assert(abs((float) $sides['d'] - (float) $sides['c']) < 0.004, 'the ledger balances after a fresh setup');

    if ($failures !== []) {
        foreach ($failures as $failure) {
            fwrite(STDERR, "FAIL: {$failure}\n");
        }
        exit(1);
    }

    printf("Fresh migration tests passed (%d tables verified).\n", count($tables));
} finally {
    $pdo = null;
    $cleanup = new PDO($rootDsn, $db['user'], $db['pass'], $options);
    $cleanup->exec("DROP DATABASE IF EXISTS `{$dbName}`");
}
