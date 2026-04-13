<?php

declare(strict_types=1);

namespace App\Controller;

use App\Response\ApiResponse;
use Domain\User\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 平台管理员权限检查 Trait
 *
 * 用于需要平台权限的控制器（如 TenantController）。
 * 平台权限不走 RBAC，而是检查 users.is_platform_admin 字段。
 */
trait RequiresPlatformAdmin
{
    /**
     * 检查当前用户是否为平台管理员
     */
    protected function requirePlatformAdmin(ServerRequestInterface $request, UserRepositoryInterface $userRepository): ?ResponseInterface
    {
        $userId = $request->getAttribute('auth_user_id');
        
        if ($userId === null) {
            return ApiResponse::unauthorized('未登录');
        }

        $user = $userRepository->findById((int)$userId);
        
        if ($user === null) {
            return ApiResponse::unauthorized('用户不存在');
        }

        // 检查 is_platform_admin 字段（不是 RBAC 角色）
        if (!$this->isPlatformAdmin($user)) {
            return ApiResponse::forbidden('仅平台管理员可访问此接口');
        }

        return null;
    }

    /**
     * 判断用户是否为平台管理员
     */
    private function isPlatformAdmin($user): bool
    {
        if (method_exists($user, 'isPlatformAdmin')) {
            return $user->isPlatformAdmin();
        }

        if (method_exists($user, 'toArray')) {
            $data = $user->toArray();
            return !empty($data['is_platform_admin']);
        }

        return false;
    }
}
