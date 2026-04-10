<?php

declare(strict_types=1);

return [
    // JWT 配置
    'jwt' => [
        'secret_key'  => $_ENV['JWT_SECRET'] ?? 'yii-link-secret-key-change-in-production',
        'expire_time' => (int)($_ENV['JWT_EXPIRE'] ?? 86400), // 默认 24 小时
        'algorithm'   => 'HS256',
    ],

    // 数据库配置
    'db' => [
        'dsn'      => $_ENV['DB_DSN'] ?? 'mysql:host=127.0.0.1;dbname=yii_link;charset=utf8mb4',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? '',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],

    // 日志配置
    'log' => [
        'level' => $_ENV['LOG_LEVEL'] ?? 'debug',
        'path'  => __DIR__ . '/../runtime/logs/app.log',
    ],

    // 应用配置
    'app' => [
        'name'    => 'Yii-Link API',
        'version' => '1.0.0',
        'debug'   => (bool)($_ENV['APP_DEBUG'] ?? true),
        'env'     => $_ENV['APP_ENV'] ?? 'development',
    ],
];
