<?php

declare(strict_types=1);

namespace Application\Rbac\Service;

use App\Exception\BusinessException;
use Application\Tenant\TenantContext;
use Domain\Rbac\Repository\RbacRepositoryInterface;

/**
 * 用户角色分配服务
 */
class UserRoleService
{
    public function __construct(
        private readonly RbacRepositoryInterface $rbacRepository,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * 为用户分配角色
     *
     * @param int[] $roleIds
     */
    public function assignRoles(string $userAccount, array $roleIds): void
    {
        $tenantId = $this->tenantContext->requireTenantId();

        // 先移除用户的所有角色
        $this->rbacRepository->removeAllRolesFromUser($tenantId, $userAccount);

        // 再分配新角色
        foreach ($roleIds as $roleId) {
            $role = $this->rbacRepository->findRoleById((int)$roleId);
            if ($role === null) {
                throw new BusinessException("角色 ID {$roleId} 不存在", 404);
            }

            if ($role->getTenantId() !== $tenantId) {
                throw new BusinessException("无权限分配角色 ID {$roleId}", 403);
            }

            $this->rbacRepository->assignRoleToUser($tenantId, $userAccount, (int)$roleId);
        }
    }

    /**
     * 移除用户的指定角色
     */
    public function removeRole(string $userAccount, int $roleId): void
    {
        $tenantId = $this->tenantContext->requireTenantId();
        
        $role = $this->rbacRepository->findRoleById($roleId);
        if ($role !== null && $role->getTenantId() !== $tenantId) {
            throw new BusinessException('无权限操作此角色', 403);
        }

        $this->rbacRepository->removeRoleFromUser($tenantId, $userAccount, $roleId);
    }

    /**
     * 获取用户的所有角色 ID
     *
     * @return int[]
     */
    public function getUserRoleIds(string $userAccount): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        return $this->rbacRepository->getUserRoleIds($tenantId, $userAccount);
    }
}
