<?php

/** @var Core\Router $router */

use Controllers\ApiController;

// Reached as /api/<endpoint>; the "/api" prefix is stripped before matching.
// Responses are JSON. Unauthenticated calls get 401, role-restricted ones get 403.
$router->get('/health', [ApiController::class, 'health'], ['public' => true]);
$router->get('/me', [ApiController::class, 'me']);
