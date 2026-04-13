<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * SaaS：为 sys_tenants 增加配额字段（用户数 / 存储 / API 调用）
 */
final class M240105000000AddTenantQuotaFields implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->addColumn(
            'sys_tenants',
            'quota_users',
            ColumnBuilder::integer()->unsigned()->notNull()->defaultValue(100)->comment('用户数配额（0=无限制）'),
        );

        $b->addColumn(
            'sys_tenants',
            'quota_storage_mb',
            ColumnBuilder::integer()->unsigned()->notNull()->defaultValue(1024)->comment('存储空间配额MB（0=无限制）'),
        );

        $b->addColumn(
            'sys_tenants',
            'quota_api_calls',
            ColumnBuilder::integer()->unsigned()->notNull()->defaultValue(10000)->comment('API调用配额/天（0=无限制）'),
        );
    }

    public function down(MigrationBuilder $b): void
    {
        // 反向顺序删除，便于排查问题（一般无强依赖，但习惯上这样做）
        $b->dropColumn('sys_tenants', 'quota_api_calls');
        $b->dropColumn('sys_tenants', 'quota_storage_mb');
        $b->dropColumn('sys_tenants', 'quota_users');
    }
}

