<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * RBAC：用户-角色（使用 tenant_id + user_account 关联 sys_users，不使用 user_id）
 *
 * 依赖：{@see M240101000000CreateUsersTable}、{@see M240102000001CreateRolesTable}
 */
final class M240102000003CreateUserRoleTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('sys_user_role', [
            'tenant_id' => ColumnBuilder::char(32)->notNull()->comment('租户上下文'),
            'user_account' => ColumnBuilder::string(100)->notNull()->comment('用户登录账号，对应 sys_users.account'),
            'role_id' => ColumnBuilder::integer()->unsigned()->notNull()->comment('角色 ID'),
        ]);

        $b->addPrimaryKey('sys_user_role', 'pk_user_role', ['tenant_id', 'user_account', 'role_id']);

        $b->addForeignKey(
            'sys_user_role',
            'fk_sys_ur_user_tenant_account',
            ['tenant_id', 'user_account'],
            '{{%sys_users}}',
            ['tenant_id', 'account'],
            'CASCADE',
            'CASCADE',
        );

        $b->addForeignKey(
            'sys_user_role',
            'fk_sys_ur_role',
            'role_id',
            '{{%sys_roles}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('sys_user_role', 'fk_sys_ur_role');
        $b->dropForeignKey('sys_user_role', 'fk_sys_ur_user_tenant_account');
        $b->dropTable('sys_user_role');
    }
}
