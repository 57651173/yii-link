<?php

declare(strict_types=1);

namespace Database\Seeds;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 为默认角色分配权限
 *
 * 依赖：
 * - {@see M240101000002RbacDefaultSeeder} - 角色数据
 * - {@see M240103000000PermissionDefaultSeeder} - 权限数据
 */
final class M240103000001AssignPermissionsToRolesSeeder implements SeederInterface
{
    public function run(ConnectionInterface $db): void
    {
        $tenantId = $db->createCommand(
            'SELECT id FROM {{%sys_tenants}} WHERE code = :code LIMIT 1',
            [':code' => 'system']
        )->queryScalar();

        if ($tenantId === false || $tenantId === null) {
            throw new \RuntimeException('缺少 system 租户');
        }

        $tenantId = (string)$tenantId;

        // 获取角色 ID
        $roles = $this->loadRoleIdsBySlug($db, $tenantId);

        // 为 admin 角色分配所有权限（租户管理员）
        if (isset($roles['admin'])) {
            $this->assignPermissionsToRole($db, $roles['admin'], [
                // 用户管理（全部权限）
                2001, 2002, 2003, 2004, 2005, 2006, 2007,
                // 角色管理（全部权限）
                3001, 3002, 3003, 3004, 3005, 3006, 3007,
                // 权限管理（只读）
                4001, 4002, 4003,
                // 用户角色分配（全部权限）
                5001, 5002, 5003,
            ]);
        }

        // 为 manager 角色分配部分权限（部门经理）
        if (isset($roles['manager'])) {
            $this->assignPermissionsToRole($db, $roles['manager'], [
                // 用户管理（查看、编辑、禁用）
                2001, 2002, 2004, 2006,
                // 角色管理（查看）
                3001, 3002,
                // 用户角色分配（查看、分配）
                5001, 5002,
            ]);
        }

        // 为 worker 角色分配基础权限（普通员工）
        if (isset($roles['worker'])) {
            $this->assignPermissionsToRole($db, $roles['worker'], [
                // 用户管理（查看列表和详情）
                2001, 2002,
            ]);
        }

        // guest 角色不分配任何权限（只读访客，按需配置）
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

    /**
     * @param int[] $permissionIds
     */
    private function assignPermissionsToRole(ConnectionInterface $db, int $roleId, array $permissionIds): void
    {
        // 先清空已有权限
        $db->createCommand()->delete('{{%sys_role_permission}}', ['role_id' => $roleId])->execute();

        // 批量插入新权限
        foreach ($permissionIds as $permissionId) {
            // 检查权限是否存在
            $exists = (int)$db->createCommand(
                'SELECT COUNT(*) FROM {{%sys_permissions}} WHERE id = :id',
                [':id' => $permissionId]
            )->queryScalar();

            if ($exists === 0) {
                continue;
            }

            // 检查是否已分配
            $assigned = (int)$db->createCommand(
                'SELECT COUNT(*) FROM {{%sys_role_permission}} WHERE role_id = :rid AND permission_id = :pid',
                [':rid' => $roleId, ':pid' => $permissionId]
            )->queryScalar();

            if ($assigned > 0) {
                continue;
            }

            $db->createCommand()->insert('{{%sys_role_permission}}', [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ])->execute();
        }
    }
}
