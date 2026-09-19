<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'LMS',
        'base_url' => '/itec_logistic',
    ],
    'db' => [
        'host' => getenv('LOGISTICS_DB_HOST') ?: '127.0.0.1',
        'name' => getenv('LOGISTICS_DB_NAME') ?: 'logistics_mvc',
        'user' => getenv('LOGISTICS_DB_USER') ?: 'root',
        'pass' => getenv('LOGISTICS_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
];
