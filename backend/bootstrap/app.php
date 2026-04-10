<?php

declare(strict_types=1);

/**
 * 应用引导文件
 *
 * 负责：
 *  1. 加载 .env 环境变量
 *  2. 加载 config/params.php 配置参数
 *  3. 构建 DI 容器（定义来自 config/container.php）
 *  4. 构建路由集合（路由来自 config/routes.php）
 *
 * 返回值供 public/index.php 直接使用。
 */

use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;

$rootDir = dirname(__DIR__);

// ── 1. 加载 .env ──────────────────────────────────────────────────
$envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// ── 2. 加载配置参数 ────────────────────────────────────────────────
$params = require $rootDir . '/config/params.php';

// ── 3. 构建 DI 容器 ───────────────────────────────────────────────
$definitions = (require $rootDir . '/config/container.php')($params);
$container   = new Container(ContainerConfig::create()->withDefinitions($definitions));

// ── 4. 构建路由集合 ───────────────────────────────────────────────
$collector = new RouteCollector();
(require $rootDir . '/config/routes.php')($collector);
$routeCollection = new RouteCollection($collector);

return compact('container', 'routeCollection');
