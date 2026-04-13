<?php

declare(strict_types=1);

namespace Database\Seeds;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 默认角色 + 为用户绑定角色（依赖 {@see M240101000001UserDefaultsSeeder}）。
 *
 * SaaS 角色设计：
 * - 每个租户的角色是**完全独立**的（通过 tenant_id 隔离）
 * - 不存在跨租户的"超级管理员"角色
 * - 用户的"平台管理员"能力由 `users.is_platform_admin` 字段控制（不在 RBAC 中）
 * 
 * 默认角色（仅演示用，实际租户应自行创建角色）：
 * - admin: 租户管理员（租户内最高权限）
 * - manager: 部门经理
 * - worker: 普通员工
 * - guest: 访客（只读）
 */
final class M240101000002RbacDefaultSeeder implements SeederInterface
{
    public function run(ConnectionInterface $db): void
    {
        $tenantId = $db->createCommand(
            'SELECT id FROM {{%sys_tenants}} WHERE code = :code LIMIT 1',
            [':code' => 'system']
        )->queryScalar();

        if ($tenantId === false || $tenantId === null) {
            throw new \RuntimeException('缺少 system 租户，请先执行 M240100000001DefaultTenantSeeder。');
        }

        $tenantId = (string)$tenantId;

        // 租户内默认角色（每个租户独立）
        $roles = [
            ['slug' => 'admin', 'name' => '租户管理员', 'description' => '租户内最高权限（非平台管理员）'],
            ['slug' => 'manager', 'name' => '部门经理', 'description' => '部门管理权限'],
            ['slug' => 'worker', 'name' => '普通员工', 'description' => '日常业务操作'],
            ['slug' => 'guest', 'name' => '访客', 'description' => '只读访问'],
        ];

        foreach ($roles as $role) {
            $this->insertRoleIfMissing($db, $tenantId, $role);
        }

        $slugToId = $this->loadRoleIdsBySlug($db, $tenantId);

        // 用户角色分配（仅演示）
        // 注意：admin 用户虽然有 is_platform_admin=1（平台能力），但在租户内也需要角色
        $assignments = [
            'admin' => 'admin',    // 平台管理员 + 租户管理员
            'root' => 'admin',     // 租户管理员
            'test' => 'worker',    // 普通员工
            'demo' => 'worker',    // 普通员工
            'guest' => 'guest',    // 访客
        ];

        foreach ($assignments as $account => $roleSlug) {
            $roleId = $slugToId[$roleSlug] ?? null;
            if ($roleId === null) {
                continue;
            }
            $this->insertUserRoleIfMissing($db, $tenantId, $account, $roleId);
        }
    }

    /**
     * @param array{slug: string, name: string, description?: string|null} $role
     */
    private function insertRoleIfMissing(ConnectionInterface $db, string $tenantId, array $role): void
    {
        $n = (int)$db->createCommand(
            'SELECT COUNT(*) FROM {{%sys_roles}} WHERE tenant_id = :tid AND slug = :slug',
            [':tid' => $tenantId, ':slug' => $role['slug']]
        )->queryScalar();

        if ($n > 0) {
            return;
        }

        $db->createCommand()->insert('{{%sys_roles}}', [
            'tenant_id' => $tenantId,
            'name' => $role['name'],
            'slug' => $role['slug'],
            'description' => $role['description'] ?? null,
        ])->execute();
    }

    /**
     * @return array<string, int> slug => id
     */
    private function loadRoleIdsBySlug(ConnectionInterface $db, string $tenantId): array
    {
        $rows = $db->createCommand(
            'SELECT id, slug FROM {{%sys_roles}} WHERE tenant_id = :tid',
            [':tid' => $tenantId]
        )->queryAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(string)$row['slug']] = (int)$row['id'];
        }

        return $map;
    }

    private function insertUserRoleIfMissing(ConnectionInterface $db, string $tenantId, string $account, int $roleId): void
    {
        $userExists = (int)$db->createCommand(
            'SELECT COUNT(*) FROM {{%sys_users}} WHERE tenant_id = :tid AND account = :acc AND deleted_at IS NULL',
            [':tid' => $tenantId, ':acc' => $account]
        )->queryScalar();

        if ($userExists === 0) {
            return;
        }

        $n = (int)$db->createCommand(
            'SELECT COUNT(*) FROM {{%sys_user_role}} WHERE tenant_id = :tid AND user_account = :acc AND role_id = :rid',
            [':tid' => $tenantId, ':acc' => $account, ':rid' => $roleId]
        )->queryScalar();

        if ($n > 0) {
            return;
        }

        $db->createCommand()->insert('{{%sys_user_role}}', [
            'tenant_id' => $tenantId,
            'user_account' => $account,
            'role_id' => $roleId,
        ])->execute();
    }
}
