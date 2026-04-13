<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\AuditLog;

use Application\Tenant\TenantContext;
use Domain\AuditLog\Entity\AuditLog;
use Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 审计日志仓储的数据库实现
 * 
 * 按租户隔离查询，自动过滤当前租户的审计日志。
 */
class DbAuditLogRepository implements AuditLogRepositoryInterface
{
    private const TABLE = 'sys_audit_logs';
    
    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly TenantContext $tenantContext,
    ) {
    }
    
    public function save(AuditLog $auditLog): AuditLog
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        
        $data = [
            'tenant_id' => $auditLog->getTenantId(),
            'user_id' => $auditLog->getUserId(),
            'user_account' => $auditLog->getUserAccount(),
            'action' => $auditLog->getAction(),
            'resource_type' => $auditLog->getResourceType(),
            'resource_id' => $auditLog->getResourceId(),
            'http_method' => $auditLog->getHttpMethod(),
            'http_path' => $auditLog->getHttpPath(),
            'ip_address' => $auditLog->getIpAddress(),
            'user_agent' => $auditLog->getUserAgent(),
            'old_values' => $auditLog->getOldValues() ? json_encode($auditLog->getOldValues(), JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $auditLog->getNewValues() ? json_encode($auditLog->getNewValues(), JSON_UNESCAPED_UNICODE) : null,
            'status' => $auditLog->getStatus(),
            'error_message' => $auditLog->getErrorMessage(),
            'created_at' => $now,
        ];
        
        if ($auditLog->getId() === null) {
            // 新增
            $this->db->createCommand()->insert('{{%' . self::TABLE . '}}', $data)->execute();
            $id = (int)$this->db->getLastInsertID();
            
            return new AuditLog(
                tenantId: $auditLog->getTenantId(),
                action: $auditLog->getAction(),
                resourceType: $auditLog->getResourceType(),
                status: $auditLog->getStatus(),
                userId: $auditLog->getUserId(),
                userAccount: $auditLog->getUserAccount(),
                resourceId: $auditLog->getResourceId(),
                httpMethod: $auditLog->getHttpMethod(),
                httpPath: $auditLog->getHttpPath(),
                ipAddress: $auditLog->getIpAddress(),
                userAgent: $auditLog->getUserAgent(),
                oldValues: $auditLog->getOldValues(),
                newValues: $auditLog->getNewValues(),
                errorMessage: $auditLog->getErrorMessage(),
                id: $id,
                createdAt: new \DateTimeImmutable($now),
            );
        }
        
        // 更新（一般不会更新审计日志，但保留接口）
        $this->db->createCommand()->update(
            '{{%' . self::TABLE . '}}',
            $data,
            ['id' => $auditLog->getId()]
        )->execute();
        
        return $auditLog;
    }
    
    public function findById(int $id): ?AuditLog
    {
        $tid = $this->tenantContext->requireTenantId();
        
        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE id = :id AND tenant_id = :tid LIMIT 1',
            [':id' => $id, ':tid' => $tid]
        )->queryOne();
        
        return $row ? $this->hydrate($row) : null;
    }
    
    public function findAll(int $page = 1, int $pageSize = 20, array $filters = []): array
    {
        $tid = $this->tenantContext->requireTenantId();
        $offset = ($page - 1) * $pageSize;
        
        // 构建 WHERE 条件
        $where = ['tenant_id = :tid'];
        $params = [':tid' => $tid];
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params[':action'] = $filters['action'];
        }
        
        if (!empty($filters['resource_type'])) {
            $where[] = 'resource_type = :resource_type';
            $params[':resource_type'] = $filters['resource_type'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = 'created_at >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = 'created_at <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // 查询总数
        $total = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM {{%' . self::TABLE . '}} WHERE ' . $whereClause,
            $params
        )->queryScalar();
        
        // 查询数据
        $rows = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE ' . $whereClause . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
            array_merge($params, [':limit' => $pageSize, ':offset' => $offset])
        )->queryAll();
        
        return [
            'items' => array_map([$this, 'hydrate'], $rows),
            'total' => $total,
        ];
    }
    
    private function hydrate(array $row): AuditLog
    {
        return new AuditLog(
            tenantId: $row['tenant_id'],
            action: $row['action'],
            resourceType: $row['resource_type'],
            status: $row['status'],
            userId: $row['user_id'] ? (int)$row['user_id'] : null,
            userAccount: $row['user_account'],
            resourceId: $row['resource_id'],
            httpMethod: $row['http_method'],
            httpPath: $row['http_path'],
            ipAddress: $row['ip_address'],
            userAgent: $row['user_agent'],
            oldValues: $row['old_values'] ? json_decode($row['old_values'], true) : null,
            newValues: $row['new_values'] ? json_decode($row['new_values'], true) : null,
            errorMessage: $row['error_message'],
            id: (int)$row['id'],
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }
}
