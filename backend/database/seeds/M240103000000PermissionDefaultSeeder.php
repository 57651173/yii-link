<?php

declare(strict_types=1);

namespace Database\Seeds;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 默认权限种子数据
 *
 * 权限设计：
 * - 权限是全局的（不属于任何租户）
 * - 采用树形结构（parent_id）
 * - type: menu（菜单）、button（按钮操作）
 * - slug 用于代码中检查权限（如 user.list）
 */
final class M240103000000PermissionDefaultSeeder implements SeederInterface
{
    public function run(ConnectionInterface $db): void
    {
        $permissions = $this->getDefaultPermissions();

        foreach ($permissions as $perm) {
            $this->insertPermissionIfMissing($db, $perm);
        }
    }

    /**
     * 获取默认权限列表
     *
     * @return array[]
     */
    private function getDefaultPermissions(): array
    {
        return [
            // ==================== 租户管理（平台管理员） ====================
            [
                'id' => 1000,
                'parent_id' => null,
                'name' => '租户管理',
                'slug' => 'tenant',
                'type' => 'menu',
                'sort_order' => 1000,
                'description' => '平台管理员 - 租户管理模块',
            ],
            [
                'id' => 1001,
                'parent_id' => 1000,
                'name' => '租户列表',
                'slug' => 'tenant.list',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/tenants',
                'sort_order' => 1,
            ],
            [
                'id' => 1002,
                'parent_id' => 1000,
                'name' => '创建租户',
                'slug' => 'tenant.create',
                'type' => 'button',
                'http_method' => 'POST',
                'http_path' => '/api/v1/tenants',
                'sort_order' => 2,
            ],
            [
                'id' => 1003,
                'parent_id' => 1000,
                'name' => '编辑租户',
                'slug' => 'tenant.update',
                'type' => 'button',
                'http_method' => 'PUT',
                'http_path' => '/api/v1/tenants/*',
                'sort_order' => 3,
            ],
            [
                'id' => 1004,
                'parent_id' => 1000,
                'name' => '删除租户',
                'slug' => 'tenant.delete',
                'type' => 'button',
                'http_method' => 'DELETE',
                'http_path' => '/api/v1/tenants/*',
                'sort_order' => 4,
            ],
            [
                'id' => 1005,
                'parent_id' => 1000,
                'name' => '禁用/启用租户',
                'slug' => 'tenant.toggle_status',
                'type' => 'button',
                'http_method' => 'PATCH',
                'http_path' => '/api/v1/tenants/*/disable,/api/v1/tenants/*/enable',
                'sort_order' => 5,
            ],

            // ==================== 用户管理 ====================
            [
                'id' => 2000,
                'parent_id' => null,
                'name' => '用户管理',
                'slug' => 'user',
                'type' => 'menu',
                'sort_order' => 2000,
                'description' => '租户内用户管理',
            ],
            [
                'id' => 2001,
                'parent_id' => 2000,
                'name' => '用户列表',
                'slug' => 'user.list',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/users',
                'sort_order' => 1,
            ],
            [
                'id' => 2002,
                'parent_id' => 2000,
                'name' => '用户详情',
                'slug' => 'user.view',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/users/*',
                'sort_order' => 2,
            ],
            [
                'id' => 2003,
                'parent_id' => 2000,
                'name' => '创建用户',
                'slug' => 'user.create',
                'type' => 'button',
                'http_method' => 'POST',
                'http_path' => '/api/v1/users',
                'sort_order' => 3,
            ],
            [
                'id' => 2004,
                'parent_id' => 2000,
                'name' => '编辑用户',
                'slug' => 'user.update',
                'type' => 'button',
                'http_method' => 'PUT',
                'http_path' => '/api/v1/users/*',
                'sort_order' => 4,
            ],
            [
                'id' => 2005,
                'parent_id' => 2000,
                'name' => '删除用户',
                'slug' => 'user.delete',
                'type' => 'button',
                'http_method' => 'DELETE',
                'http_path' => '/api/v1/users/*',
                'sort_order' => 5,
            ],
            [
                'id' => 2006,
                'parent_id' => 2000,
                'name' => '禁用/启用用户',
                'slug' => 'user.toggle_status',
                'type' => 'button',
                'http_method' => 'PATCH',
                'http_path' => '/api/v1/users/*/disable,/api/v1/users/*/enable',
                'sort_order' => 6,
            ],
            [
                'id' => 2007,
                'parent_id' => 2000,
                'name' => '重置密码',
                'slug' => 'user.reset_password',
                'type' => 'button',
                'http_method' => 'POST',
                'http_path' => '/api/v1/users/*/reset-password',
                'sort_order' => 7,
            ],

            // ==================== 角色管理 ====================
            [
                'id' => 3000,
                'parent_id' => null,
                'name' => '角色管理',
                'slug' => 'role',
                'type' => 'menu',
                'sort_order' => 3000,
                'description' => '租户内角色管理',
            ],
            [
                'id' => 3001,
                'parent_id' => 3000,
                'name' => '角色列表',
                'slug' => 'role.list',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/roles',
                'sort_order' => 1,
            ],
            [
                'id' => 3002,
                'parent_id' => 3000,
                'name' => '角色详情',
                'slug' => 'role.view',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/roles/*',
                'sort_order' => 2,
            ],
            [
                'id' => 3003,
                'parent_id' => 3000,
                'name' => '创建角色',
                'slug' => 'role.create',
                'type' => 'button',
                'http_method' => 'POST',
                'http_path' => '/api/v1/roles',
                'sort_order' => 3,
            ],
            [
                'id' => 3004,
                'parent_id' => 3000,
                'name' => '编辑角色',
                'slug' => 'role.update',
                'type' => 'button',
                'http_method' => 'PUT',
                'http_path' => '/api/v1/roles/*',
                'sort_order' => 4,
            ],
            [
                'id' => 3005,
                'parent_id' => 3000,
                'name' => '删除角色',
                'slug' => 'role.delete',
                'type' => 'button',
                'http_method' => 'DELETE',
                'http_path' => '/api/v1/roles/*',
                'sort_order' => 5,
            ],
            [
                'id' => 3006,
                'parent_id' => 3000,
                'name' => '分配权限',
                'slug' => 'role.assign_permissions',
                'type' => 'button',
                'http_method' => 'PUT',
                'http_path' => '/api/v1/roles/*/permissions',
                'sort_order' => 6,
            ],
            [
                'id' => 3007,
                'parent_id' => 3000,
                'name' => '查看角色权限',
                'slug' => 'role.view_permissions',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/roles/*/permissions',
                'sort_order' => 7,
            ],

            // ==================== 权限管理 ====================
            [
                'id' => 4000,
                'parent_id' => null,
                'name' => '权限管理',
                'slug' => 'permission',
                'type' => 'menu',
                'sort_order' => 4000,
                'description' => '系统权限查看（全局共享）',
            ],
            [
                'id' => 4001,
                'parent_id' => 4000,
                'name' => '权限列表',
                'slug' => 'permission.list',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/permissions',
                'sort_order' => 1,
            ],
            [
                'id' => 4002,
                'parent_id' => 4000,
                'name' => '权限树',
                'slug' => 'permission.tree',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/permissions/tree',
                'sort_order' => 2,
            ],
            [
                'id' => 4003,
                'parent_id' => 4000,
                'name' => '权限详情',
                'slug' => 'permission.view',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/permissions/*',
                'sort_order' => 3,
            ],

            // ==================== 用户角色分配 ====================
            [
                'id' => 5000,
                'parent_id' => null,
                'name' => '用户角色',
                'slug' => 'user_role',
                'type' => 'menu',
                'sort_order' => 5000,
                'description' => '用户角色分配管理',
            ],
            [
                'id' => 5001,
                'parent_id' => 5000,
                'name' => '查看用户角色',
                'slug' => 'user_role.list',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/users/*/roles',
                'sort_order' => 1,
            ],
            [
                'id' => 5002,
                'parent_id' => 5000,
                'name' => '分配角色',
                'slug' => 'user_role.assign',
                'type' => 'button',
                'http_method' => 'PUT',
                'http_path' => '/api/v1/users/*/roles',
                'sort_order' => 2,
            ],
            [
                'id' => 5003,
                'parent_id' => 5000,
                'name' => '移除角色',
                'slug' => 'user_role.remove',
                'type' => 'button',
                'http_method' => 'DELETE',
                'http_path' => '/api/v1/users/*/roles/*',
                'sort_order' => 3,
            ],
        ];
    }

    /**
     * 插入权限（如果不存在）
     *
     * @param array{id: int, parent_id: int|null, name: string, slug: string, type: string, ...} $perm
     */
    private function insertPermissionIfMissing(ConnectionInterface $db, array $perm): void
    {
        $exists = (int)$db->createCommand(
            'SELECT COUNT(*) FROM {{%sys_permissions}} WHERE id = :id',
            [':id' => $perm['id']]
        )->queryScalar();

        if ($exists > 0) {
            return;
        }

        $db->createCommand()->insert('{{%sys_permissions}}', [
            'id' => $perm['id'],
            'parent_id' => $perm['parent_id'] ?? null,
            'name' => $perm['name'],
            'slug' => $perm['slug'],
            'type' => $perm['type'],
            'http_method' => $perm['http_method'] ?? null,
            'http_path' => $perm['http_path'] ?? null,
            'sort_order' => $perm['sort_order'] ?? 0,
            'description' => $perm['description'] ?? null,
        ])->execute();
    }
}
