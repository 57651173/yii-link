<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Response\ApiResponse;
use Application\Tenant\TenantContext;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * JWT 身份认证中间件
 *
 * 拦截所有需要认证的请求，从 Authorization 头中提取并验证 JWT Token。
 * 验证成功后，将用户信息注入请求属性，供后续 Controller 使用。
 *
 * 请求头格式：Authorization: Bearer {token}
 */
class AuthMiddleware implements MiddlewareInterface
{
    private string $secretKey;
    private string $algorithm;

    public function __construct(
        private readonly array $params,
        private readonly TenantContext $tenantContext,
    ) {
        $this->secretKey = $this->params['jwt']['secret_key'] ?? 'default-secret-key';
        $this->algorithm = $this->params['jwt']['algorithm'] ?? 'HS256';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return ApiResponse::unauthorized('缺少 Authorization 请求头');
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return ApiResponse::unauthorized('Authorization 格式错误，应为 Bearer {token}');
        }

        $token = substr($authHeader, 7);

        if (empty($token)) {
            return ApiResponse::unauthorized('Token 不能为空');
        }

        try {
            $payload = JWT::decode($token, new Key($this->secretKey, $this->algorithm));

            $defaultTenantId = (string)($this->params['default_tenant_id'] ?? '');
            $tidFromToken    = isset($payload->tid) ? (string)$payload->tid : '';
            $userAccount     = isset($payload->name) ? (string)$payload->name : ''; // 从 JWT 提取 account
            
            if ($tidFromToken !== '') {
                $this->tenantContext->setTenantId($tidFromToken);
            } elseif ($defaultTenantId !== '') {
                $this->tenantContext->setTenantId($defaultTenantId);
            }

            // 将解码后的用户信息注入请求属性，供 Controller 使用
            $request = $request
                ->withAttribute('auth_user_id', $payload->sub)
                ->withAttribute('auth_user_account', $userAccount)
                ->withAttribute('auth_tenant_id', $this->tenantContext->getTenantId())
                ->withAttribute('auth_payload', $payload);

            return $handler->handle($request);
        } catch (ExpiredException) {
            return ApiResponse::unauthorized('Token 已过期，请重新登录');
        } catch (SignatureInvalidException) {
            return ApiResponse::unauthorized('Token 签名无效');
        } catch (\Throwable) {
            return ApiResponse::unauthorized('Token 无效');
        }
    }
}
