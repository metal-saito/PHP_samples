<?php

declare(strict_types=1);

return [
    'driver' => getenv('DB_DRIVER') ?: 'sqlite',
    'sqlite' => [
        'path' => __DIR__ . '/../database.sqlite',
    ],
    'mysql' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_DATABASE') ?: 'tasks',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
];
