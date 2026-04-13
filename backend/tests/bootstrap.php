<?php

declare(strict_types=1);

/*
 * PHPUnit Bootstrap 文件
 * 在测试运行前加载，用于初始化测试环境
 */

// 加载 Composer 自动加载
require __DIR__ . '/../vendor/autoload.php';

// 加载环境变量（如果存在且 Dotenv 已安装）
if (file_exists(__DIR__ . '/../.env') && class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

// 设置测试环境
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';

echo "PHPUnit Bootstrap: Test environment loaded\n";
