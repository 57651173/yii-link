<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\User;

use Domain\User\Entity\User;
use Domain\User\Repository\UserRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 用户仓储的数据库实现
 *
 * 使用 Yii3 DB 组件操作数据库，将数据库记录与领域实体互相转换。
 */
class DbUserRepository implements UserRepositoryInterface
{
    private const TABLE = 'users';

    public function __construct(
        private readonly ConnectionInterface $db,
    ) {
    }

    public function findById(int $id): ?User
    {
        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE id = :id LIMIT 1',
            [':id' => $id]
        )->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE email = :email LIMIT 1',
            [':email' => $email]
        )->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;

        $total = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM {{%' . self::TABLE . '}}'
        )->queryScalar();

        $rows = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} ORDER BY id DESC LIMIT :limit OFFSET :offset',
            [':limit' => $pageSize, ':offset' => $offset]
        )->queryAll();

        return [
            'items' => array_map([$this, 'hydrate'], $rows),
            'total' => $total,
        ];
    }

    public function save(User $user): User
    {
        $now  = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $data = [
            'name'          => $user->getName(),
            'email'         => $user->getEmail(),
            'password_hash' => $user->getPasswordHash(),
            'status'        => $user->getStatus(),
            'updated_at'    => $now,
        ];

        if ($user->getId() === null) {
            // 新增
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
            );
        }

        // 更新
        $this->db->createCommand()->update(
            '{{%' . self::TABLE . '}}',
            $data,
            ['id' => $user->getId()]
        )->execute();

        return $user;
    }

    public function delete(int $id): bool
    {
        $affected = $this->db->createCommand()->delete(
            '{{%' . self::TABLE . '}}',
            ['id' => $id]
        )->execute();

        return $affected > 0;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM {{%' . self::TABLE . '}} WHERE email = :email';
        $params = [':email' => $email];

        if ($excludeId !== null) {
            $sql             .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeId;
        }

        return (int)$this->db->createCommand($sql, $params)->queryScalar() > 0;
    }

    /**
     * 将数据库行数据转换为用户领域实体
     */
    private function hydrate(array $row): User
    {
        return new User(
            name: $row['name'],
            email: $row['email'],
            passwordHash: $row['password_hash'],
            status: $row['status'],
            id: (int)$row['id'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
        );
    }
}
