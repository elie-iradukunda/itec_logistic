<?php

$routes = [
    'home' => ['Controllers\\HomeController', 'index'],
    'login' => ['Controllers\\HomeController', 'login'],
    'logout' => ['Controllers\\HomeController', 'logout'],
    'dashboard' => ['Controllers\\DashboardController', 'index'],
    'vehicles' => ['Controllers\\LogisticsController', 'vehicles'],
    'trips' => ['Controllers\\LogisticsController', 'trips'],
    'drivers' => ['Controllers\\LogisticsController', 'drivers'],
    'maintenance' => ['Controllers\\LogisticsController', 'maintenance'],
    'requests' => ['Controllers\\LogisticsController', 'requests'],
    'fuel' => ['Controllers\\LogisticsController', 'fuel'],
    'expenses' => ['Controllers\\LogisticsController', 'expenses'],
    'warehouse' => ['Controllers\\LogisticsController', 'warehouse'],
    'reports' => ['Controllers\\LogisticsController', 'reports'],
    'deliveries' => ['Controllers\\LogisticsController', 'deliveries'],
    'procurement' => ['Controllers\\LogisticsController', 'procurement'],
    'users' => ['Controllers\\LogisticsController', 'users'],
];

$route = $_GET['route'] ?? 'home';
$route = trim((string) $route, '/');

if (!isset($routes[$route])) {
    http_response_code(404);
    view('layouts/error', ['title' => 'Page not found', 'message' => 'The requested logistics page does not exist.']);
    exit;
}

[$controller, $action] = $routes[$route];

if (!in_array($route, ['home', 'login', 'logout'], true) && !is_logged_in()) {
    header('Location: ?route=home&login_required=1#login');
    exit;
}

if (!in_array($route, ['home', 'login', 'logout'], true) && !role_can($route)) {
    header('Location: ?route=dashboard&denied=1');
    exit;
}

$instance = new $controller();
$instance->{$action}();
