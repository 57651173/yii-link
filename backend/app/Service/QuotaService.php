<?php

declare(strict_types=1);

namespace App\Service;

use Application\Tenant\TenantContext;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 配额检查服务
 * 
 * 检查租户的资源使用是否超过配额。
 */
class QuotaService
{
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly TenantContext $tenantContext,
        private readonly RateLimiter $rateLimiter,
    ) {
    }
    
    /**
     * 检查用户数配额
     */
    public function checkUserQuota(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        
        // 获取租户配额设置
        $tenant = $this->db->createCommand(
            'SELECT quota_users FROM sys_tenants WHERE id = :id LIMIT 1',
            [':id' => $tenantId]
        )->queryOne();
        
        if (!$tenant) {
            return ['allowed' => false, 'message' => '租户不存在'];
        }
        
        $quotaUsers = (int)$tenant['quota_users'];
        
        // 0 表示无限制
        if ($quotaUsers === 0) {
            return ['allowed' => true, 'message' => '无限制'];
        }
        
        // 查询当前用户数
        $currentUsers = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM sys_users WHERE tenant_id = :tid AND deleted_at IS NULL',
            [':tid' => $tenantId]
        )->queryScalar();
        
        if ($currentUsers >= $quotaUsers) {
            return [
                'allowed' => false,
                'message' => '用户数已达配额上限',
                'current' => $currentUsers,
                'quota' => $quotaUsers,
            ];
        }
        
        return [
            'allowed' => true,
            'current' => $currentUsers,
            'quota' => $quotaUsers,
            'remaining' => $quotaUsers - $currentUsers,
        ];
    }
    
    /**
     * 检查 API 调用配额（每日）
     */
    public function checkApiQuota(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        
        // 获取租户配额设置
        $tenant = $this->db->createCommand(
            'SELECT quota_api_calls FROM sys_tenants WHERE id = :id LIMIT 1',
            [':id' => $tenantId]
        )->queryOne();
        
        if (!$tenant) {
            return ['allowed' => false, 'message' => '租户不存在'];
        }
        
        $quotaApiCalls = (int)$tenant['quota_api_calls'];
        
        // 0 表示无限制
        if ($quotaApiCalls === 0) {
            return ['allowed' => true, 'message' => '无限制'];
        }
        
        // 使用 RateLimiter 检查每日调用次数
        $result = $this->rateLimiter->attempt(
            "tenant_api_daily:{$tenantId}",
            $quotaApiCalls,
            86400 // 24 小时
        );
        
        if (!$result['allowed']) {
            return [
                'allowed' => false,
                'message' => 'API 调用次数已达每日配额上限',
                'quota' => $quotaApiCalls,
                'reset_at' => $result['reset_at'],
            ];
        }
        
        return [
            'allowed' => true,
            'quota' => $quotaApiCalls,
            'remaining' => $result['remaining'],
            'reset_at' => $result['reset_at'],
        ];
    }
    
    /**
     * 获取租户配额使用情况
     */
    public function getQuotaUsage(): array
    {
        $tenantId = $this->tenantContext->requireTenantId();
        
        // 获取配额设置
        $tenant = $this->db->createCommand(
            'SELECT quota_users, quota_storage_mb, quota_api_calls FROM sys_tenants WHERE id = :id LIMIT 1',
            [':id' => $tenantId]
        )->queryOne();
        
        if (!$tenant) {
            return [];
        }
        
        // 用户数使用情况
        $currentUsers = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM sys_users WHERE tenant_id = :tid AND deleted_at IS NULL',
            [':tid' => $tenantId]
        )->queryScalar();
        
        // API 调用使用情况（从 Redis 获取）
        $apiKey = "tenant_api_daily:{$tenantId}";
        $apiCalls = $this->rateLimiter->remaining($apiKey, (int)$tenant['quota_api_calls']);
        
        return [
            'users' => [
                'quota' => (int)$tenant['quota_users'],
                'used' => $currentUsers,
                'remaining' => max(0, (int)$tenant['quota_users'] - $currentUsers),
                'unlimited' => (int)$tenant['quota_users'] === 0,
            ],
            'storage' => [
                'quota_mb' => (int)$tenant['quota_storage_mb'],
                'used_mb' => 0, // TODO: 实现存储使用量统计
                'unlimited' => (int)$tenant['quota_storage_mb'] === 0,
            ],
            'api_calls' => [
                'quota_daily' => (int)$tenant['quota_api_calls'],
                'remaining_today' => $apiCalls,
                'unlimited' => (int)$tenant['quota_api_calls'] === 0,
            ],
        ];
    }
}
