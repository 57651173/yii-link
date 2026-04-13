<?php

declare(strict_types=1);

return [
    /**
     * 旧 JWT 无 tid 时的回退租户主键（须与种子默认租户一致）。
     * 可通过环境变量 DEFAULT_TENANT_ID 覆盖；未设置时为 md5('sys_tenant_default')。
     */
    'default_tenant_id' => $_ENV['DEFAULT_TENANT_ID'] ?? md5('sys_tenant_default'),

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

    // Redis 配置
    'redis' => [
        'host'     => $_ENV['REDIS_HOST'] ?? 'redis',
        'port'     => (int)($_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => (int)($_ENV['REDIS_DB'] ?? 0),
        'timeout'  => (float)($_ENV['REDIS_TIMEOUT'] ?? 2.0),
    ],

    // 应用配置
    'app' => [
        'name'    => 'Yii-Link API',
        'version' => '1.0.0',
        'debug'   => (bool)($_ENV['APP_DEBUG'] ?? true),
        'env'     => $_ENV['APP_ENV'] ?? 'development',
    ],
];
