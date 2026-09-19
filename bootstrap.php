<?php

declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';

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
    static $accounts = null;

    if (is_array($accounts)) {
        return $accounts;
    }

    try {
        $repository = new \Models\UserRepository();
        $accounts = array_intersect_key($repository->activeLoginAccounts(), role_definitions());
    } catch (\Throwable) {
        $accounts = [];
    }

    return $accounts;
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

/** users.prvg: 1 = privileged (may switch into any role), 2 = standard (default). Stored at login and kept while switching roles. */
function current_prvg(): int
{
    return (int) ($_SESSION['logistics_prvg'] ?? 2) === 1 ? 1 : 2;
}

function can_switch_role(): bool
{
    return is_logged_in() && current_prvg() === 1;
}

function current_user_id(): ?int
{
    return isset($_SESSION['logistics_user_id']) ? (int) $_SESSION['logistics_user_id'] : null;
}

function current_notifications(int $limit = 6): array
{
    if (!is_logged_in()) {
        return [];
    }

    try {
        return (new \Models\Notification())->forCurrentUser(current_user_id(), current_role(), $limit);
    } catch (\Throwable) {
        return [];
    }
}

function unread_notification_count(): int
{
    if (!is_logged_in()) {
        return 0;
    }

    try {
        return (new \Models\Notification())->unreadCount(current_user_id(), current_role());
    } catch (\Throwable) {
        return 0;
    }
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

if (isset($_GET['role'], $roleDefinitions[$_GET['role']]) && can_switch_role()) {
    $_SESSION['logistics_role'] = $_GET['role'];
    $account = demo_accounts()[$_GET['role']] ?? null;
    if ($account !== null) {
        $_SESSION['logistics_user_id'] = $account['id'];
        $_SESSION['logistics_user_name'] = $account['name'];
        $_SESSION['logistics_user_email'] = $account['email'];
    }
}

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

function url(string|array $path = '', array $query = []): string
{
    $segments = array_map('rawurlencode', array_map('strval', (array) $path));
    $url = rtrim((string) config('app.base_url', ''), '/') . '/' . implode('/', $segments);

    return $query === [] ? $url : $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function time_ago(?string $datetime): string
{
    $time = $datetime ? strtotime($datetime) : false;
    if ($time === false) {
        return '';
    }

    $seconds = max(0, time() - $time);

    return match (true) {
        $seconds < 60 => 'Just now',
        $seconds < 3600 => intdiv($seconds, 60) . ' min ago',
        $seconds < 86400 => intdiv($seconds, 3600) . ' h ago',
        $seconds < 604800 => intdiv($seconds, 86400) . ' d ago',
        default => date('d M Y', $time),
    };
}

/** First URL segment of the current request without the app base path, e.g. "vehicles" for /itec_logistic/vehicles/create. */
function current_route(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = rtrim((string) config('app.base_url', ''), '/');
    if ($base !== '' && strncasecmp($path, $base, strlen($base)) === 0) {
        $path = substr($path, strlen($base));
    }

    return explode('/', trim($path, '/'))[0] ?? '';
}

/** " active" when the sidebar link points at the page being viewed (reports links also match their report_type filter). */
function nav_active(string $route, ?string $reportType = null): string
{
    if (current_route() !== $route) {
        return '';
    }
    if ($route === 'reports' && $reportType !== null && (string) ($_GET['report_type'] ?? '') !== $reportType) {
        return '';
    }

    return ' active';
}

/** True when the current page is one of the routes inside a sidebar group. */
function nav_group(array $routes): bool
{
    return in_array(current_route(), $routes, true);
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require __DIR__ . '/app/Views/' . $template . '.php';
}
