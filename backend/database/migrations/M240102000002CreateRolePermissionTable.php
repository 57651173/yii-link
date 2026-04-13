<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * RBAC：角色-权限 关联表
 *
 * 依赖：{@see M240102000000CreatePermissionsTable}、{@see M240102000001CreateRolesTable}
 */
final class M240102000002CreateRolePermissionTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('sys_role_permission', [
            'role_id' => ColumnBuilder::integer()->unsigned()->notNull(),
            'permission_id' => ColumnBuilder::integer()->unsigned()->notNull(),
        ]);

        $b->addPrimaryKey('sys_role_permission', 'pk_role_permission', ['role_id', 'permission_id']);

        $b->addForeignKey(
            'sys_role_permission',
            'fk_sys_rp_role',
            'role_id',
            '{{%sys_roles}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
        $b->addForeignKey(
            'sys_role_permission',
            'fk_sys_rp_permission',
            'permission_id',
            '{{%sys_permissions}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('sys_role_permission', 'fk_sys_rp_permission');
        $b->dropForeignKey('sys_role_permission', 'fk_sys_rp_role');
        $b->dropTable('sys_role_permission');
    }
}
