<?php

declare(strict_types=1);

namespace Application\AuditLog\Service;

use Application\Tenant\TenantContext;
use Domain\AuditLog\Entity\AuditLog;
use Domain\AuditLog\Repository\AuditLogRepositoryInterface;

/**
 * 审计日志应用服务
 * 
 * 提供审计日志的记录和查询功能。
 */
class AuditLogService
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
        private readonly TenantContext $tenantContext,
    ) {
    }
    
    /**
     * 记录审计日志
     * 
     * @param array{
     *     action: string,
     *     resource_type: string,
     *     resource_id?: string|null,
     *     http_method?: string|null,
     *     http_path?: string|null,
     *     ip_address?: string|null,
     *     user_agent?: string|null,
     *     old_values?: array|null,
     *     new_values?: array|null,
     *     user_id?: int|null,
     *     user_account?: string|null,
     *     tenant_id?: string|null,
     * } $data
     */
    public function log(array $data): void
    {
        $tenantId = $data['tenant_id'] ?? $this->tenantContext->requireTenantId();
        
        $auditLog = new AuditLog(
            tenantId: $tenantId,
            action: $data['action'],
            resourceType: $data['resource_type'],
            status: AuditLog::STATUS_SUCCESS,
            userId: $data['user_id'] ?? null,
            userAccount: $data['user_account'] ?? null,
            resourceId: $data['resource_id'] ?? null,
            httpMethod: $data['http_method'] ?? null,
            httpPath: $data['http_path'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            oldValues: $data['old_values'] ?? null,
            newValues: $data['new_values'] ?? null,
        );
        
        $this->auditLogRepository->save($auditLog);
    }
    
    /**
     * 记录成功操作
     */
    public function logSuccess(array $data): void
    {
        $this->log(array_merge($data, ['status' => AuditLog::STATUS_SUCCESS]));
    }
    
    /**
     * 记录失败操作
     */
    public function logFailed(array $data, string $errorMessage): void
    {
        $tenantId = $data['tenant_id'] ?? $this->tenantContext->getTenantId();
        
        if ($tenantId === null) {
            // 如果没有租户上下文（如登录失败），使用默认租户
            $tenantId = md5('sys_tenant_default');
        }
        
        $auditLog = new AuditLog(
            tenantId: $tenantId,
            action: $data['action'],
            resourceType: $data['resource_type'],
            status: AuditLog::STATUS_FAILED,
            userId: $data['user_id'] ?? null,
            userAccount: $data['user_account'] ?? null,
            resourceId: $data['resource_id'] ?? null,
            httpMethod: $data['http_method'] ?? null,
            httpPath: $data['http_path'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            oldValues: $data['old_values'] ?? null,
            newValues: $data['new_values'] ?? null,
            errorMessage: $errorMessage,
        );
        
        $this->auditLogRepository->save($auditLog);
    }
    
    /**
     * 根据 ID 获取审计日志
     */
    public function getById(int $id): ?AuditLog
    {
        return $this->auditLogRepository->findById($id);
    }
    
    /**
     * 获取审计日志列表
     * 
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param array $filters 过滤条件
     * @return array{items: AuditLog[], total: int}
     */
    public function getList(int $page = 1, int $pageSize = 20, array $filters = []): array
    {
        return $this->auditLogRepository->findAll($page, $pageSize, $filters);
    }
}
