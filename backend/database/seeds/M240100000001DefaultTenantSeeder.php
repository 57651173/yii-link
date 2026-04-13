<?php

declare(strict_types=1);

namespace Database\Seeds;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 默认租户（code=system），主键 id 与 config/params.php 中 default_tenant_id（及 md5('sys_tenant_default') 默认值）一致。
 */
final class M240100000001DefaultTenantSeeder implements SeederInterface
{
    public function run(ConnectionInterface $db): void
    {
        /** @var array{id: string} $cfg */
        $tenantId = md5('sys_tenant_default');

        $n = (int)$db->createCommand(
            'SELECT COUNT(*) FROM {{%sys_tenants}} WHERE code = :code OR id = :id',
            [':code' => 'default', ':id' => $tenantId]
        )->queryScalar();

        if ($n > 0) {
            return;
        }

        $db->createCommand()->insert('{{%sys_tenants}}', [
            'id' => $tenantId,
            'name' => '系统租户',
            'code' => 'system',
            'status' => 10,
            'plan_code' => 'dev',
            'remark' => '本地/开发默认租户',
        ])->execute();
    }
}
