<?php

declare(strict_types=1);

namespace Application\Rbac\Service;

use App\Exception\BusinessException;
use Application\Tenant\TenantContext;
use Domain\Rbac\Entity\Permission;
use Domain\Rbac\Entity\Role;
use Domain\Rbac\Repository\RbacRepositoryInterface;

/**
 * 角色管理服务
 */
class RoleService
{
    public function __construct(
        private readonly RbacRepositoryInterface $rbacRepository,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * 创建角色
     */
    public function create(array $data): Role
    {
        $tenantId = $this->tenantContext->requireTenantId();
        
        $slug = trim($data['slug'] ?? '');
        $name = trim($data['name'] ?? '');

        if (empty($slug) || empty($name)) {
            throw new BusinessException('角色标识和名称不能为空', 422);
        }

        if ($this->rbacRepository->roleSlugExists($tenantId, $slug)) {
            throw new BusinessException("角色标识 {$slug} 已存在", 409);
        }

        $role = new Role(
            id: null,
            tenantId: $tenantId,
            name: $name,
            slug: $slug,
            description: $data['description'] ?? null,
        );

        return $this->rbacRepository->saveRole($role);
    }

    /**
     * 更新角色
     */
    public function update(int $id, array $data): Role
    {
        $role = $this->rbacRepository->findRoleById($id);
        if ($role === null) {
            throw new BusinessException('角色不存在', 404);
        }

        $tenantId = $this->tenantContext->requireTenantId();
        if ($role->getTenantId() !== $tenantId) {
            throw new BusinessException('无权限操作此角色', 403);
        }

        $slug = trim($data['slug'] ?? $role->getSlug());
        if ($slug !== $role->getSlug() && $this->rbacRepository->roleSlugExists($tenantId, $slug, $id)) {
            throw new BusinessException("角色标识 {$slug} 已被其他角色使用", 409);
        }

        $updated = new Role(
            id: $id,
            tenantId: $tenantId,
            name: trim($data['name'] ?? $role->getName()),
            slug: $slug,
            description: $data['description'] ?? $role->getDescription(),
            createdAt: $role->getCreatedAt(),
            updatedAt: new \DateTimeImmutable(),
        );

        return $this->rbacRepository->saveRole($updated);
    }

    /**
     * 获取角色详情
     */
    public function getById(int $id): Role
    {
        $role = $this->rbacRepository->findRoleById($id);
        if ($role === null) {
            throw new BusinessException('角色不存在', 404);
        }

        $tenantId = $this->tenantContext->requireTenantId();
        if ($role->getTenantId() !== $tenantId) {
            throw new BusinessException('无权限查看此角色', 403);
        }

        return $role;
    }

    /**
     * 获取当前租户的角色列表
     */
    public function getList(int $page = 1, int $pageSize = 20): array
    {
        $tenantId = $this->tenantContext->requireTenantId();

        return $this->rbacRepository->findRolesByTenant($tenantId, $page, $pageSize);
    }

    /**
     * 删除角色
     */
    public function delete(int $id): bool
    {
        $role = $this->rbacRepository->findRoleById($id);
        if ($role === null) {
            throw new BusinessException('角色不存在', 404);
        }

        $tenantId = $this->tenantContext->requireTenantId();
        if ($role->getTenantId() !== $tenantId) {
            throw new BusinessException('无权限删除此角色', 403);
        }

        return $this->rbacRepository->deleteRole($id);
    }

    /**
     * 为角色分配权限
     *
     * @param int[] $permissionIds
     */
    public function assignPermissions(int $roleId, array $permissionIds): void
    {
        $role = $this->rbacRepository->findRoleById($roleId);
        if ($role === null) {
            throw new BusinessException('角色不存在', 404);
        }

        $tenantId = $this->tenantContext->requireTenantId();
        if ($role->getTenantId() !== $tenantId) {
            throw new BusinessException('无权限操作此角色', 403);
        }

        // 先移除旧权限，再分配新权限
        $this->rbacRepository->removeAllPermissionsFromRole($roleId);
        
        if (!empty($permissionIds)) {
            $this->rbacRepository->assignPermissionsToRole($roleId, $permissionIds);
        }
    }

    /**
     * 获取角色的权限列表
     *
     * @return string[]
     */
    public function getRolePermissions(int $roleId): array
    {
        $role = $this->rbacRepository->findRoleById($roleId);
        if ($role === null) {
            throw new BusinessException('角色不存在', 404);
        }

        return $this->rbacRepository->getRolePermissions($roleId);
    }
}
