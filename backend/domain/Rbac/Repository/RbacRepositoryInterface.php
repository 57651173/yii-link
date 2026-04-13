<?php

declare(strict_types=1);

namespace Domain\Rbac\Repository;

use Domain\Rbac\Entity\Permission;
use Domain\Rbac\Entity\Role;

/**
 * RBAC 仓储接口（查询用户角色、权限）
 */
interface RbacRepositoryInterface
{
    /**
     * 获取用户在当前租户下的所有角色 ID
     *
     * @return int[]
     */
    public function getUserRoleIds(string $tenantId, string $userAccount): array;

    /**
     * 获取用户在当前租户下的所有权限 slug
     *
     * @return string[]
     */
    public function getUserPermissions(string $tenantId, string $userAccount): array;

    /**
     * 检查用户是否有指定权限
     */
    public function hasPermission(string $tenantId, string $userAccount, string $permissionSlug): bool;

    /**
     * 检查用户是否有指定角色
     */
    public function hasRole(string $tenantId, string $userAccount, string $roleSlug): bool;

    /**
     * 获取角色拥有的权限 slug 列表
     *
     * @param int $roleId
     * @return string[]
     */
    public function getRolePermissions(int $roleId): array;

    // ── 角色管理 ──────────────────────────────────────────────

    public function findRoleById(int $id): ?Role;

    /**
     * @return array{items: Role[], total: int}
     */
    public function findRolesByTenant(string $tenantId, int $page = 1, int $pageSize = 20): array;

    public function saveRole(Role $role): Role;

    public function deleteRole(int $id): bool;

    public function roleSlugExists(string $tenantId, string $slug, ?int $excludeId = null): bool;

    // ── 权限管理 ──────────────────────────────────────────────

    public function findPermissionById(int $id): ?Permission;

    /**
     * @return Permission[]
     */
    public function findAllPermissions(): array;

    /**
     * 为角色分配权限
     *
     * @param int $roleId
     * @param int[] $permissionIds
     */
    public function assignPermissionsToRole(int $roleId, array $permissionIds): void;

    /**
     * 移除角色的所有权限
     */
    public function removeAllPermissionsFromRole(int $roleId): void;

    // ── 用户角色管理 ──────────────────────────────────────────

    /**
     * 为用户分配角色
     */
    public function assignRoleToUser(string $tenantId, string $userAccount, int $roleId): void;

    /**
     * 移除用户的角色
     */
    public function removeRoleFromUser(string $tenantId, string $userAccount, int $roleId): void;

    /**
     * 移除用户的所有角色
     */
    public function removeAllRolesFromUser(string $tenantId, string $userAccount): void;
}
