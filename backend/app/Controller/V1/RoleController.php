<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Exception\BusinessException;
use App\Response\ApiResponse;
use Application\Rbac\Service\RoleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 角色管理控制器
 */
class RoleController
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {
    }

    /**
     * GET /api/v1/roles
     *
     * 获取角色列表（当前租户）
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = (int)($query['page'] ?? 1);
        $pageSize = (int)($query['page_size'] ?? 20);

        try {
            $result = $this->roleService->getList($page, $pageSize);

            return ApiResponse::success([
                'items' => array_map(fn($role) => $role->toArray(), $result['items']),
                'total' => $result['total'],
                'page' => $page,
                'page_size' => $pageSize,
                'pages' => (int)ceil($result['total'] / $pageSize),
            ]);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * GET /api/v1/roles/{id}
     *
     * 获取角色详情
     */
    public function view(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int)($args['id'] ?? 0);

        try {
            $role = $this->roleService->getById($id);
            return ApiResponse::success($role->toArray());
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * POST /api/v1/roles
     *
     * 创建角色
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody() ?? [];

        try {
            $role = $this->roleService->create($body);
            return ApiResponse::success($role->toArray(), '角色创建成功', 201);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * PUT /api/v1/roles/{id}
     *
     * 更新角色
     */
    public function update(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int)($args['id'] ?? 0);
        $body = $request->getParsedBody() ?? [];

        try {
            $role = $this->roleService->update($id, $body);
            return ApiResponse::success($role->toArray(), '角色更新成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * DELETE /api/v1/roles/{id}
     *
     * 删除角色
     */
    public function delete(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int)($args['id'] ?? 0);

        try {
            $this->roleService->delete($id);
            return ApiResponse::success(null, '角色删除成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * GET /api/v1/roles/{id}/permissions
     *
     * 获取角色的权限列表
     */
    public function permissions(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int)($args['id'] ?? 0);

        try {
            $permissions = $this->roleService->getRolePermissions($id);
            return ApiResponse::success($permissions);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * PUT /api/v1/roles/{id}/permissions
     *
     * 为角色分配权限
     * 请求体：{ "permission_ids": [1, 2, 3] }
     */
    public function assignPermissions(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int)($args['id'] ?? 0);
        $body = $request->getParsedBody() ?? [];
        $permissionIds = $body['permission_ids'] ?? [];

        if (!is_array($permissionIds)) {
            return ApiResponse::error('permission_ids 必须是数组', 422, 422);
        }

        try {
            $this->roleService->assignPermissions($id, $permissionIds);
            return ApiResponse::success(null, '权限分配成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }
}
