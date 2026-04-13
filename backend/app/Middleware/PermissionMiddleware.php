<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Response\ApiResponse;
use Application\Rbac\Service\RbacService;
use Application\Tenant\TenantContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 权限检查中间件
 *
 * 验证当前用户是否拥有路由配置的权限。
 *
 * 权限来源（按优先级）：
 * 1. 请求属性 `permission` 或 `permissions`（手动注入）
 * 2. 路由名称自动映射（例如：user.index -> user.list）
 *
 * 使用方式：
 *   Route::get('/api/v1/users')
 *       ->middleware([AuthMiddleware::class, PermissionMiddleware::class])
 *       ->name('user.index')  // 自动映射到 user.list 权限
 */
class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RbacService $rbacService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1. 获取当前用户账号（由 AuthMiddleware 注入）
        $userAccount = $request->getAttribute('auth_user_account');
        if ($userAccount === null || $userAccount === '') {
            return ApiResponse::forbidden('未找到用户账号，请先通过 AuthMiddleware');
        }

        // 2. 检查租户上下文
        $tenantId = $this->tenantContext->getTenantId();
        if ($tenantId === null || $tenantId === '') {
            return ApiResponse::forbidden('租户上下文未设置');
        }

        // 3. 尝试从请求属性获取权限配置（优先）
        $requiredPermission = $request->getAttribute('permission'); // 单个权限
        $requiredPermissions = $request->getAttribute('permissions'); // 多个权限（任一即可）

        // 4. 如果请求属性没有，尝试从路由名称推断
        if ($requiredPermission === null && $requiredPermissions === null) {
            $routeName = $request->getAttribute('_route');
            if ($routeName !== null) {
                $requiredPermission = $this->getPermissionFromRouteName($routeName);
            }
        }

        // 如果仍然没有权限配置，直接放行
        if ($requiredPermission === null && $requiredPermissions === null) {
            return $handler->handle($request);
        }

        // 5. 检查单个权限
        if ($requiredPermission !== null) {
            if (!$this->rbacService->userHasPermission($userAccount, $requiredPermission)) {
                return ApiResponse::forbidden("缺少权限：{$requiredPermission}");
            }
        }

        // 6. 检查多个权限（任一即可）
        if ($requiredPermissions !== null && is_array($requiredPermissions)) {
            if (!$this->rbacService->userHasAnyPermission($userAccount, $requiredPermissions)) {
                return ApiResponse::forbidden('缺少必要权限：' . implode(', ', $requiredPermissions));
            }
        }

        return $handler->handle($request);
    }

    /**
     * 从路由名称推断权限
     * 例如：user.index -> user.list, role.create -> role.create
     */
    private function getPermissionFromRouteName(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        // 路由名称映射表
        $map = [
            // 用户管理
            'user.index' => 'user.list',
            'user.show' => 'user.view',
            'user.create' => 'user.create',
            'user.update' => 'user.update',
            'user.delete' => 'user.delete',
            'user.disable' => 'user.toggle_status',
            'user.enable' => 'user.toggle_status',
            'user.resetPassword' => 'user.reset_password',
            
            // 角色管理
            'role.index' => 'role.list',
            'role.view' => 'role.view',
            'role.create' => 'role.create',
            'role.update' => 'role.update',
            'role.delete' => 'role.delete',
            'role.permissions' => 'role.view_permissions',
            'role.assignPermissions' => 'role.assign_permissions',
            
            // 权限管理
            'permission.index' => 'permission.list',
            'permission.tree' => 'permission.tree',
            'permission.view' => 'permission.view',
            
            // 用户角色
            'user_role.index' => 'user_role.list',
            'user_role.assign' => 'user_role.assign',
            'user_role.remove' => 'user_role.revoke',
            
            // 审计日志管理
            'audit_log.index' => 'audit_log.list',
            'audit_log.show' => 'audit_log.view',
        ];

        return $map[$routeName] ?? null;
    }
}
