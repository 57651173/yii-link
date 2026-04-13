<?php

declare(strict_types=1);

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

/**
 * 创建审计日志表
 * 
 * 用于记录系统中所有关键操作，便于安全审计、问题追溯和用户行为分析。
 * 
 * @author System
 * @since 2026-04-13
 */
final class M240104000000CreateAuditLogsTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $sql = <<<SQL
        CREATE TABLE `sys_audit_logs` (
          `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '主键',
          `tenant_id` CHAR(32) NOT NULL COMMENT '租户主键',
          
          -- 操作人信息
          `user_id` INT UNSIGNED NULL COMMENT '操作用户ID（可为空，如系统操作）',
          `user_account` VARCHAR(50) NULL COMMENT '操作用户账号（冗余，便于查询）',
          
          -- 操作信息
          `action` VARCHAR(20) NOT NULL COMMENT '操作类型：create|update|delete|login|logout|export',
          `resource_type` VARCHAR(50) NOT NULL COMMENT '资源类型：user|role|tenant|permission',
          `resource_id` VARCHAR(100) NULL COMMENT '资源ID（字符串，兼容各种主键）',
          
          -- 请求信息
          `http_method` VARCHAR(10) NULL COMMENT 'HTTP方法：GET|POST|PUT|DELETE|PATCH',
          `http_path` VARCHAR(255) NULL COMMENT '请求路径',
          `ip_address` VARCHAR(45) NULL COMMENT 'IP地址（支持IPv6）',
          `user_agent` VARCHAR(512) NULL COMMENT 'User-Agent',
          
          -- 数据变更
          `old_values` TEXT NULL COMMENT '变更前数据（JSON）',
          `new_values` TEXT NULL COMMENT '变更后数据（JSON）',
          
          -- 操作结果
          `status` ENUM('success', 'failed') NOT NULL DEFAULT 'success' COMMENT '操作结果',
          `error_message` VARCHAR(512) NULL COMMENT '错误信息（失败时）',
          
          -- 时间戳
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
          
          -- 索引
          INDEX `idx_tenant_user` (`tenant_id`, `user_id`),
          INDEX `idx_tenant_action` (`tenant_id`, `action`),
          INDEX `idx_tenant_resource` (`tenant_id`, `resource_type`, `resource_id`(50)),
          INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='审计日志表';
        SQL;

        $b->execute($sql);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('sys_audit_logs');
    }
}
