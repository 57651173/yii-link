<?php

declare(strict_types=1);

use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * 登录凭证全局唯一：`account`、`email` 在全库各至多一行（同一登录名只对应一个 tenant_id）。
 *
 * 先解除 `sys_user_role` 对外键依赖的唯一索引，再替换为全局唯一列索引，并重建 `(tenant_id, account)` 唯一索引以保留 RBAC 外键。
 */
final class M240103000000UsersGlobalUniqueAccountEmail implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->dropForeignKey('sys_user_role', 'fk_sys_ur_user_tenant_account');

        $b->dropIndex('sys_users', 'uk_users_tenant_account');
        $b->dropIndex('sys_users', 'uk_users_tenant_email');

        $b->createIndex('sys_users', 'uk_users_account', ['account'], IndexType::UNIQUE);
        $b->createIndex('sys_users', 'uk_users_email', ['email'], IndexType::UNIQUE);

        $b->createIndex('sys_users', 'uk_users_tenant_account', ['tenant_id', 'account'], IndexType::UNIQUE);

        $b->addForeignKey(
            'sys_user_role',
            'fk_sys_ur_user_tenant_account',
            ['tenant_id', 'user_account'],
            '{{%sys_users}}',
            ['tenant_id', 'account'],
            'CASCADE',
            'CASCADE',
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('sys_user_role', 'fk_sys_ur_user_tenant_account');

        $b->dropIndex('sys_users', 'uk_users_tenant_account');
        $b->dropIndex('sys_users', 'uk_users_account');
        $b->dropIndex('sys_users', 'uk_users_email');

        $b->createIndex('sys_users', 'uk_users_tenant_account', ['tenant_id', 'account'], IndexType::UNIQUE);
        $b->createIndex('sys_users', 'uk_users_tenant_email', ['tenant_id', 'email'], IndexType::UNIQUE);

        $b->addForeignKey(
            'sys_user_role',
            'fk_sys_ur_user_tenant_account',
            ['tenant_id', 'user_account'],
            '{{%sys_users}}',
            ['tenant_id', 'account'],
            'CASCADE',
            'CASCADE',
        );
    }
}
