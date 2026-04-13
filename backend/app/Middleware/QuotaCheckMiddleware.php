<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Response\ApiResponse;
use App\Service\QuotaService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 配额检查中间件
 * 
 * 检查租户配额使用情况，防止超限。
 */
class QuotaCheckMiddleware implements MiddlewareInterface
{
    // 需要检查用户配额的操作
    private const USER_QUOTA_PATHS = [
        '/api/v1/users',  // POST - 创建用户
    ];
    
    // 需要检查 API 配额的路径（排除公开接口）
    private const SKIP_API_QUOTA_PATHS = [
        '/health',
        '/health/simple',
        '/api/v1/login',
    ];
    
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        
        // 1. 检查用户数配额（创建用户时）
        if ($method === 'POST' && in_array($path, self::USER_QUOTA_PATHS)) {
            $userQuotaResult = $this->quotaService->checkUserQuota();
            
            if (!$userQuotaResult['allowed']) {
                return ApiResponse::error(
                    $userQuotaResult['message'],
                    429,
                    429,
                    [
                        'quota_type' => 'users',
                        'current' => $userQuotaResult['current'] ?? 0,
                        'quota' => $userQuotaResult['quota'] ?? 0,
                    ]
                );
            }
        }
        
        // 2. 检查 API 调用配额（所有接口，除了公开接口）
        $skipApiQuota = false;
        foreach (self::SKIP_API_QUOTA_PATHS as $skipPath) {
            if ($path === $skipPath || str_starts_with($path, $skipPath)) {
                $skipApiQuota = true;
                break;
            }
        }
        
        if (!$skipApiQuota) {
            $apiQuotaResult = $this->quotaService->checkApiQuota();
            
            if (!$apiQuotaResult['allowed']) {
                return ApiResponse::error(
                    $apiQuotaResult['message'],
                    429,
                    429,
                    [
                        'quota_type' => 'api_calls',
                        'quota' => $apiQuotaResult['quota'] ?? 0,
                        'reset_at' => $apiQuotaResult['reset_at'] ?? 0,
                    ]
                );
            }
        }
        
        return $handler->handle($request);
    }
}
