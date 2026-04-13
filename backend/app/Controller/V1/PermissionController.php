<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Exception\BusinessException;
use App\Response\ApiResponse;
use Application\Rbac\Service\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 权限管理控制器
 */
class PermissionController
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    /**
     * GET /api/v1/permissions
     *
     * 获取所有权限列表
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $permissions = $this->permissionService->getAllPermissions();
            return ApiResponse::success($permissions);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * GET /api/v1/permissions/tree
     *
     * 获取权限树形结构
     */
    public function tree(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $tree = $this->permissionService->getPermissionTree();
            return ApiResponse::success($tree);
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * GET /api/v1/permissions/{id}
     *
     * 获取权限详情
     */
    public function view(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int)($args['id'] ?? 0);

        try {
            $permission = $this->permissionService->getById($id);
            return ApiResponse::success($permission);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }
}
