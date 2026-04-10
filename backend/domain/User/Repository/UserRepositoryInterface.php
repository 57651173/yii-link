<?php

declare(strict_types=1);

namespace Domain\User\Repository;

use Domain\User\Entity\User;

/**
 * 用户仓储接口
 *
 * 定义用户数据访问的契约，具体实现在 Infrastructure 层。
 * 遵循依赖倒置原则，Domain 层不依赖具体实现。
 */
interface UserRepositoryInterface
{
    /**
     * 通过 ID 查找用户
     */
    public function findById(int $id): ?User;

    /**
     * 通过邮箱查找用户
     */
    public function findByEmail(string $email): ?User;

    /**
     * 获取所有用户列表（支持分页）
     *
     * @return array{items: User[], total: int}
     */
    public function findAll(int $page = 1, int $pageSize = 20): array;

    /**
     * 保存用户（新增或更新）
     */
    public function save(User $user): User;

    /**
     * 删除用户
     */
    public function delete(int $id): bool;

    /**
     * 判断邮箱是否已被使用
     */
    public function emailExists(string $email, ?int $excludeId = null): bool;
}
