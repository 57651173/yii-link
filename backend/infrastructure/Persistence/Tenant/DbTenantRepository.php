<?php

declare(strict_types=1);

namespace Infrastructure\Persistence\Tenant;

use Domain\Tenant\Entity\Tenant;
use Domain\Tenant\Repository\TenantRepositoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 租户仓储数据库实现
 */
class DbTenantRepository implements TenantRepositoryInterface
{
    private const TABLE = 'sys_tenants';

    public function __construct(
        private readonly ConnectionInterface $db,
    ) {
    }

    public function findById(string $id): ?Tenant
    {
        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        )->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCode(string $code): ?Tenant
    {
        $row = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE code = :code AND deleted_at IS NULL LIMIT 1',
            [':code' => $code]
        )->queryOne();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;

        $total = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM {{%' . self::TABLE . '}} WHERE deleted_at IS NULL'
        )->queryScalar();

        $rows = $this->db->createCommand(
            'SELECT * FROM {{%' . self::TABLE . '}} WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset',
            [':limit' => $pageSize, ':offset' => $offset]
        )->queryAll();

        return [
            'items' => array_map([$this, 'hydrate'], $rows),
            'total' => $total,
        ];
    }

    public function save(Tenant $tenant): Tenant
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $data = [
            'id' => $tenant->getId(),
            'name' => $tenant->getName(),
            'code' => $tenant->getCode(),
            'status' => $tenant->getStatus(),
            'plan_code' => $tenant->getPlanCode(),
            'max_users' => $tenant->getMaxUsers(),
            'expired_at' => $tenant->getExpiredAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $now,
        ];

        $exists = $this->db->createCommand(
            'SELECT COUNT(*) FROM {{%' . self::TABLE . '}} WHERE id = :id',
            [':id' => $tenant->getId()]
        )->queryScalar();

        if ((int)$exists === 0) {
            $data['created_at'] = $now;
            $this->db->createCommand()->insert('{{%' . self::TABLE . '}}', $data)->execute();
        } else {
            $this->db->createCommand()->update(
                '{{%' . self::TABLE . '}}',
                $data,
                ['id' => $tenant->getId()]
            )->execute();
        }

        return $tenant;
    }

    public function delete(string $id): bool
    {
        $affected = $this->db->createCommand()->update(
            '{{%' . self::TABLE . '}}',
            ['deleted_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')],
            ['id' => $id]
        )->execute();

        return $affected > 0;
    }

    public function codeExists(string $code, ?string $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM {{%' . self::TABLE . '}} WHERE code = :code AND deleted_at IS NULL';
        $params = [':code' => $code];

        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params[':excludeId'] = $excludeId;
        }

        return (int)$this->db->createCommand($sql, $params)->queryScalar() > 0;
    }

    private function hydrate(array $row): Tenant
    {
        return new Tenant(
            id: (string)$row['id'],
            name: (string)$row['name'],
            code: (string)$row['code'],
            status: (int)$row['status'],
            planCode: $row['plan_code'] !== null ? (string)$row['plan_code'] : null,
            maxUsers: $row['max_users'] !== null ? (int)$row['max_users'] : null,
            contactName: $row['contact_name'] !== null ? (string)$row['contact_name'] : null,
            contactPhone: $row['contact_phone'] !== null ? (string)$row['contact_phone'] : null,
            contactEmail: $row['contact_email'] !== null ? (string)$row['contact_email'] : null,
            settingsJson: $row['settings_json'] !== null ? (string)$row['settings_json'] : null,
            remark: $row['remark'] !== null ? (string)$row['remark'] : null,
            expiredAt: $row['expired_at'] !== null ? new \DateTimeImmutable($row['expired_at']) : null,
            deletedAt: $row['deleted_at'] !== null ? new \DateTimeImmutable($row['deleted_at']) : null,
            createdAt: new \DateTimeImmutable($row['created_at']),
            updatedAt: new \DateTimeImmutable($row['updated_at']),
        );
    }
}
