<?php

declare(strict_types=1);

/**
 * 路由注册文件
 *
 * 返回一个闭包，接收 RouteCollector 并向其中注册所有路由。
 * 新增模块时，在此文件追加路由即可，无需修改入口文件。
 *
 * 路由规则：
 *  - 公开接口：直接 ->action(...)
 *  - 需要认证：先 ->middleware(AuthMiddleware::class)
 *  - 需要权限：再 ->middleware(PermissionMiddleware::class)
 *    权限通过路由名称自动映射（例如：user.index -> user.list）
 */

use App\Controller\V1\AuthController;
use App\Controller\V1\PermissionController;
use App\Controller\V1\RoleController;
use App\Controller\V1\TenantController;
use App\Controller\V1\UserController;
use App\Controller\V1\UserRoleController;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollector;

return static function (RouteCollector $collector): void {

    // ═══════════════════════════════════════════════════════════════════
    // 健康检查（公开接口，无需登录）
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        // 完整健康检查
        Route::get('/health')
            ->action([HealthController::class, 'index'])
            ->name('health.index'),
        
        // 简化健康检查
        Route::get('/health/simple')
            ->action([HealthController::class, 'simple'])
            ->name('health.simple'),
    );

    // ═══════════════════════════════════════════════════════════════════
    // 公开接口（无需登录）
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        Route::post('/api/v1/login')
            ->action([AuthController::class, 'login'])
            ->name('auth.login'),
        
        // Token 刷新（公开接口，需要旧 Token）
        Route::post('/api/v1/refresh')
            ->action([AuthController::class, 'refresh'])
            ->name('auth.refresh'),
    );

    // ═══════════════════════════════════════════════════════════════════
    // 认证接口（需要 JWT，无需特定权限）
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        Route::get('/api/v1/me')
            ->middleware(AuthMiddleware::class)
            ->action([AuthController::class, 'me'])
            ->name('auth.me'),
    );

    // ═══════════════════════════════════════════════════════════════════
    // 租户管理（仅平台管理员 - 不使用 PermissionMiddleware）
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        // 租户列表
        Route::get('/api/v1/tenants')
            ->middleware(AuthMiddleware::class)
            ->action([TenantController::class, 'index'])
            ->name('tenant.index'),

        // 租户详情
        Route::get('/api/v1/tenants/{id}')
            ->middleware(AuthMiddleware::class)
            ->action([TenantController::class, 'view'])
            ->name('tenant.view'),

        // 创建租户
        Route::post('/api/v1/tenants')
            ->middleware(AuthMiddleware::class)
            ->action([TenantController::class, 'create'])
            ->name('tenant.create'),

        // 更新租户
        Route::put('/api/v1/tenants/{id}')
            ->middleware(AuthMiddleware::class)
            ->action([TenantController::class, 'update'])
            ->name('tenant.update'),

        // 删除租户
        Route::delete('/api/v1/tenants/{id}')
            ->middleware(AuthMiddleware::class)
            ->action([TenantController::class, 'delete'])
            ->name('tenant.delete'),

        // 禁用租户
        Route::patch('/api/v1/tenants/{id}/disable')
            ->middleware(AuthMiddleware::class)
            ->action([TenantController::class, 'disable'])
            ->name('tenant.disable'),

        // 启用租户
        Route::patch('/api/v1/tenants/{id}/enable')
            ->middleware(AuthMiddleware::class)
            ->action([TenantController::class, 'enable'])
            ->name('tenant.enable'),
    );

    // ═══════════════════════════════════════════════════════════════════
    // 用户管理（租户内 - 需要权限）- 使用 account 作为标识符
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        // 用户列表
        Route::get('/api/v1/users')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserController::class, 'index'])
            ->name('user.index'),  // -> user.list

        // 用户详情
        Route::get('/api/v1/users/{account}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserController::class, 'show'])
            ->name('user.show'),  // -> user.view

        // 创建用户
        Route::post('/api/v1/users')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserController::class, 'create'])
            ->name('user.create'),  // -> user.create

        // 更新用户
        Route::put('/api/v1/users/{account}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserController::class, 'update'])
            ->name('user.update'),  // -> user.update

        // 删除用户
        Route::delete('/api/v1/users/{account}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserController::class, 'delete'])
            ->name('user.delete'),  // -> user.delete

        // 禁用用户
        Route::patch('/api/v1/users/{account}/disable')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserController::class, 'disable'])
            ->name('user.disable'),  // -> user.toggle_status

        // 启用用户
        Route::patch('/api/v1/users/{account}/enable')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserController::class, 'enable'])
            ->name('user.enable'),  // -> user.toggle_status

        // 重置密码
        Route::post('/api/v1/users/{account}/reset-password')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserController::class, 'resetPassword'])
            ->name('user.resetPassword'),  // -> user.reset_password
    );

    // ═══════════════════════════════════════════════════════════════════
    // 角色管理（租户内 - 需要权限）
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        // 角色列表
        Route::get('/api/v1/roles')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([RoleController::class, 'index'])
            ->name('role.index'),  // -> role.list

        // 角色详情
        Route::get('/api/v1/roles/{id:\d+}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([RoleController::class, 'view'])
            ->name('role.view'),  // -> role.view

        // 创建角色
        Route::post('/api/v1/roles')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([RoleController::class, 'create'])
            ->name('role.create'),  // -> role.create

        // 更新角色
        Route::put('/api/v1/roles/{id:\d+}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([RoleController::class, 'update'])
            ->name('role.update'),  // -> role.update

        // 删除角色
        Route::delete('/api/v1/roles/{id:\d+}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([RoleController::class, 'delete'])
            ->name('role.delete'),  // -> role.delete

        // 获取角色权限
        Route::get('/api/v1/roles/{id:\d+}/permissions')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([RoleController::class, 'permissions'])
            ->name('role.permissions'),  // -> role.view_permissions

        // 分配权限给角色
        Route::put('/api/v1/roles/{id:\d+}/permissions')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([RoleController::class, 'assignPermissions'])
            ->name('role.assignPermissions'),  // -> role.assign_permissions
    );

    // ═══════════════════════════════════════════════════════════════════
    // 权限管理（全局 - 只读）
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        // 权限列表
        Route::get('/api/v1/permissions')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([PermissionController::class, 'index'])
            ->name('permission.index'),  // -> permission.list

        // 权限树
        Route::get('/api/v1/permissions/tree')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([PermissionController::class, 'tree'])
            ->name('permission.tree'),  // -> permission.tree

        // 权限详情
        Route::get('/api/v1/permissions/{id:\d+}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([PermissionController::class, 'view'])
            ->name('permission.view'),  // -> permission.view
    );

    // ═══════════════════════════════════════════════════════════════════
    // 用户角色分配（租户内 - 需要权限）
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        // 获取用户角色
        Route::get('/api/v1/users/{account}/roles')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserRoleController::class, 'index'])
            ->name('user_role.index'),  // -> user_role.list

        // 分配角色给用户
        Route::put('/api/v1/users/{account}/roles')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserRoleController::class, 'assign'])
            ->name('user_role.assign'),  // -> user_role.assign

        // 移除用户角色
        Route::delete('/api/v1/users/{account}/roles/{roleId:\d+}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([UserRoleController::class, 'remove'])
            ->name('user_role.remove'),  // -> user_role.remove
    );
    // ═══════════════════════════════════════════════════════════════════
    // 审计日志管理（租户内 - 需要权限）
    // ═══════════════════════════════════════════════════════════════════
    $collector->addRoute(
        // 审计日志列表
        Route::get('/api/v1/audit-logs')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([AuditLogController::class, 'index'])
            ->name('audit_log.index'),  // -> audit_log.list

        // 审计日志详情
        Route::get('/api/v1/audit-logs/{id:\d+}')
            ->middleware(AuthMiddleware::class, PermissionMiddleware::class)
            ->action([AuditLogController::class, 'show'])
            ->name('audit_log.show'),  // -> audit_log.view
    );
};
