<?php

declare(strict_types=1);

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

    ob_start();
    require __DIR__ . '/../scripts/migrate.php';
    $secondRun = (string) ob_get_clean();

    $pdo = new PDO($dbDsn, $db['user'], $db['pass'], $options);
    $counts = $pdo->query(
        'SELECT
            (SELECT COUNT(*) FROM roles) AS roles_count,
            (SELECT COUNT(*) FROM users) AS users_count,
            (SELECT COUNT(*) FROM notifications) AS notifications_count,
            (SELECT COUNT(*) FROM reports) AS reports_count,
            (SELECT COUNT(*) FROM migrations) AS migrations_count'
    )->fetch();

    $failures = [];
    $assert = static function (bool $condition, string $message) use (&$failures): void {
        if (!$condition) {
            $failures[] = $message;
        }
    };

    $assert(str_contains($firstRun, 'Imported database/schema.sql'), 'fresh migration imports schema');
    $assert(str_contains($firstRun, 'Loaded database/seed.sql'), 'fresh migration loads seed');
    $assert(str_contains($secondRun, 'Skipped 20260918_000001_extend_logistics_schema.sql'), 'second migration run skips applied migrations');
    $assert((int) $counts['roles_count'] === 7, 'fresh migration creates seven roles');
    $assert((int) $counts['users_count'] === 7, 'fresh migration creates seven users');
    $assert((int) $counts['notifications_count'] === 7, 'fresh migration creates seven notifications');
    $assert((int) $counts['reports_count'] === 9, 'fresh migration creates reports');
    $assert((int) $counts['migrations_count'] === 2, 'fresh migration records migrations');

    if ($failures !== []) {
        foreach ($failures as $failure) {
            fwrite(STDERR, "FAIL: {$failure}\n");
        }
        exit(1);
    }

    echo "Fresh migration tests passed.\n";
} finally {
    $pdo = null;
    $cleanup = new PDO($rootDsn, $db['user'], $db['pass'], $options);
    $cleanup->exec("DROP DATABASE IF EXISTS `{$dbName}`");
}
