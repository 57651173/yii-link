<?php

declare(strict_types=1);

namespace Application\Rbac\Service;

use App\Service\CacheService;
use Application\Tenant\TenantContext;
use Domain\Rbac\Repository\RbacRepositoryInterface;

/**
 * RBAC 应用服务（权限检查、角色查询）
 * 
 * 集成缓存：用户权限缓存 5 分钟，显著提升性能
 */
class RbacService
{
    private const CACHE_TTL = 300; // 5 分钟
    
    public function __construct(
        private readonly RbacRepositoryInterface $rbacRepository,
        private readonly TenantContext $tenantContext,
        private readonly CacheService $cacheService,
    ) {
    }

    /**
     * 检查当前租户下的用户是否有指定权限
     */
    public function userHasPermission(string $userAccount, string $permissionSlug): bool
    {
        $permissions = $this->getUserPermissionsWithCache($userAccount);
        
        return in_array($permissionSlug, $permissions, true);
    }

    /**
     * 检查当前租户下的用户是否有指定角色
     */
    public function userHasRole(string $userAccount, string $roleSlug): bool
    {
        $tenantId = $this->tenantContext->requireTenantId();

        return $this->rbacRepository->hasRole($tenantId, $userAccount, $roleSlug);
    }

    /**
     * 获取用户所有权限 slug（带缓存）
     *
     * @return string[]
     */
    public function getUserPermissions(string $userAccount): array
    {
        return $this->getUserPermissionsWithCache($userAccount);
    }
    
    /**
     * 获取用户权限（带缓存）
     * 
     * @return string[]
     */
    private function getUserPermissionsWithCache(string $userAccount): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $cacheKey = $this->getPermissionCacheKey($tenantId, $userAccount);
        
        return $this->cacheService->remember(
            $cacheKey,
            fn() => $this->rbacRepository->getUserPermissions($tenantId, $userAccount),
            self::CACHE_TTL
        );
    }

    /**
     * 获取用户所有角色 ID
     *
     * @return int[]
     */
    public function getUserRoleIds(string $userAccount): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        return $this->rbacRepository->getUserRoleIds($tenantId, $userAccount);
    }

    /**
     * 检查用户是否有任一指定权限
     *
     * @param string[] $permissionSlugs
     */
    public function userHasAnyPermission(string $userAccount, array $permissionSlugs): bool
    {
        $userPermissions = $this->getUserPermissionsWithCache($userAccount);
        
        foreach ($permissionSlugs as $slug) {
            if (in_array($slug, $userPermissions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查用户是否有全部指定权限
     *
     * @param string[] $permissionSlugs
     */
    public function userHasAllPermissions(string $userAccount, array $permissionSlugs): bool
    {
        $userPermissions = $this->getUserPermissionsWithCache($userAccount);
        
        foreach ($permissionSlugs as $slug) {
            if (!in_array($slug, $userPermissions, true)) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * 清除用户权限缓存（角色变更时调用）
     */
    public function clearUserPermissionCache(string $userAccount): void
    {
        $tenantId = $this->tenantContext->requireTenantId();
        $cacheKey = $this->getPermissionCacheKey($tenantId, $userAccount);
        
        $this->cacheService->delete($cacheKey);
    }
    
    /**
     * 清除租户所有用户的权限缓存
     */
    public function clearTenantPermissionCache(): void
    {
        $tenantId = $this->tenantContext->requireTenantId();
        
        // 清除该租户下所有用户的权限缓存
        $this->cacheService->clear("user_permissions:{$tenantId}:*");
    }
    
    /**
     * 生成权限缓存键
     */
    private function getPermissionCacheKey(string $tenantId, string $userAccount): string
    {
        return "user_permissions:{$tenantId}:{$userAccount}";
    }
}
