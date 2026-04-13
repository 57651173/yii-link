<?php

declare(strict_types=1);

use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * RBAC：角色表（tenant_id 为 char(32)）
 */
final class M240102000001CreateRolesTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('sys_roles', [
            'id' => ColumnBuilder::primaryKey()->unsigned()->comment('角色ID'),

            'tenant_id' => ColumnBuilder::char(32)->notNull()->comment('所属租户主键'),

            'name' => ColumnBuilder::string(64)->notNull()->comment('角色名称'),
            'slug' => ColumnBuilder::string(64)->notNull()->comment('租户内 slug'),
            'description' => ColumnBuilder::string(255)->null()->comment('说明'),

            'created_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'",
            'updated_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'",
        ]);

        $b->createIndex('sys_roles', 'uk_roles_tenant_slug', ['tenant_id', 'slug'], IndexType::UNIQUE);
        $b->createIndex('sys_roles', 'idx_roles_tenant_id', ['tenant_id']);

        $b->addForeignKey(
            'sys_roles',
            'fk_roles_tenant_id',
            'tenant_id',
            '{{%sys_tenants}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('sys_roles', 'fk_roles_tenant_id');
        $b->dropTable('sys_roles');
    }
}
