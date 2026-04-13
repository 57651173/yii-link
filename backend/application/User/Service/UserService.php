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
     * 创建新用户（支持数组参数，用于租户初始化）
     *
     * @param array{account: string, email: string, password: string, nickname?: string, status?: string} $data
     */
    public function createUser(array $data): User
    {
        $account = trim($data['account'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');

        if (empty($account) || empty($email) || empty($password)) {
            throw new BusinessException('账号、邮箱和密码不能为空', 422);
        }

        // 检查账号是否已存在
        if ($this->userRepository->accountExists($account)) {
            throw new BusinessException('该账号已被注册', 422);
        }

        if ($this->userRepository->emailExists($email)) {
            throw new BusinessException('该邮箱已被注册', 422);
        }

        $user = new User(
            name: $account,
            email: $email,
            passwordHash: $this->hashPassword($password),
            status: $data['status'] ?? User::STATUS_ACTIVE,
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
     * 根据 account 获取用户
     */
    public function getByAccount(string $account): User
    {
        $user = $this->userRepository->findByAccount($account);

        if ($user === null) {
            throw new BusinessException('用户不存在', 404);
        }

        return $user;
    }

    /**
     * 更新用户（通过 account）
     */
    public function updateByAccount(string $account, UpdateUserDTO $dto): User
    {
        $user = $this->getByAccount($account);

        if ($dto->name !== null) {
            $user = $user->rename($dto->name);
        }

        if ($dto->email !== null) {
            if ($this->userRepository->emailExists($dto->email)) {
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
     * 删除用户（通过 account）
     */
    public function deleteByAccount(string $account): void
    {
        $this->getByAccount($account); // 先确认用户存在

        $this->userRepository->deleteByAccount($account);
    }

    /**
     * 禁用用户
     */
    public function disable(string $account): User
    {
        $user = $this->getByAccount($account);

        if ($user->getStatus() === User::STATUS_BANNED) {
            throw new BusinessException('用户已被禁用', 422);
        }

        $user = $user->ban();
        return $this->userRepository->save($user);
    }

    /**
     * 启用用户
     */
    public function enable(string $account): User
    {
        $user = $this->getByAccount($account);

        if ($user->getStatus() === User::STATUS_ACTIVE) {
            throw new BusinessException('用户已是激活状态', 422);
        }

        $user = $user->activate();
        return $this->userRepository->save($user);
    }

    /**
     * 重置密码
     */
    public function resetPassword(string $account, string $newPassword): User
    {
        if (strlen($newPassword) < 6) {
            throw new BusinessException('密码长度不能少于6位', 422);
        }

        $user = $this->getByAccount($account);
        $user = $user->changePassword($this->hashPassword($newPassword));

        return $this->userRepository->save($user);
    }

    /**
     * 通过邮箱/账号和密码验证用户（用于登录）
     * 
     * @param string $credential 邮箱或账号
     * @param string $password 密码
     * @param ?string $tenantId 租户主键；非空时仅在该租户内校验；为空时依赖库表全局唯一 account/email（见迁移 M240103000000）
     * @return User|null 验证成功返回用户对象，失败返回 null
     */
    public function validateCredentials(string $credential, string $password, ?string $tenantId = null): ?User
    {
        $user = $this->userRepository->findByEmailOrAccount($credential, $tenantId);

        if ($user === null) {
            return null;
        }

        // 检查用户状态
        if (!$user->isActive()) {
            return null;
        }

        // 验证密码
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
