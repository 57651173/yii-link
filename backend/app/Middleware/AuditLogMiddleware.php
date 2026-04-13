<?php

declare(strict_types=1);

namespace App\Middleware;

use Application\AuditLog\Service\AuditLogService;
use Application\Tenant\TenantContext;
use Domain\AuditLog\Entity\AuditLog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 审计日志中间件
 * 
 * 自动记录所有 CUD 操作（POST, PUT, DELETE, PATCH）。
 * 
 * 注意：
 * - 只记录需要认证的操作（已通过 AuthMiddleware）
 * - 登录操作需要在 AuthController 中手动记录
 */
class AuditLogMiddleware implements MiddlewareInterface
{
    // 不需要记录的路径
    private const SKIP_PATHS = [
        '/api/v1/login',      // 登录在 Controller 中手动记录
        '/api/v1/audit-logs', // 查询审计日志本身不需要记录
    ];
    
    // HTTP 方法到操作类型的映射
    private const METHOD_ACTION_MAP = [
        'POST' => AuditLog::ACTION_CREATE,
        'PUT' => AuditLog::ACTION_UPDATE,
        'PATCH' => AuditLog::ACTION_UPDATE,
        'DELETE' => AuditLog::ACTION_DELETE,
    ];
    
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly TenantContext $tenantContext,
    ) {
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        
        // 只记录 CUD 操作
        if (!isset(self::METHOD_ACTION_MAP[$method])) {
            return $handler->handle($request);
        }
        
        // 跳过不需要记录的路径
        foreach (self::SKIP_PATHS as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return $handler->handle($request);
            }
        }
        
        // 执行请求
        $response = $handler->handle($request);
        
        // 只有成功响应才记录（2xx）
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            return $response;
        }
        
        // 异步记录审计日志（不影响响应）
        try {
            $this->recordAuditLog($request, $response);
        } catch (\Throwable $e) {
            // 审计日志记录失败不应该影响主流程
            error_log("Failed to record audit log: " . $e->getMessage());
        }
        
        return $response;
    }
    
    private function recordAuditLog(ServerRequestInterface $request, ResponseInterface $response): void
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $action = self::METHOD_ACTION_MAP[$method];
        
        // 从请求中获取用户信息（由 AuthMiddleware 注入）
        $userId = $request->getAttribute('auth_user_id');
        $userAccount = $request->getAttribute('auth_user_account');
        
        // 推断资源类型和资源 ID
        [$resourceType, $resourceId] = $this->inferResource($path, $request);
        
        // 获取 IP 地址
        $ipAddress = $this->getClientIp($request);
        
        // 获取 User-Agent
        $userAgent = $request->getHeaderLine('User-Agent');
        
        // 获取变更数据（仅对 PUT/PATCH 记录）
        $newValues = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $body = $request->getParsedBody();
            if (is_array($body)) {
                // 过滤敏感字段
                $newValues = $this->filterSensitiveData($body);
            }
        }
        
        // 记录审计日志
        $this->auditLogService->log([
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'http_method' => $method,
            'http_path' => $path,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'user_id' => $userId,
            'user_account' => $userAccount,
            'new_values' => $newValues,
        ]);
    }
    
    /**
     * 从路径推断资源类型和资源 ID
     * 
     * @return array{0: string, 1: string|null}
     */
    private function inferResource(string $path, ServerRequestInterface $request): array
    {
        // 移除 /api/v1/ 前缀
        $path = preg_replace('#^/api/v\d+/#', '', $path);
        
        // 解析路径
        $parts = explode('/', trim($path, '/'));
        
        if (empty($parts)) {
            return ['unknown', null];
        }
        
        $resourceType = $parts[0]; // 如 users, roles, tenants
        $resourceId = $parts[1] ?? null; // 如 account, role_id
        
        // 如果是子资源操作（如 /users/123/reset-password），使用主资源类型
        if (count($parts) > 2) {
            $resourceId = $parts[1];
        }
        
        return [$resourceType, $resourceId];
    }
    
    /**
     * 获取客户端真实 IP
     */
    private function getClientIp(ServerRequestInterface $request): ?string
    {
        $serverParams = $request->getServerParams();
        
        // 优先从代理头获取
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ip = $serverParams[$header];
                // 如果是逗号分隔的多个 IP，取第一个
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return null;
    }
    
    /**
     * 过滤敏感数据
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = ['password', 'password_hash', 'token', 'secret', 'api_key'];
        
        $filtered = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $filtered[$key] = '***';
            } else {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }
}
