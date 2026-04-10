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
 * 用户管理控制器
 *
 * 提供标准 RESTful 风格的用户增删改查接口。
 * Controller 不包含任何业务逻辑，所有操作委托给 UserService。
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
     * GET /api/v1/users/{id}
     *
     * 获取单个用户详情
     */
    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        try {
            $user = $this->userService->getById($id);
            return ApiResponse::success($user->toArray());
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * POST /api/v1/users
     *
     * 创建新用户
     * 请求体：{ "name": "张三", "email": "zhangsan@example.com", "password": "123456" }
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody() ?? [];

        $name     = trim((string)($body['name'] ?? ''));
        $email    = trim((string)($body['email'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if (empty($name)) {
            return ApiResponse::error('用户名不能为空', 422, 422);
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error('邮箱格式不正确', 422, 422);
        }

        if (strlen($password) < 6) {
            return ApiResponse::error('密码长度不能少于6位', 422, 422);
        }

        try {
            $dto  = new CreateUserDTO($name, $email, $password);
            $user = $this->userService->create($dto);

            return ApiResponse::success($user->toArray(), '用户创建成功', 201);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * PUT /api/v1/users/{id}
     *
     * 更新用户信息
     */
    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $id   = (int)$request->getAttribute('id');
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
            $user = $this->userService->update($id, $dto);

            return ApiResponse::success($user->toArray(), '用户更新成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }

    /**
     * DELETE /api/v1/users/{id}
     *
     * 删除用户
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int)$request->getAttribute('id');

        try {
            $this->userService->delete($id);
            return ApiResponse::success(null, '用户删除成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        }
    }
}
