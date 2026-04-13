<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\User;

use Application\Tenant\TenantContext;
use Domain\User\Entity\User;
use Domain\User\Repository\UserRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 用户仓储的数据库实现（按 {@see TenantContext} 做运行时租户隔离）。
 */
class DbUserRepository implements UserRepositoryInterface
{
    private const TABLE = 'sys_users';

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function findById(int $id): ?User
    {
        $tid = $this->tenantContext->requireTenantId();

        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE id = :id AND tenant_id = :tid AND deleted_at IS NULL LIMIT 1',
            [':id' => $id, ':tid' => $tid]
        )->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $tid = $this->tenantContext->requireTenantId();

        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE tenant_id = :tid AND email = :email AND deleted_at IS NULL LIMIT 1',
            [':tid' => $tid, ':email' => $email]
        )->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByAccount(string $account): ?User
    {
        $tid = $this->tenantContext->requireTenantId();

        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE tenant_id = :tid AND account = :account AND deleted_at IS NULL LIMIT 1',
            [':tid' => $tid, ':account' => $account]
        )->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmailOrAccount(string $credential, ?string $tenantId = null): ?User
    {
        if ($tenantId !== null && $tenantId !== '') {
            $row = $this->db->createCommand(
                'SELECT * FROM {{%' . self::TABLE . '}} WHERE tenant_id = :tid AND (email = :credential OR account = :credential) AND deleted_at IS NULL LIMIT 1',
                [':tid' => $tenantId, ':credential' => $credential]
            )->queryOne();

            return $row ? $this->hydrate($row) : null;
        }

        // 全局唯一：account、email 均有唯一索引（见 M240103000000），按「邮箱形态 / 账号形态」分步解析，避免 OR 命中两行
        return $this->findByCredentialGlobally($credential);
    }

    /**
     * 未指定租户时：先按账号、再按邮箱（或含 @ 时先邮箱后账号）解析，至多一行。
     */
    private function findByCredentialGlobally(string $credential): ?User
    {
        $looksLikeEmail = str_contains($credential, '@');

        if ($looksLikeEmail) {
            $row = $this->db->createCommand(
                'SELECT * FROM {{%' . self::TABLE . '}} WHERE email = :c AND deleted_at IS NULL LIMIT 1',
                [':c' => $credential]
            )->queryOne();
            if ($row !== false && $row !== null) {
                return $this->hydrate($row);
            }

            $row = $this->db->createCommand(
                'SELECT * FROM {{%' . self::TABLE . '}} WHERE account = :c AND deleted_at IS NULL LIMIT 1',
                [':c' => $credential]
            )->queryOne();

            return $row ? $this->hydrate($row) : null;
        }

        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE account = :c AND deleted_at IS NULL LIMIT 1',
            [':c' => $credential]
        )->queryOne();
        if ($row !== false && $row !== null) {
            return $this->hydrate($row);
        }

        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE email = :c AND deleted_at IS NULL LIMIT 1',
            [':c' => $credential]
        )->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(int $page = 1, int $pageSize = 20): array
    {
        $tid    = $this->tenantContext->requireTenantId();
        $offset = ($page - 1) * $pageSize;

        $total = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM {{%' . self::TABLE . '}} WHERE tenant_id = :tid AND deleted_at IS NULL',
            [':tid' => $tid]
        )->queryScalar();

        $rows = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE tenant_id = :tid AND deleted_at IS NULL ORDER BY id DESC LIMIT :limit OFFSET :offset',
            [':tid' => $tid, ':limit' => $pageSize, ':offset' => $offset]
        )->queryAll();

        return [
            'items' => array_map([$this, 'hydrate'], $rows),
            'total' => $total,
        ];
    }

    public function save(User $user): User
    {
        $now       = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $tenantKey = $user->getTenantId() ?? $this->tenantContext->requireTenantId();

        $data = [
            'tenant_id'     => $tenantKey,
            'account'       => $user->getName(),
            'email'         => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'status'        => $this->statusToDb($user->getStatus()),
            'updated_at'    => $now,
        ];

        if ($user->getId() === null) {
            $data['created_at'] = $now;
            $this->db->createCommand()->insert('{{%' . self::TABLE . '}}', $data)->execute();
            $id = (int)$this->db->getLastInsertID();

            return new User(
                name: $user->getName(),
                email: $user->getEmail(),
                passwordHash: $user->getPasswordHash(),
                status: $user->getStatus(),
                id: $id,
                createdAt: new \DateTimeImmutable($now),
                updatedAt: new \DateTimeImmutable($now),
                tenantId: $tenantKey,
                isPlatformAdmin: $user->isPlatformAdmin(),
            );
        }

        $this->db->createCommand()->update(
            '{{%' . self::TABLE . '}}',
            $data,
            ['id' => $user->getId(), 'tenant_id' => $tenantKey]
        )->execute();

        return $user;
    }

    public function delete(int $id): bool
    {
        $tid = $this->tenantContext->requireTenantId();

        $affected = $this->db->createCommand()->delete(
            '{{%' . self::TABLE . '}}',
            ['id' => $id, 'tenant_id' => $tid]
        )->execute();

        return $affected > 0;
    }

    public function deleteByAccount(string $account): bool
    {
        $tid = $this->tenantContext->requireTenantId();
        
        $affected = $this->db->createCommand()->delete(
            '{{%' . self::TABLE . '}}',
            ['tenant_id' => $tid, 'account' => $account]
        )->execute();
        
        return $affected > 0;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $tid = $this->tenantContext->requireTenantId();

        $sql    = 'SELECT COUNT(*) FROM {{%' . self::TABLE . '}} WHERE tenant_id = :tid AND email = :email AND deleted_at IS NULL';
        $params = [':tid' => $tid, ':email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeId;
        }

        return (int)$this->db->createCommand($sql, $params)->queryScalar() > 0;
    }

    public function accountExists(string $account, ?string $excludeAccount = null): bool
    {
        $tid = $this->tenantContext->requireTenantId();

        $sql = 'SELECT COUNT(*) FROM {{%' . self::TABLE . '}} WHERE tenant_id = :tid AND account = :account AND deleted_at IS NULL';
        $params = [':tid' => $tid, ':account' => $account];

        if ($excludeAccount !== null) {
            $sql .= ' AND account != :excludeAccount';
            $params[':excludeAccount'] = $excludeAccount;
        }

        $count = (int)$this->db->createCommand($sql, $params)->queryScalar();
        return $count > 0;
    }

    private function hydrate(array $row): User
    {
        $account = $row['account'] ?? $row['name'] ?? '';

        return new User(
            name: $account,
            email: $row['email'],
            passwordHash: $row['password_hash'],
            status: $this->statusFromDb($row['status']),
            id: (int)$row['id'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
            tenantId: (string)$row['tenant_id'],
            isPlatformAdmin: !empty($row['is_platform_admin']),
        );
    }

    /**
     * 与迁移注释一致：10 正常 9 未激活 1 禁用 0 删除
     */
    private function statusToDb(string $status): int
    {
        return match ($status) {
            User::STATUS_ACTIVE => 10,
            User::STATUS_INACTIVE => 9,
            User::STATUS_BANNED => 1,
            default => 10,
        };
    }

    private function statusFromDb(mixed $value): string
    {
        return match ((int)$value) {
            10 => User::STATUS_ACTIVE,
            9 => User::STATUS_INACTIVE,
            1 => User::STATUS_BANNED,
            0 => User::STATUS_BANNED,
            default => User::STATUS_ACTIVE,
        };
    }
}
