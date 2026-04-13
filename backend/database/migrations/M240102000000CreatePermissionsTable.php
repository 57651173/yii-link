<?php

declare(strict_types=1);

use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * RBAC：权限表（全局权限点）
 */
final class M240102000000CreatePermissionsTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('sys_permissions', [
            'id' => ColumnBuilder::primaryKey()->unsigned()->comment('权限ID'),
            'parent_id' => ColumnBuilder::integer()->unsigned()->null()->comment('父级，用于菜单/分组树'),

            'name' => ColumnBuilder::string(128)->notNull()->comment('显示名称'),
            'slug' => ColumnBuilder::string(128)->notNull()->comment('唯一标识，如 user.list'),
            'type' => ColumnBuilder::string(16)->notNull()->defaultValue('api')->comment('api|menu|action'),

            'http_method' => ColumnBuilder::string(16)->null()->comment('如 GET'),
            'http_path' => ColumnBuilder::string(255)->null()->comment('路由或路径模式'),

            'sort_order' => ColumnBuilder::integer()->notNull()->defaultValue(0)->comment('排序'),
            'description' => ColumnBuilder::string(500)->null()->comment('说明'),

            'created_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'",
            'updated_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'",
        ]);

        $b->createIndex('sys_permissions', 'uk_permissions_slug', ['slug'], IndexType::UNIQUE);
        $b->createIndex('sys_permissions', 'idx_permissions_parent_id', ['parent_id']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('sys_permissions');
    }
}
