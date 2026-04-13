<?php

/**
 * 中文翻译文件
 */

return [
    // 通用
    'success' => '成功',
    'error' => '错误',
    'warning' => '警告',
    'info' => '信息',
    
    // 用户相关
    'user.created.success' => '用户创建成功',
    'user.updated.success' => '用户更新成功',
    'user.deleted.success' => '用户删除成功',
    'user.not.found' => '用户不存在',
    'user.disabled.success' => '用户已禁用',
    'user.enabled.success' => '用户已启用',
    
    // 认证相关
    'auth.login.success' => '登录成功',
    'auth.login.failed' => '登录失败',
    'auth.logout.success' => '退出登录成功',
    'auth.token.invalid' => 'Token 无效',
    'auth.token.expired' => 'Token 已过期',
    'auth.token.refreshed' => 'Token 刷新成功',
    
    // 权限相关
    'permission.denied' => '权限不足',
    'permission.created.success' => '权限创建成功',
    'permission.not.found' => '权限不存在',
    
    // 租户相关
    'tenant.created.success' => '租户创建成功',
    'tenant.not.found' => '租户不存在',
    'tenant.quota.exceeded' => '租户配额已超限',
    
    // 验证相关
    'validation.required' => '{field} 不能为空',
    'validation.email' => '{field} 邮箱格式不正确',
    'validation.min.length' => '{field} 长度不能少于 {min} 位',
    'validation.max.length' => '{field} 长度不能超过 {max} 位',
    
    // 限流相关
    'rate_limit.exceeded' => '请求过于频繁，请稍后重试',
    
    // 系统相关
    'system.healthy' => '系统运行正常',
    'system.unhealthy' => '系统部分组件异常',
];
