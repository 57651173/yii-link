<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * 创建用户表
 *
 * 执行：php yii migrate:up
 * 回滚：php yii migrate:down
 */
final class M240101000000CreateUsersTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('users', [
            'id'            => $b->primaryKey()->unsigned()->comment('用户ID'),
            'name'          => $b->string(100)->notNull()->comment('用户名'),
            'email'         => $b->string(255)->notNull()->comment('邮箱（唯一）'),
            'password_hash' => $b->string(255)->notNull()->comment('密码哈希（bcrypt）'),
            'status'        => "ENUM('active','inactive','banned') NOT NULL DEFAULT 'active' COMMENT '用户状态'",
            'created_at'    => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间'",
            'updated_at'    => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'",
        ]);

        $b->createIndex('uk_users_email', 'users', ['email'], unique: true);
        $b->createIndex('idx_users_status', 'users', ['status']);
        $b->createIndex('idx_users_created_at', 'users', ['created_at']);

        // 插入测试账号（密码明文：password）
        $b->insert('users', [
            'name'          => '超级管理员',
            'email'         => 'admin@yii-link.com',
            'password_hash' => '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'status'        => 'active',
        ]);
        $b->insert('users', [
            'name'          => '测试用户',
            'email'         => 'test@yii-link.com',
            'password_hash' => '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'status'        => 'active',
        ]);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('users');
    }
}
