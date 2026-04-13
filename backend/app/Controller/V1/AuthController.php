<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Exception\BusinessException;
use App\Response\ApiResponse;
use Application\AuditLog\Service\AuditLogService;
use Application\Tenant\TenantResolver;
use Application\User\Service\UserService;
use Domain\AuditLog\Entity\AuditLog;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 认证控制器
 *
 * 处理用户登录、登出和 Token 刷新。
 */
class AuthController
{
    private string $jwtSecret;
    private string $jwtAlgorithm;
    private int $jwtExpireTime;

    public function __construct(
        private readonly UserService $userService,
        private readonly TenantResolver $tenantResolver,
        private readonly AuditLogService $auditLogService,
        array $params = [],
    ) {
        $this->jwtSecret     = $params['jwt']['secret_key'] ?? 'default-secret';
        $this->jwtAlgorithm  = $params['jwt']['algorithm'] ?? 'HS256';
        $this->jwtExpireTime = $params['jwt']['expire_time'] ?? 86400;
    }

    /**
     * POST /api/v1/login
     *
     * 支持邮箱或账号登录
     * 请求体：{ "username": "admin", "password": "123456" }
     * 或：{ "username": "admin@yii-link.com", "password": "123456" }
     * 返回：{ "code": 0, "data": { "token": "..." } }
     */
    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody() ?? [];

        // 支持 username 或 email/account 字段
        $username = trim((string)($body['username'] ?? $body['email'] ?? $body['account'] ?? ''));
        $password = (string)($body['password'] ?? '');

        // 验证必填项
        if (empty($username) || empty($password)) {
            return ApiResponse::error('用户名和密码不能为空', 422, 422);
        }

        // 验证凭证长度
        if (mb_strlen($username) < 2) {
            return ApiResponse::error('用户名长度不能少于2个字符', 422, 422);
        }

        if (mb_strlen($password) < 6) {
            return ApiResponse::error('密码长度不能少于6位', 422, 422);
        }

        $tenantCode = trim((string)($body['tenant_code'] ?? ''));
        $tenantId   = null;
        if ($tenantCode !== '') {
            $tenantId = $this->tenantResolver->resolveIdByCode($tenantCode);
            if ($tenantId === null) {
                return ApiResponse::error('租户不存在或已停用', 404, 404);
            }
        }

        // 验证用户凭证
        try {
            $user = $this->userService->validateCredentials($username, $password, $tenantId);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('服务暂时不可用，请稍后重试: ' . $e->getMessage(), 503, 503);
        }

        if ($user === null) {
            // 记录登录失败
            $this->recordLoginFailure($request, $username, $tenantId, '用户名或密码错误');
            return ApiResponse::error('用户名或密码错误', 401, 401);
        }

        $tenantKey = $user->getTenantId();
        if ($tenantKey === null || $tenantKey === '') {
            return ApiResponse::error('用户数据异常：缺少租户信息', 500, 500);
        }

        // 生成 JWT Token
        $now     = time();
        $payload = [
            'iss' => 'yii-link',
            'sub' => $user->getId(),
            'iat' => $now,
            'exp' => $now + $this->jwtExpireTime,
            'tid' => $tenantKey,
            'email' => $user->getEmail(),
            'name'  => $user->getName(),
        ];

        $token = JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);

        // 记录登录成功
        $this->recordLoginSuccess($request, $user);

        return ApiResponse::success([
            'token'      => $token,
            'expires_in' => $this->jwtExpireTime,
            'user'       => $user->toArray(),
        ], '登录成功');
    }

    /**
     * 记录登录成功的审计日志
     */
    private function recordLoginSuccess(ServerRequestInterface $request, $user): void
    {
        try {
            $this->auditLogService->log([
                'tenant_id' => $user->getTenantId(),
                'user_id' => $user->getId(),
                'user_account' => $user->getName(),
                'action' => AuditLog::ACTION_LOGIN,
                'resource_type' => 'auth',
                'resource_id' => $user->getName(),
                'http_method' => $request->getMethod(),
                'http_path' => $request->getUri()->getPath(),
                'ip_address' => $this->getClientIp($request),
                'user_agent' => $request->getHeaderLine('User-Agent'),
                'new_values' => [
                    'login_time' => date('Y-m-d H:i:s'),
                    'status' => 'success',
                ],
            ]);
        } catch (\Throwable $e) {
            // 审计日志记录失败不应影响登录流程
            error_log("Failed to record login audit log: " . $e->getMessage());
        }
    }

    /**
     * 记录登录失败的审计日志
     */
    private function recordLoginFailure(
        ServerRequestInterface $request,
        string $username,
        ?string $tenantId,
        string $reason
    ): void {
        try {
            // 使用默认租户或提供的租户ID
            $tid = $tenantId ?? md5('sys_tenant_default');
            
            $this->auditLogService->logFailed([
                'tenant_id' => $tid,
                'user_account' => $username,
                'action' => AuditLog::ACTION_LOGIN,
                'resource_type' => 'auth',
                'resource_id' => $username,
                'http_method' => $request->getMethod(),
                'http_path' => $request->getUri()->getPath(),
                'ip_address' => $this->getClientIp($request),
                'user_agent' => $request->getHeaderLine('User-Agent'),
            ], $reason);
        } catch (\Throwable $e) {
            // 审计日志记录失败不应影响响应
            error_log("Failed to record login failure audit log: " . $e->getMessage());
        }
    }

    /**
     * 获取客户端真实 IP
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

    /**
     * GET /api/v1/me
     *
     * 获取当前登录用户信息（需要 JWT 认证）
     */
    public function me(ServerRequestInterface $request): ResponseInterface
    {
        $userId = $request->getAttribute('auth_user_id');

        $user = $this->userService->getById((int)$userId);

        return ApiResponse::success($user->toArray());
    }
    
    /**
     * POST /api/v1/refresh
     * 
     * 刷新 JWT Token
     * 
     * 允许在 Token 过期后 7 天内刷新
     */
    public function refresh(ServerRequestInterface $request): ResponseInterface
    {
        // 1. 从 Header 获取旧 Token
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return ApiResponse::error('缺少 Authorization Header', 401, 401);
        }
        
        $oldToken = substr($authHeader, 7);
        
        try {
            // 2. 解析旧 Token（允许过期）
            JWT::$leeway = 7 * 86400; // 允许 7 天的宽限期
            $decoded = JWT::decode($oldToken, new \Firebase\JWT\Key($this->jwtSecret, $this->jwtAlgorithm));
            JWT::$leeway = 0; // 重置
            
            // 3. 检查是否过期太久
            if (time() - $decoded->exp > 7 * 86400) {
                return ApiResponse::error('Token 已过期太久，请重新登录', 401, 401);
            }
            
            // 4. 验证用户是否还存在且状态正常
            $user = $this->userService->getById((int)$decoded->sub);
            
            if ($user->getStatus() !== 'active') {
                return ApiResponse::error('用户状态异常，无法刷新 Token', 401, 401);
            }
            
            // 5. 生成新 Token
            $now = time();
            $payload = [
                'iss' => 'yii-link',
                'sub' => $user->getId(),
                'iat' => $now,
                'exp' => $now + $this->jwtExpireTime,
                'tid' => $user->getTenantId(),
                'account' => $user->getName(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ];
            
            $newToken = JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
            
            return ApiResponse::success([
                'token' => $newToken,
                'expires_in' => $this->jwtExpireTime,
                'refreshed_at' => date('Y-m-d H:i:s'),
            ], 'Token 刷新成功');
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            // Token 过期（但在 7 天内）
            return ApiResponse::error('Token 已过期，但在宽限期内，请联系管理员', 401, 401);
        } catch (\Exception $e) {
            return ApiResponse::error('Token 无效：' . $e->getMessage(), 401, 401);
        }
    }
}
