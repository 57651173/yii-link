<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Response\ApiResponse;
use App\Service\RateLimiter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * API 限流中间件
 * 
 * 基于 Redis 实现三种限流策略：
 * 1. IP 限流（防止恶意攻击）
 * 2. 用户限流（防止单用户滥用）
 * 3. 租户限流（防止单租户滥用）
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    // 跳过限流的路径（健康检查等）
    private const SKIP_PATHS = [
        '/health',
        '/health/simple',
    ];
    
    // 默认限流配置（可从配置文件读取）
    private const LIMITS = [
        'ip' => ['max' => 100, 'decay' => 60],        // IP: 100次/分钟
        'user' => ['max' => 200, 'decay' => 60],      // 用户: 200次/分钟
        'tenant' => ['max' => 500, 'decay' => 60],    // 租户: 500次/分钟
    ];
    
    public function __construct(
        private readonly RateLimiter $rateLimiter,
    ) {
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        
        // 跳过不需要限流的路径
        foreach (self::SKIP_PATHS as $skipPath) {
            if ($path === $skipPath || str_starts_with($path, $skipPath)) {
                return $handler->handle($request);
            }
        }
        
        // 1. IP 限流（最宽松）
        $ip = $this->getClientIp($request);
        if ($ip !== null) {
            $ipResult = $this->rateLimiter->attempt(
                "ip:{$ip}",
                self::LIMITS['ip']['max'],
                self::LIMITS['ip']['decay']
            );
            
            if (!$ipResult['allowed']) {
                return $this->rateLimitResponse($ipResult, 'IP');
            }
        }
        
        // 2. 用户限流（如果已登录）
        $userId = $request->getAttribute('auth_user_id');
        if ($userId !== null) {
            $userResult = $this->rateLimiter->attempt(
                "user:{$userId}",
                self::LIMITS['user']['max'],
                self::LIMITS['user']['decay']
            );
            
            if (!$userResult['allowed']) {
                return $this->rateLimitResponse($userResult, 'User');
            }
        }
        
        // 3. 租户限流（如果有租户上下文）
        $tenantId = $request->getAttribute('auth_tenant_id');
        if ($tenantId !== null) {
            $tenantResult = $this->rateLimiter->attempt(
                "tenant:{$tenantId}",
                self::LIMITS['tenant']['max'],
                self::LIMITS['tenant']['decay']
            );
            
            if (!$tenantResult['allowed']) {
                return $this->rateLimitResponse($tenantResult, 'Tenant');
            }
        }
        
        // 执行请求
        $response = $handler->handle($request);
        
        // 添加限流响应头（使用 IP 限流信息）
        if (isset($ipResult)) {
            $response = $response
                ->withHeader('X-RateLimit-Limit', (string)self::LIMITS['ip']['max'])
                ->withHeader('X-RateLimit-Remaining', (string)$ipResult['remaining'])
                ->withHeader('X-RateLimit-Reset', (string)$ipResult['reset_at']);
        }
        
        return $response;
    }
    
    /**
     * 返回限流响应
     */
    private function rateLimitResponse(array $result, string $type): ResponseInterface
    {
        $response = ApiResponse::error(
            '请求过于频繁，请稍后重试',
            429,
            429,
            [
                'type' => $type,
                'retry_after' => $result['reset_at'] - time(),
            ]
        );
        
        return $response
            ->withHeader('X-RateLimit-Limit', (string)$result['remaining'])
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string)$result['reset_at'])
            ->withHeader('Retry-After', (string)($result['reset_at'] - time()));
    }
    
    /**
     * 获取客户端 IP
     */
    private function getClientIp(ServerRequestInterface $request): ?string
    {
        $serverParams = $request->getServerParams();
        
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];
        
        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ip = $serverParams[$header];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return null;
    }
}
