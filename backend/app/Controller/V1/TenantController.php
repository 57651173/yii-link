<?php

declare(strict_types=1);

namespace App\Controller\V1;

use App\Controller\RequiresPlatformAdmin;
use App\Exception\BusinessException;
use App\Response\ApiResponse;
use Application\Tenant\Service\TenantService;
use Domain\User\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 租户管理控制器（仅平台管理员可访问）
 */
class TenantController
{
    use RequiresPlatformAdmin;

    public function __construct(
        private readonly TenantService $tenantService,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * GET /api/v1/tenants
     *
     * 获取租户列表（分页）
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // 检查平台管理员权限
        $error = $this->requirePlatformAdmin($request, $this->userRepository);
        if ($error !== null) {
            return $error;
        }

        $query = $request->getQueryParams();
        $page = (int)($query['page'] ?? 1);
        $pageSize = (int)($query['page_size'] ?? 20);

        try {
            $result = $this->tenantService->getList($page, $pageSize);

            return ApiResponse::success([
                'items' => array_map(fn($tenant) => $tenant->toArray(), $result['items']),
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
     * GET /api/v1/tenants/{id}
     *
     * 获取租户详情
     */
    public function view(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $error = $this->requirePlatformAdmin($request, $this->userRepository);
        if ($error !== null) {
            return $error;
        }

        $id = $args['id'] ?? '';

        try {
            $tenant = $this->tenantService->getById($id);
            return ApiResponse::success($tenant->toArray());
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * POST /api/v1/tenants
     *
     * 创建租户
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $error = $this->requirePlatformAdmin($request, $this->userRepository);
        if ($error !== null) {
            return $error;
        }

        $body = $request->getParsedBody() ?? [];

        try {
            $tenant = $this->tenantService->create($body);
            return ApiResponse::success($tenant->toArray(), '租户创建成功', 201);
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * PUT /api/v1/tenants/{id}
     *
     * 更新租户
     */
    public function update(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $error = $this->requirePlatformAdmin($request, $this->userRepository);
        if ($error !== null) {
            return $error;
        }

        $id = $args['id'] ?? '';
        $body = $request->getParsedBody() ?? [];

        try {
            $tenant = $this->tenantService->update($id, $body);
            return ApiResponse::success($tenant->toArray(), '租户更新成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * DELETE /api/v1/tenants/{id}
     *
     * 删除租户（软删除）
     */
    public function delete(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $error = $this->requirePlatformAdmin($request, $this->userRepository);
        if ($error !== null) {
            return $error;
        }

        $id = $args['id'] ?? '';

        try {
            $this->tenantService->delete($id);
            return ApiResponse::success(null, '租户删除成功');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * PATCH /api/v1/tenants/{id}/disable
     *
     * 禁用租户
     */
    public function disable(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $error = $this->requirePlatformAdmin($request, $this->userRepository);
        if ($error !== null) {
            return $error;
        }

        $id = $args['id'] ?? '';

        try {
            $tenant = $this->tenantService->disable($id);
            return ApiResponse::success($tenant->toArray(), '租户已禁用');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }

    /**
     * PATCH /api/v1/tenants/{id}/enable
     *
     * 启用租户
     */
    public function enable(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $error = $this->requirePlatformAdmin($request, $this->userRepository);
        if ($error !== null) {
            return $error;
        }

        $id = $args['id'] ?? '';

        try {
            $tenant = $this->tenantService->enable($id);
            return ApiResponse::success($tenant->toArray(), '租户已启用');
        } catch (BusinessException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 500, 500);
        }
    }
}
