<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Exception\BusinessException;
use App\Response\ApiResponse;
use Application\AuditLog\Service\AuditLogService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 审计日志控制器
 * 
 * 提供审计日志查询功能（只读）。
 */
class AuditLogController
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }
    
    /**
     * GET /api/v1/audit-logs
     * 
     * 获取审计日志列表（分页 + 过滤）
     * 
     * 查询参数：
     * - page: 页码（默认1）
     * - page_size: 每页数量（默认20）
     * - user_id: 用户ID（可选）
     * - action: 操作类型（可选）
     * - resource_type: 资源类型（可选）
     * - start_date: 开始日期（可选，格式 Y-m-d）
     * - end_date: 结束日期（可选，格式 Y-m-d）
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        
        $page = max(1, (int)($query['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($query['page_size'] ?? 20)));
        
        // 构建过滤条件
        $filters = [];
        
        if (!empty($query['user_id'])) {
            $filters['user_id'] = (int)$query['user_id'];
        }
        
        if (!empty($query['action'])) {
            $filters['action'] = trim($query['action']);
        }
        
        if (!empty($query['resource_type'])) {
            $filters['resource_type'] = trim($query['resource_type']);
        }
        
        if (!empty($query['start_date'])) {
            $filters['start_date'] = trim($query['start_date']) . ' 00:00:00';
        }
        
        if (!empty($query['end_date'])) {
            $filters['end_date'] = trim($query['end_date']) . ' 23:59:59';
        }
        
        try {
            $result = $this->auditLogService->getList($page, $pageSize, $filters);
            $items = array_map(fn($log) => $log->toArray(), $result['items']);
            
            return ApiResponse::paginate($items, $result['total'], $page, $pageSize);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('服务暂时不可用: ' . $e->getMessage(), 503, 503);
        }
    }
    
    /**
     * GET /api/v1/audit-logs/{id}
     * 
     * 获取审计日志详情
     */
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');
        
        try {
            $log = $this->auditLogService->getById($id);
            
            if ($log === null) {
                return ApiResponse::error('审计日志不存在', 404, 404);
            }
            
            return ApiResponse::success($log->toArray());
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('服务暂时不可用: ' . $e->getMessage(), 503, 503);
        }
    }
}
