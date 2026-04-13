<?php

declare(strict_types=1);

namespace Application\Rbac\Service;

use App\Exception\BusinessException;
use Domain\Rbac\Repository\RbacRepositoryInterface;

/**
 * 权限管理服务
 */
class PermissionService
{
    public function __construct(
        private readonly RbacRepositoryInterface $rbacRepository,
    ) {
    }

    /**
     * 获取所有权限列表（树形结构或平铺）
     *
     * @return array
     */
    public function getAllPermissions(): array
    {
        $permissions = $this->rbacRepository->findAllPermissions();

        return array_map(fn($permission) => $permission->toArray(), $permissions);
    }

    /**
     * 获取权限详情
     */
    public function getById(int $id): array
    {
        $permission = $this->rbacRepository->findPermissionById($id);
        if ($permission === null) {
            throw new BusinessException('权限不存在', 404);
        }

        return $permission->toArray();
    }

    /**
     * 构建权限树（父子结构）
     *
     * @return array
     */
    public function getPermissionTree(): array
    {
        $permissions = $this->rbacRepository->findAllPermissions();
        
        $map = [];
        $tree = [];

        foreach ($permissions as $permission) {
            $item = $permission->toArray();
            $item['children'] = [];
            $map[$item['id']] = &$item;
            unset($item);
        }

        foreach ($map as $id => &$item) {
            if ($item['parent_id'] === null) {
                $tree[] = &$item;
            } else {
                if (isset($map[$item['parent_id']])) {
                    $map[$item['parent_id']]['children'][] = &$item;
                }
            }
        }

        return $tree;
    }
}
