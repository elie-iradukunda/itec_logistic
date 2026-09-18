<?php

declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';
$route = trim((string) ($_GET['route'] ?? 'dashboard'), '/');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$roleDefinitions = [
    'super_admin' => ['label' => 'Super Admin', 'routes' => '*'],
    'logistics_manager' => ['label' => 'Logistics Manager', 'routes' => ['dashboard', 'vehicles', 'trips', 'deliveries', 'requests', 'drivers', 'maintenance', 'fuel', 'expenses', 'warehouse', 'procurement', 'reports']],
    'fleet_manager' => ['label' => 'Fleet Manager', 'routes' => ['dashboard', 'vehicles', 'drivers', 'maintenance', 'fuel', 'reports']],
    'warehouse_manager' => ['label' => 'Warehouse Manager', 'routes' => ['dashboard', 'warehouse', 'procurement', 'requests', 'reports']],
    'driver' => ['label' => 'Driver', 'routes' => ['dashboard', 'trips', 'deliveries']],
    'finance' => ['label' => 'Finance', 'routes' => ['dashboard', 'fuel', 'expenses', 'procurement', 'reports']],
    'management' => ['label' => 'Management', 'routes' => ['dashboard', 'reports']],
];

$demoAccounts = [
    'super_admin' => ['name' => 'Admin User', 'email' => 'admin@itec.rw'],
    'logistics_manager' => ['name' => 'Aline Mukamana', 'email' => 'aline@itec.rw'],
    'fleet_manager' => ['name' => 'Eric Murenzi', 'email' => 'eric@itec.rw'],
    'warehouse_manager' => ['name' => 'Nadine Tuyisenge', 'email' => 'nadine@itec.rw'],
    'driver' => ['name' => 'Samuel Niyonzima', 'email' => 'samuel@itec.rw'],
    'finance' => ['name' => 'Emmanuel Safari', 'email' => 'emmanuel@itec.rw'],
    'management' => ['name' => 'Jean Pierre Habimana', 'email' => 'jeanpierre@itec.rw'],
];

if (isset($_GET['role'], $roleDefinitions[$_GET['role']]) && is_logged_in()) {
    $_SESSION['logistics_role'] = $_GET['role'];
    $account = $demoAccounts[$_GET['role']] ?? null;
    if ($account !== null) {
        $_SESSION['logistics_user_name'] = $account['name'];
        $_SESSION['logistics_user_email'] = $account['email'];
    }
}

function is_logged_in(): bool
{
    return (bool) ($_SESSION['logistics_authenticated'] ?? false);
}

function current_role(): string
{
    return $_SESSION['logistics_role'] ?? 'logistics_manager';
}

function role_label(): string
{
    global $roleDefinitions;
    return $roleDefinitions[current_role()]['label'] ?? 'Logistics Manager';
}

function role_can(string $route): bool
{
    global $roleDefinitions;
    $routes = $roleDefinitions[current_role()]['routes'] ?? [];
    return $routes === '*' || in_array($route, $routes, true);
}

function role_definitions(): array
{
    global $roleDefinitions;
    return $roleDefinitions;
}

function demo_accounts(): array
{
    global $demoAccounts;
    return $demoAccounts;
}

function current_user_name(): string
{
    $accounts = demo_accounts();
    $role = current_role();
    return $_SESSION['logistics_user_name'] ?? ($accounts[$role]['name'] ?? role_label());
}

function current_user_email(): string
{
    $accounts = demo_accounts();
    $role = current_role();
    return $_SESSION['logistics_user_email'] ?? ($accounts[$role]['email'] ?? '');
}

if (PHP_SAPI === 'cli-server') {
    $config['app']['base_url'] = '';
}

spl_autoload_register(static function (string $class): void {
    $path = __DIR__ . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

function config(string $key, mixed $default = null): mixed
{
    global $config;
    $value = $config;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/app/Views/' . $template . '.php';
}
