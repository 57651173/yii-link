<?php

declare(strict_types=1);

use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * SaaS：租户表（主键 id 为 32 字符，须在 sys_users 之前执行）
 */
final class M240100000000CreateTenantsTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('sys_tenants', [
            'id' => ColumnBuilder::char(32)->notNull()->comment('租户主键（32 字符，建议 UUID 无横线）'),

            'name' => ColumnBuilder::string(128)->notNull()->comment('租户名称/企业名'),
            'code' => ColumnBuilder::string(64)->notNull()->comment('租户编码（唯一）'),

            'status' => ColumnBuilder::tinyint(1)->notNull()->defaultValue(10)->comment('10 正常 9 未激活 1 禁用 0 删除'),

            'plan_code' => ColumnBuilder::string(32)->null()->comment('订阅套餐编码'),
            'max_users' => ColumnBuilder::integer()->unsigned()->null()->comment('用户数上限，NULL 不限制'),

            'contact_name' => ColumnBuilder::string(64)->null()->comment('联系人'),
            'contact_phone' => ColumnBuilder::string(32)->null()->comment('联系电话'),
            'contact_email' => ColumnBuilder::string(255)->null()->comment('联系邮箱'),

            'settings_json' => ColumnBuilder::text()->null()->comment('扩展配置 JSON'),
            'remark' => ColumnBuilder::string(500)->null()->comment('备注'),

            'expired_at' => ColumnBuilder::datetime()->null()->comment('订阅/试用到期时间'),
            'deleted_at' => ColumnBuilder::datetime()->null()->comment('软删除'),

            'created_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'",
            'updated_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'",
        ]);

        $b->addPrimaryKey('sys_tenants', 'pk_tenants', 'id');
        $b->createIndex('sys_tenants', 'uk_tenants_code', ['code'], IndexType::UNIQUE);
        $b->createIndex('sys_tenants', 'idx_tenants_status', ['status']);
        $b->createIndex('sys_tenants', 'idx_tenants_deleted_at', ['deleted_at']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('sys_tenants');
    }
}
