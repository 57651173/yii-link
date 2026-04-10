<?php

declare(strict_types=1);

namespace Application\User\Service;

use App\Exception\BusinessException;
use Application\User\DTO\CreateUserDTO;
use Application\User\DTO\UpdateUserDTO;
use Domain\User\Entity\User;
use Domain\User\Repository\UserRepositoryInterface;

/**
 * 用户应用服务
 *
 * 负责协调领域对象完成用户相关的业务流程。
 * Controller 只调用 Service，不直接操作数据。
 */
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * 创建新用户
     */
    public function create(CreateUserDTO $dto): User
    {
        if ($this->userRepository->emailExists($dto->email)) {
            throw new BusinessException('该邮箱已被注册', 422);
        }

        $user = new User(
            name: $dto->name,
            email: $dto->email,
            passwordHash: $this->hashPassword($dto->password),
        );

        return $this->userRepository->save($user);
    }

    /**
     * 根据 ID 获取用户
     */
    public function getById(int $id): User
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            throw new BusinessException('用户不存在', 404);
        }

        return $user;
    }

    /**
     * 获取用户列表
     */
    public function getList(int $page = 1, int $pageSize = 20): array
    {
        return $this->userRepository->findAll($page, $pageSize);
    }

    /**
     * 更新用户信息
     */
    public function update(int $id, UpdateUserDTO $dto): User
    {
        $user = $this->getById($id);

        if ($dto->name !== null) {
            $user = $user->rename($dto->name);
        }

        if ($dto->email !== null) {
            if ($this->userRepository->emailExists($dto->email, $id)) {
                throw new BusinessException('该邮箱已被使用', 422);
            }
            $user = $user->changeEmail($dto->email);
        }

        if ($dto->password !== null) {
            $user = $user->changePassword($this->hashPassword($dto->password));
        }

        return $this->userRepository->save($user);
    }

    /**
     * 删除用户
     */
    public function delete(int $id): void
    {
        $this->getById($id); // 先确认用户存在

        $this->userRepository->delete($id);
    }

    /**
     * 通过邮箱和密码验证用户（用于登录）
     */
    public function validateCredentials(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!$user->isActive()) {
            return null;
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            return null;
        }

        return $user;
    }

    /**
     * 哈希密码
     */
    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
