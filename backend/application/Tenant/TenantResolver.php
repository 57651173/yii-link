<?php

declare(strict_types=1);

namespace Application\Tenant;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 根据租户编码解析租户主键（sys_tenants.code）。
 */
final class TenantResolver
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {
    }

    public function resolveIdByCode(string $code): ?string
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $row = $this->db->createCommand(
            'SELECT id FROM {{%sys_tenants}} WHERE code = :code AND deleted_at IS NULL AND status = 10 LIMIT 1',
            [':code' => $code]
        )->queryOne();

        return $row !== false && $row !== null ? (string)$row['id'] : null;
    }
}
