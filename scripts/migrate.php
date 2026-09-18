<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];
$charset = $db['charset'] ?? 'utf8mb4';
$rootDsn = sprintf('mysql:host=%s;charset=%s', $db['host'], $charset);
$databaseDsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $charset);

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$root = new PDO($rootDsn, $db['user'], $db['pass'], $options);
$root->exec(sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    str_replace('`', '``', $db['name'])
));

$pdo = new PDO($databaseDsn, $db['user'], $db['pass'], $options);

$tableExists = static function (PDO $pdo, string $table): bool {
    $statement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = ?'
    );
    $statement->execute([$table]);

    return (int) $statement->fetchColumn() > 0;
};

$tableCount = static function (PDO $pdo): int {
    $statement = $pdo->query(
        'SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_type = "BASE TABLE"'
    );

    return (int) $statement->fetchColumn();
};

$prepareSql = static function (string $path, string $databaseName): string {
    $sql = (string) file_get_contents($path);
    $quotedName = '`' . str_replace('`', '``', $databaseName) . '`';

    return str_replace(
        ['CREATE DATABASE IF NOT EXISTS logistics_mvc', 'USE logistics_mvc;'],
        ['CREATE DATABASE IF NOT EXISTS ' . $quotedName, 'USE ' . $quotedName . ';'],
        $sql
    );
};

$hasSchema = $tableExists($pdo, 'roles') && $tableExists($pdo, 'users');
if (!$hasSchema) {
    if ($tableCount($pdo) > 0) {
        fwrite(STDERR, "Database contains partial tables but the base logistics schema is missing. Please review it before migrating.\n");
        exit(1);
    }

    $schemaPath = __DIR__ . '/../database/schema.sql';
    $root->exec($prepareSql($schemaPath, $db['name']));
    echo "Imported database/schema.sql\n";
}

$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(190) NOT NULL UNIQUE,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)');

$migrationDir = __DIR__ . '/../database/migrations';
$files = glob($migrationDir . '/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    $statement = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration = ?');
    $statement->execute([$name]);
    if ((int) $statement->fetchColumn() > 0) {
        echo "Skipped {$name}\n";
        continue;
    }

    $sql = trim((string) file_get_contents($file));
    if ($sql === '') {
        continue;
    }

    try {
        $pdo->exec($sql);
        $insert = $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
        $insert->execute([$name]);
        echo "Applied {$name}\n";
    } catch (Throwable $exception) {
        fwrite(STDERR, "Failed {$name}: {$exception->getMessage()}\n");
        exit(1);
    }
}

$seedPath = __DIR__ . '/../database/seed.sql';
try {
    $root->exec($prepareSql($seedPath, $db['name']));
    echo "Loaded database/seed.sql\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "Failed seed: {$exception->getMessage()}\n");
    exit(1);
}

echo "Database setup complete.\n";
