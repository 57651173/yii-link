<?php

declare(strict_types=1);

use Yiisoft\Db\Constant\IndexType;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * 用户表：tenant_id 为 char(32)；与租户、RBAC 的关联以 account 为准（见 user_role）。
 *
 * 依赖：{@see M240100000000CreateTenantsTable}
 */
final class M240101000000CreateUsersTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('sys_users', [
            'id' => ColumnBuilder::primaryKey()->unsigned()->comment('用户ID'),

            'account' => ColumnBuilder::string(100)->notNull()->comment('登录账号（租户内唯一）'),
            'email' => ColumnBuilder::string(255)->notNull()->comment('邮箱（租户内唯一）'),
            'password_hash' => ColumnBuilder::string(255)->notNull()->comment('密码哈希'),

            'nickname' => ColumnBuilder::string(100)->null()->comment('昵称/显示名'),
            'mobile' => ColumnBuilder::string(32)->null()->comment('手机号'),
            'avatar' => ColumnBuilder::string(512)->null()->comment('头像 URL'),

            'gender' => ColumnBuilder::tinyint(1)->null()->comment('性别：0 未知，1 男，2 女'),
            'locale' => ColumnBuilder::string(16)->defaultValue('zh-CN')->comment('语言区域'),
            'timezone' => ColumnBuilder::string(64)->defaultValue('Asia/Shanghai')->comment('时区'),

            'status' => ColumnBuilder::tinyint(1)->notNull()->defaultValue(10)->comment('10 正常 9 未激活 1 禁用 0 删除'),
            'tenant_id' => ColumnBuilder::char(32)->notNull()->comment('所属租户主键（32 字符）'),
            'is_platform_admin' => ColumnBuilder::tinyint(1)->notNull()->defaultValue(0)->comment('1 平台超级管理员'),

            'email_verified_at' => ColumnBuilder::datetime()->null()->comment('邮箱验证时间'),
            'last_login_at' => ColumnBuilder::datetime()->null()->comment('最后登录时间'),
            'last_login_ip' => ColumnBuilder::string(45)->null()->comment('最后登录 IP'),
            'failed_login_count' => ColumnBuilder::smallint()->notNull()->defaultValue(0)->comment('连续登录失败次数'),

            'remark' => ColumnBuilder::string(500)->null()->comment('备注'),
            'deleted_at' => ColumnBuilder::datetime()->null()->comment('软删除'),

            'created_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'",
            'updated_at' => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'",
        ]);

        $b->createIndex('sys_users', 'uk_users_tenant_account', ['tenant_id', 'account'], IndexType::UNIQUE);
        $b->createIndex('sys_users', 'uk_users_tenant_email', ['tenant_id', 'email'], IndexType::UNIQUE);
        $b->createIndex('sys_users', 'uk_users_mobile', ['mobile'], IndexType::UNIQUE);
        $b->createIndex('sys_users', 'idx_users_tenant_id', ['tenant_id']);
        $b->createIndex('sys_users', 'idx_users_status', ['status']);
        $b->createIndex('sys_users', 'idx_users_created_at', ['created_at']);
        $b->createIndex('sys_users', 'idx_users_deleted_at', ['deleted_at']);

        $b->addForeignKey(
            'sys_users',
            'fk_users_tenant_id',
            'tenant_id',
            '{{%sys_tenants}}',
            'id',
            'RESTRICT',
            'CASCADE',
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('sys_users', 'fk_users_tenant_id');
        $b->dropTable('sys_users');
    }
}
