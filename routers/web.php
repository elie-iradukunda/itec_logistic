<?php

/** @var Core\Router $router */

use Controllers\DashboardController;
use Controllers\HomeController;
use Controllers\LogisticsController;

// Public pages
$router->get('/', [HomeController::class, 'index'], ['public' => true]);
$router->add(['GET', 'POST'], '/login', [HomeController::class, 'login'], ['public' => true]);
$router->get('/logout', [HomeController::class, 'logout'], ['public' => true]);

// Authenticated pages; access is checked against the role definitions in bootstrap.php,
// using the first path segment (e.g. "vehicles") as the permission key.
$router->get('/dashboard', [DashboardController::class, 'index']);

// Report exports must be registered before the generic /reports/{id} routes.
$router->get('/reports/export', [LogisticsController::class, 'export'], ['defaults' => ['module' => 'reports']]);
$router->get('/reports/export/{id}', [LogisticsController::class, 'export'], ['defaults' => ['module' => 'reports']]);

foreach (['vehicles', 'trips', 'drivers', 'maintenance', 'requests', 'fuel', 'expenses', 'warehouse', 'reports', 'deliveries', 'procurement', 'users'] as $module) {
    $defaults = ['defaults' => ['module' => $module]];

    $router->get("/{$module}", [LogisticsController::class, 'index'], $defaults);
    $router->get("/{$module}/create", [LogisticsController::class, 'create'], $defaults);
    $router->post("/{$module}/create", [LogisticsController::class, 'create'], $defaults);
    $router->get("/{$module}/{id}", [LogisticsController::class, 'details'], $defaults);
    $router->get("/{$module}/{id}/edit", [LogisticsController::class, 'edit'], $defaults);
    $router->post("/{$module}/{id}/edit", [LogisticsController::class, 'edit'], $defaults);
    $router->post("/{$module}/{id}/toggle", [LogisticsController::class, 'toggle'], $defaults);
    $router->post("/{$module}/{id}/delete", [LogisticsController::class, 'delete'], $defaults);
}
