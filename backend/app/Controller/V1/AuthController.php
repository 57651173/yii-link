<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Response\ApiResponse;
use Application\User\Service\UserService;
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
        array $params = [],
    ) {
        $this->jwtSecret     = $params['jwt']['secret_key'] ?? 'default-secret';
        $this->jwtAlgorithm  = $params['jwt']['algorithm'] ?? 'HS256';
        $this->jwtExpireTime = $params['jwt']['expire_time'] ?? 86400;
    }

    /**
     * POST /api/v1/login
     *
     * 请求体：{ "email": "xxx@xxx.com", "password": "123456" }
     * 返回：{ "code": 0, "data": { "token": "..." } }
     */
    public function login(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody() ?? [];

        $email    = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if (empty($email) || empty($password)) {
            return ApiResponse::error('邮箱和密码不能为空', 422, 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error('邮箱格式不正确', 422, 422);
        }

        try {
            $user = $this->userService->validateCredentials($email, $password);
        } catch (\Throwable $e) {
            return ApiResponse::error('服务暂时不可用，请稍后重试: ' . $e->getMessage(), 503, 503);
        }

        if ($user === null) {
            return ApiResponse::error('邮箱或密码错误', 401, 401);
        }

        $now     = time();
        $payload = [
            'iss' => 'yii-link',
            'sub' => $user->getId(),
            'iat' => $now,
            'exp' => $now + $this->jwtExpireTime,
            'email' => $user->getEmail(),
            'name'  => $user->getName(),
        ];

        $token = JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);

        return ApiResponse::success([
            'token'      => $token,
            'expires_in' => $this->jwtExpireTime,
            'user'       => $user->toArray(),
        ], '登录成功');
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
}
