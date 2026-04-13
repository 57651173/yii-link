<?php

declare(strict_types=1);

namespace Domain\AuditLog\Repository;

use Domain\AuditLog\Entity\AuditLog;

/**
 * 审计日志仓储接口
 * 
 * 定义审计日志数据访问的契约。
 */
interface AuditLogRepositoryInterface
{
    /**
     * 保存审计日志
     */
    public function save(AuditLog $auditLog): AuditLog;
    
    /**
     * 根据 ID 查找审计日志
     */
    public function findById(int $id): ?AuditLog;
    
    /**
     * 获取审计日志列表（支持分页和过滤）
     * 
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param array $filters 过滤条件 {
     *     user_id?: int,
     *     action?: string,
     *     resource_type?: string,
     *     start_date?: string,
     *     end_date?: string
     * }
     * @return array{items: AuditLog[], total: int}
     */
    public function findAll(int $page = 1, int $pageSize = 20, array $filters = []): array;
}
