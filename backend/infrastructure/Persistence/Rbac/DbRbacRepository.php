<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Rbac;

use Domain\Rbac\Entity\Permission;
use Domain\Rbac\Entity\Role;
use Domain\Rbac\Repository\RbacRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * RBAC 仓储数据库实现（查询用户角色与权限 + 角色/权限管理）
 */
class DbRbacRepository implements RbacRepositoryInterface
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {
    }

    // ── 权限检查 ──────────────────────────────────────────────

    public function getUserRoleIds(string $tenantId, string $userAccount): array
    {
        $rows = $this->db->createCommand(
            'SELECT role_id FROM {{%sys_user_role}} WHERE tenant_id = :tid AND user_account = :account',
            [':tid' => $tenantId, ':account' => $userAccount]
        )->queryAll();

        return array_map(fn($row) => (int)$row['role_id'], $rows);
    }

    public function getUserPermissions(string $tenantId, string $userAccount): array
    {
        $sql = <<<SQL
            SELECT DISTINCT p.slug
            FROM {{%sys_permissions}} p
            INNER JOIN {{%sys_role_permission}} rp ON rp.permission_id = p.id
            INNER JOIN {{%sys_user_role}} ur ON ur.role_id = rp.role_id
            WHERE ur.tenant_id = :tid AND ur.user_account = :account
        SQL;

        $rows = $this->db->createCommand($sql, [
            ':tid' => $tenantId,
            ':account' => $userAccount,
        ])->queryAll();

        return array_map(fn($row) => (string)$row['slug'], $rows);
    }

    public function hasPermission(string $tenantId, string $userAccount, string $permissionSlug): bool
    {
        $sql = <<<SQL
            SELECT COUNT(*) FROM {{%sys_permissions}} p
            INNER JOIN {{%sys_role_permission}} rp ON rp.permission_id = p.id
            INNER JOIN {{%sys_user_role}} ur ON ur.role_id = rp.role_id
            WHERE ur.tenant_id = :tid AND ur.user_account = :account AND p.slug = :slug
        SQL;

        $count = (int)$this->db->createCommand($sql, [
            ':tid' => $tenantId,
            ':account' => $userAccount,
            ':slug' => $permissionSlug,
        ])->queryScalar();

        return $count > 0;
    }

    public function hasRole(string $tenantId, string $userAccount, string $roleSlug): bool
    {
        $sql = <<<SQL
            SELECT COUNT(*) FROM {{%sys_user_role}} ur
            INNER JOIN {{%sys_roles}} r ON r.id = ur.role_id
            WHERE ur.tenant_id = :tid AND ur.user_account = :account AND r.slug = :slug
        SQL;

        $count = (int)$this->db->createCommand($sql, [
            ':tid' => $tenantId,
            ':account' => $userAccount,
            ':slug' => $roleSlug,
        ])->queryScalar();

        return $count > 0;
    }

    public function getRolePermissions(int $roleId): array
    {
        $sql = <<<SQL
            SELECT p.slug FROM {{%sys_permissions}} p
            INNER JOIN {{%sys_role_permission}} rp ON rp.permission_id = p.id
            WHERE rp.role_id = :rid
        SQL;

        $rows = $this->db->createCommand($sql, [':rid' => $roleId])->queryAll();

        return array_map(fn($row) => (string)$row['slug'], $rows);
    }

    // ── 角色管理 ──────────────────────────────────────────────

    public function findRoleById(int $id): ?Role
    {
        $row = $this->db->createCommand(
            'SELECT * FROM {{%sys_roles}} WHERE id = :id LIMIT 1',
            [':id' => $id]
        )->queryOne();

        return $row ? $this->hydrateRole($row) : null;
    }

    public function findRolesByTenant(string $tenantId, int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;

        $total = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM {{%sys_roles}} WHERE tenant_id = :tid',
            [':tid' => $tenantId]
        )->queryScalar();

        $rows = $this->db->createCommand(
            'SELECT * FROM {{%sys_roles}} WHERE tenant_id = :tid ORDER BY id DESC LIMIT :limit OFFSET :offset',
            [':tid' => $tenantId, ':limit' => $pageSize, ':offset' => $offset]
        )->queryAll();

        return [
            'items' => array_map([$this, 'hydrateRole'], $rows),
            'total' => $total,
        ];
    }

    public function saveRole(Role $role): Role
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $data = [
            'tenant_id' => $role->getTenantId(),
            'name' => $role->getName(),
            'slug' => $role->getSlug(),
            'description' => $role->getDescription(),
            'updated_at' => $now,
        ];

        if ($role->getId() === null) {
            $data['created_at'] = $now;
            $this->db->createCommand()->insert('{{%sys_roles}}', $data)->execute();
            $id = (int)$this->db->getLastInsertID();

            return new Role(
                id: $id,
                tenantId: $role->getTenantId(),
                name: $role->getName(),
                slug: $role->getSlug(),
                description: $role->getDescription(),
                createdAt: new \DateTimeImmutable($now),
                updatedAt: new \DateTimeImmutable($now),
            );
        }

        $this->db->createCommand()->update(
            '{{%sys_roles}}',
            $data,
            ['id' => $role->getId()]
        )->execute();

        return $role;
    }

    public function deleteRole(int $id): bool
    {
        $affected = $this->db->createCommand()->delete(
            '{{%sys_roles}}',
            ['id' => $id]
        )->execute();

        return $affected > 0;
    }

    public function roleSlugExists(string $tenantId, string $slug, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM {{%sys_roles}} WHERE tenant_id = :tid AND slug = :slug';
        $params = [':tid' => $tenantId, ':slug' => $slug];

        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeId;
        }

        return (int)$this->db->createCommand($sql, $params)->queryScalar() > 0;
    }

    // ── 权限管理 ──────────────────────────────────────────────

    public function findPermissionById(int $id): ?Permission
    {
        $row = $this->db->createCommand(
            'SELECT * FROM {{%sys_permissions}} WHERE id = :id LIMIT 1',
            [':id' => $id]
        )->queryOne();

        return $row ? $this->hydratePermission($row) : null;
    }

    public function findAllPermissions(): array
    {
        $rows = $this->db->createCommand(
            'SELECT * FROM {{%sys_permissions}} ORDER BY sort_order ASC, id ASC'
        )->queryAll();

        return array_map([$this, 'hydratePermission'], $rows);
    }

    public function assignPermissionsToRole(int $roleId, array $permissionIds): void
    {
        foreach ($permissionIds as $permissionId) {
            $exists = (int)$this->db->createCommand(
                'SELECT COUNT(*) FROM {{%sys_role_permission}} WHERE role_id = :rid AND permission_id = :pid',
                [':rid' => $roleId, ':pid' => $permissionId]
            )->queryScalar();

            if ($exists === 0) {
                $this->db->createCommand()->insert('{{%sys_role_permission}}', [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ])->execute();
            }
        }
    }

    public function removeAllPermissionsFromRole(int $roleId): void
    {
        $this->db->createCommand()->delete(
            '{{%sys_role_permission}}',
            ['role_id' => $roleId]
        )->execute();
    }

    // ── 用户角色管理 ──────────────────────────────────────────

    public function assignRoleToUser(string $tenantId, string $userAccount, int $roleId): void
    {
        $exists = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM {{%sys_user_role}} WHERE tenant_id = :tid AND user_account = :account AND role_id = :rid',
            [':tid' => $tenantId, ':account' => $userAccount, ':rid' => $roleId]
        )->queryScalar();

        if ($exists === 0) {
            $this->db->createCommand()->insert('{{%sys_user_role}}', [
                'tenant_id' => $tenantId,
                'user_account' => $userAccount,
                'role_id' => $roleId,
            ])->execute();
        }
    }

    public function removeRoleFromUser(string $tenantId, string $userAccount, int $roleId): void
    {
        $this->db->createCommand()->delete(
            '{{%sys_user_role}}',
            ['tenant_id' => $tenantId, 'user_account' => $userAccount, 'role_id' => $roleId]
        )->execute();
    }

    public function removeAllRolesFromUser(string $tenantId, string $userAccount): void
    {
        $this->db->createCommand()->delete(
            '{{%sys_user_role}}',
            ['tenant_id' => $tenantId, 'user_account' => $userAccount]
        )->execute();
    }

    // ── 私有方法 ──────────────────────────────────────────────

    private function hydrateRole(array $row): Role
    {
        return new Role(
            id: (int)$row['id'],
            tenantId: (string)$row['tenant_id'],
            name: (string)$row['name'],
            slug: (string)$row['slug'],
            description: $row['description'] !== null ? (string)$row['description'] : null,
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
        );
    }

    private function hydratePermission(array $row): Permission
    {
        return new Permission(
            id: (int)$row['id'],
            parentId: $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
            name: (string)$row['name'],
            slug: (string)$row['slug'],
            type: (string)$row['type'],
            httpMethod: $row['http_method'] !== null ? (string)$row['http_method'] : null,
            httpPath: $row['http_path'] !== null ? (string)$row['http_path'] : null,
            sortOrder: (int)$row['sort_order'],
            description: $row['description'] !== null ? (string)$row['description'] : null,
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
        );
    }
}
