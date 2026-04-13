<?php

declare(strict_types=1);

namespace Database\Seeds;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 审计日志权限种子数据
 * 
 * 添加审计日志管理所需的权限。
 */
final class M240104000001AuditLogPermissionSeeder
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {
    }
    
    public function run(): void
    {
        echo "开始添加审计日志权限...\n";
        
        // 1. 添加审计日志权限（2个）
        $permissions = [
            [
                'id' => 31,
                'parent_id' => null,
                'name' => '审计日志管理',
                'slug' => 'audit_log',
                'type' => 'menu',
                'http_method' => null,
                'http_path' => null,
                'sort_order' => 600,
                'description' => '审计日志菜单',
            ],
            [
                'id' => 32,
                'parent_id' => 31,
                'name' => '查看审计日志列表',
                'slug' => 'audit_log.list',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/audit-logs',
                'sort_order' => 601,
                'description' => '查看审计日志列表',
            ],
            [
                'id' => 33,
                'parent_id' => 31,
                'name' => '查看审计日志详情',
                'slug' => 'audit_log.view',
                'type' => 'button',
                'http_method' => 'GET',
                'http_path' => '/api/v1/audit-logs/{id}',
                'sort_order' => 602,
                'description' => '查看审计日志详情',
            ],
        ];
        
        foreach ($permissions as $perm) {
            $exists = $this->db->createCommand(
                'SELECT COUNT(*) FROM sys_permissions WHERE id = :id',
                [':id' => $perm['id']]
            )->queryScalar();
            
            if ($exists > 0) {
                echo "  权限 {$perm['slug']} 已存在，跳过\n";
                continue;
            }
            
            $this->db->createCommand()->insert('sys_permissions', $perm)->execute();
            echo "  ✅ 添加权限：{$perm['slug']}\n";
        }
        
        // 2. 为 admin 和 manager 角色分配审计日志权限
        echo "\n为角色分配审计日志权限...\n";
        
        // 获取 system 租户的 admin 和 manager 角色
        $tenantId = md5('sys_tenant_default');
        
        $adminRole = $this->db->createCommand(
            'SELECT id FROM sys_roles WHERE tenant_id = :tid AND slug = :slug LIMIT 1',
            [':tid' => $tenantId, ':slug' => 'admin']
        )->queryOne();
        
        $managerRole = $this->db->createCommand(
            'SELECT id FROM sys_roles WHERE tenant_id = :tid AND slug = :slug LIMIT 1',
            [':tid' => $tenantId, ':slug' => 'manager']
        )->queryOne();
        
        if ($adminRole) {
            // admin 角色拥有所有审计日志权限
            $permissionIds = [32, 33];
            foreach ($permissionIds as $pid) {
                $exists = $this->db->createCommand(
                    'SELECT COUNT(*) FROM sys_role_permission WHERE role_id = :rid AND permission_id = :pid',
                    [':rid' => $adminRole['id'], ':pid' => $pid]
                )->queryScalar();
                
                if ($exists == 0) {
                    $this->db->createCommand()->insert('sys_role_permission', [
                        'role_id' => $adminRole['id'],
                        'permission_id' => $pid,
                    ])->execute();
                }
            }
            echo "  ✅ admin 角色：分配 2 个审计日志权限\n";
        }
        
        if ($managerRole) {
            // manager 角色拥有查看权限
            $permissionIds = [32, 33];
            foreach ($permissionIds as $pid) {
                $exists = $this->db->createCommand(
                    'SELECT COUNT(*) FROM sys_role_permission WHERE role_id = :rid AND permission_id = :pid',
                    [':rid' => $managerRole['id'], ':pid' => $pid]
                )->queryScalar();
                
                if ($exists == 0) {
                    $this->db->createCommand()->insert('sys_role_permission', [
                        'role_id' => $managerRole['id'],
                        'permission_id' => $pid,
                    ])->execute();
                }
            }
            echo "  ✅ manager 角色：分配 2 个审计日志权限\n";
        }
        
        echo "\n✅ 审计日志权限添加完成！\n";
    }
}
