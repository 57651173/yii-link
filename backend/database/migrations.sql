-- ============================================================
-- Yii-Link API 数据库初始化脚本
-- 执行方式：mysql -u root -p < database/migrations.sql
-- ============================================================

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `yii_link`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `yii_link`;

-- ============================================================
-- 用户表
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT COMMENT '用户ID',
    `name`          VARCHAR(100)    NOT NULL COMMENT '用户名',
    `email`         VARCHAR(255)    NOT NULL COMMENT '邮箱（唯一）',
    `password_hash` VARCHAR(255)    NOT NULL COMMENT '密码哈希（bcrypt）',
    `status`        ENUM('active', 'inactive', 'banned')
                                    NOT NULL DEFAULT 'active' COMMENT '用户状态',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_status` (`status`),
    KEY `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='用户表';

-- ============================================================
-- 插入测试数据（管理员账户，密码：Admin@123456）
-- ============================================================
INSERT INTO `users` (`name`, `email`, `password_hash`, `status`) VALUES
('超级管理员', 'admin@yii-link.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('测试用户', 'test@yii-link.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');

-- 注意：上方密码哈希对应明文 "password"（Laravel/bcrypt 标准测试值）
-- 生产环境请使用 UserService 的注册接口创建真实账户
