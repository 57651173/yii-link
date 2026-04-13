<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Exception\BusinessException;
use App\Response\ApiResponse;
use Application\Rbac\Service\UserRoleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 用户角色管理控制器
 */
class UserRoleController
{
    public function __construct(
        private readonly UserRoleService $userRoleService,
    ) {
    }

    /**
     * PUT /api/v1/users/{account}/roles
     *
     * 为用户分配角色
     * 请求体：{ "role_ids": [1, 2, 3] }
     */
    public function assign(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $userAccount = $args['account'] ?? '';
        $body = $request->getParsedBody() ?? [];
        $roleIds = $body['role_ids'] ?? [];

        if (empty($userAccount)) {
            return ApiResponse::error('用户账号不能为空', 422, 422);
        }

        if (!is_array($roleIds)) {
            return ApiResponse::error('role_ids 必须是数组', 422, 422);
        }

        try {
            $this->userRoleService->assignRoles($userAccount, $roleIds);
            return ApiResponse::success(null, '角色分配成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * GET /api/v1/users/{account}/roles
     *
     * 获取用户的角色列表
     */
    public function index(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $userAccount = $args['account'] ?? '';

        if (empty($userAccount)) {
            return ApiResponse::error('用户账号不能为空', 422, 422);
        }

        try {
            $roleIds = $this->userRoleService->getUserRoleIds($userAccount);
            return ApiResponse::success(['role_ids' => $roleIds]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * DELETE /api/v1/users/{account}/roles/{roleId}
     *
     * 移除用户的指定角色
     */
    public function remove(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $userAccount = $args['account'] ?? '';
        $roleId = (int)($args['roleId'] ?? 0);

        if (empty($userAccount)) {
            return ApiResponse::error('用户账号不能为空', 422, 422);
        }

        try {
            $this->userRoleService->removeRole($userAccount, $roleId);
            return ApiResponse::success(null, '角色移除成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }
}
