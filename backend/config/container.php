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

use App\Controller\HealthController;
use App\Controller\V1\AuditLogController;
use App\Controller\V1\AuthController;
use App\Controller\V1\PermissionController;
use App\Controller\V1\RoleController;
use App\Controller\V1\TenantController;
use App\Controller\V1\UserController;
use App\Controller\V1\UserRoleController;
use App\Middleware\AuditLogMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Service\CacheService;
use App\Service\QuotaService;
use App\Service\RateLimiter;
use Application\AuditLog\Service\AuditLogService;
use Application\Rbac\Service\PermissionService;
use Application\Rbac\Service\RbacService;
use Application\Rbac\Service\RoleService;
use Application\Rbac\Service\UserRoleService;
use Application\Tenant\Service\TenantService;
use Application\Tenant\TenantContext;
use Application\Tenant\TenantResolver;
use Application\User\Service\UserService;
use Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use Domain\Rbac\Repository\RbacRepositoryInterface;
use Domain\Tenant\Repository\TenantRepositoryInterface;
use Domain\User\Repository\UserRepositoryInterface;
use Infrastructure\Persistence\AuditLog\DbAuditLogRepository;
use Infrastructure\Persistence\Rbac\DbRbacRepository;
use Infrastructure\Persistence\Tenant\DbTenantRepository;
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

        // ── 租户上下文（单次请求内单例）────────────────────────────
        TenantContext::class => TenantContext::class,
        TenantResolver::class => TenantResolver::class,

        // ── 仓储：接口 → 实现 ─────────────────────────────────────
        UserRepositoryInterface::class => DbUserRepository::class,
        TenantRepositoryInterface::class => DbTenantRepository::class,
        RbacRepositoryInterface::class => DbRbacRepository::class,
        AuditLogRepositoryInterface::class => DbAuditLogRepository::class,

        // ── 应用服务 ──────────────────────────────────────────────
        UserService::class => UserService::class,
        TenantService::class => TenantService::class,
        RbacService::class => RbacService::class,
        RoleService::class => RoleService::class,
        PermissionService::class => PermissionService::class,
        UserRoleService::class => UserRoleService::class,
        AuditLogService::class => AuditLogService::class,

        // ── 中间件 ────────────────────────────────────────────────
        AuthMiddleware::class => static function (TenantContext $tenantContext) use ($params): AuthMiddleware {
            return new AuthMiddleware($params, $tenantContext);
        },
        CorsMiddleware::class => static fn (): CorsMiddleware => new CorsMiddleware($params),
        PermissionMiddleware::class => PermissionMiddleware::class,
        AuditLogMiddleware::class => AuditLogMiddleware::class,
        ErrorHandlerMiddleware::class => static fn (): ErrorHandlerMiddleware => new ErrorHandlerMiddleware($params),
        RateLimitMiddleware::class => static function () use ($params): RateLimitMiddleware {
            $rateLimiter = new RateLimiter($params['redis'] ?? []);
            return new RateLimitMiddleware($rateLimiter);
        },
        
        // ───────────────────────────────────────────────────────────────
        // Services - 业务服务
        // ───────────────────────────────────────────────────────────────
        RateLimiter::class => static function () use ($params): RateLimiter {
            return new RateLimiter($params['redis'] ?? []);
        },
        CacheService::class => static function () use ($params): CacheService {
            return new CacheService($params['redis'] ?? []);
        },
        QuotaService::class => QuotaService::class,

        // ── 控制器 ────────────────────────────────────────────────
        AuthController::class => static function (UserService $service, TenantResolver $tenantResolver, AuditLogService $auditLogService) use ($params): AuthController {
            return new AuthController($service, $tenantResolver, $auditLogService, $params);
        },

        UserController::class => UserController::class,
        TenantController::class => TenantController::class,
        RoleController::class => RoleController::class,
        PermissionController::class => PermissionController::class,
        UserRoleController::class => UserRoleController::class,
        AuditLogController::class => AuditLogController::class,
        HealthController::class => static function (ConnectionInterface $db) use ($params): HealthController {
            return new HealthController($db, $params);
        },
    ];
};
