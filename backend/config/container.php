<?php

declare(strict_types=1);

/**
 * DI 容器定义文件
 *
 * 返回一个定义数组，由 bootstrap/app.php 注入到 Yiisoft\Di\Container。
 * 每条记录格式：接口/类名 => 具体实现或工厂闭包。
 *
 * 新增模块时，在此文件追加绑定即可，无需修改入口文件。
 */

use App\Controller\V1\AuthController;
use App\Controller\V1\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use Application\User\Service\UserService;
use Domain\User\Repository\UserRepositoryInterface;
use Infrastructure\Persistence\User\DbUserRepository;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mysql\Connection as MysqlConnection;
use Yiisoft\Db\Mysql\Driver as MysqlDriver;

return static function (array $params): array {
    return [

        // ── 数据库连接 ────────────────────────────────────────────
        ConnectionInterface::class => static function () use ($params): MysqlConnection {
            $driver      = new MysqlDriver(
                $params['db']['dsn'],
                $params['db']['username'],
                $params['db']['password'],
            );
            $schemaCache = new SchemaCache(
                new FileCache(dirname(__DIR__) . '/runtime/cache')
            );
            return new MysqlConnection($driver, $schemaCache);
        },

        // ── 仓储：接口 → 实现 ─────────────────────────────────────
        UserRepositoryInterface::class => DbUserRepository::class,

        // ── 应用服务 ──────────────────────────────────────────────
        UserService::class => UserService::class,

        // ── 中间件 ────────────────────────────────────────────────
        AuthMiddleware::class => static fn (): AuthMiddleware => new AuthMiddleware($params),
        CorsMiddleware::class => static fn (): CorsMiddleware => new CorsMiddleware($params),

        // ── 控制器 ────────────────────────────────────────────────
        AuthController::class => static function (UserService $service) use ($params): AuthController {
            return new AuthController($service, $params);
        },

        UserController::class => UserController::class,
    ];
};
