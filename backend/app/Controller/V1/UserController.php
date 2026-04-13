<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Exception\BusinessException;
use App\Response\ApiResponse;
use Application\User\DTO\CreateUserDTO;
use Application\User\DTO\UpdateUserDTO;
use Application\User\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 用户管理控制器（使用 account 作为标识符）
 *
 * 提供标准 RESTful 风格的用户增删改查接口。
 * Controller 不包含任何业务逻辑，所有操作委托给 UserService。
 * 
 * 注意：用户操作统一使用 account（账号）而不是 id
 */
class UserController
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    /**
     * GET /api/v1/users
     *
     * 获取用户列表（分页）
     * 查询参数：page=1&page_size=20
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $query    = $request->getQueryParams();
        $page     = max(1, (int)($query['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($query['page_size'] ?? 20)));

        try {
            $result = $this->userService->getList($page, $pageSize);
            $items  = array_map(fn($u) => $u->toArray(), $result['items']);
            return ApiResponse::paginate($items, $result['total'], $page, $pageSize);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error('服务暂时不可用: ' . $e->getMessage(), 503, 503);
        }
    }

    /**
     * GET /api/v1/users/{account}
     *
     * 获取单个用户详情
     */
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $account = (string)$request->getAttribute('account');

        try {
            $user = $this->userService->getByAccount($account);
            return ApiResponse::success($user->toArray());
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * POST /api/v1/users
     *
     * 创建新用户
     * 请求体：{ "account": "zhangsan", "email": "zhangsan@example.com", "password": "123456" }
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody() ?? [];

        $account  = trim((string)($body['account'] ?? ''));
        $email    = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if (empty($account)) {
            return ApiResponse::error('账号不能为空', 422, 422);
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error('邮箱格式不正确', 422, 422);
        }

        if (strlen($password) < 6) {
            return ApiResponse::error('密码长度不能少于6位', 422, 422);
        }

        try {
            $user = $this->userService->createUser([
                'account' => $account,
                'email' => $email,
                'password' => $password,
                'nickname' => $body['nickname'] ?? null,
                'status' => User::STATUS_ACTIVE,
            ]);

            return ApiResponse::success($user->toArray(), '用户创建成功', 201);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * PUT /api/v1/users/{account}
     *
     * 更新用户信息
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $account = (string)$request->getAttribute('account');
        $body = $request->getParsedBody() ?? [];

        $name     = isset($body['name']) ? trim((string)$body['name']) : null;
        $email    = isset($body['email']) ? trim((string)$body['email']) : null;
        $password = isset($body['password']) ? (string)$body['password'] : null;

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error('邮箱格式不正确', 422, 422);
        }

        if ($password !== null && strlen($password) < 6) {
            return ApiResponse::error('密码长度不能少于6位', 422, 422);
        }

        try {
            $dto  = new UpdateUserDTO($name, $email, $password);
            $user = $this->userService->updateByAccount($account, $dto);

            return ApiResponse::success($user->toArray(), '用户更新成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * DELETE /api/v1/users/{account}
     *
     * 删除用户
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $account = (string)$request->getAttribute('account');

        try {
            $this->userService->deleteByAccount($account);
            return ApiResponse::success(null, '用户删除成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * PATCH /api/v1/users/{account}/disable
     *
     * 禁用用户
     */
    public function disable(ServerRequestInterface $request): ResponseInterface
    {
        $account = (string)$request->getAttribute('account');

        try {
            $user = $this->userService->disable($account);
            return ApiResponse::success($user->toArray(), '用户已禁用');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * PATCH /api/v1/users/{account}/enable
     *
     * 启用用户
     */
    public function enable(ServerRequestInterface $request): ResponseInterface
    {
        $account = (string)$request->getAttribute('account');

        try {
            $user = $this->userService->enable($account);
            return ApiResponse::success($user->toArray(), '用户已启用');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * POST /api/v1/users/{account}/reset-password
     *
     * 重置密码
     */
    public function resetPassword(ServerRequestInterface $request): ResponseInterface
    {
        $account = (string)$request->getAttribute('account');
        $body = $request->getParsedBody() ?? [];

        $newPassword = (string)($body['password'] ?? '');

        if (empty($newPassword)) {
            return ApiResponse::error('新密码不能为空', 422, 422);
        }

        if (strlen($newPassword) < 6) {
            return ApiResponse::error('密码长度不能少于6位', 422, 422);
        }

        try {
            $user = $this->userService->resetPassword($account, $newPassword);
            return ApiResponse::success($user->toArray(), '密码重置成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }
}
