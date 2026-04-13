<?php

declare(strict_types=1);

namespace Database\Seeds;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 演示用户（依赖 {@see M240100000001DefaultTenantSeeder} 先写入 code=system 租户）。
 */
final class M240101000001UserDefaultsSeeder implements SeederInterface
{
    public function run(ConnectionInterface $db): void
    {
        $tenantId = $db->createCommand(
            'SELECT id FROM {{%sys_tenants}} WHERE code = :code LIMIT 1',
            [':code' => 'system']
        )->queryScalar();

        if ($tenantId === false || $tenantId === null) {
            throw new \RuntimeException('缺少 system 租户，请先执行 M240100000001DefaultTenantSeeder。');
        }

        $tenantId = (string)$tenantId;

        $userLists = [
            [
                'account' => 'admin',
                'email' => 'admin@yii-link.com',
                'password_hash' => password_hash('admin123123', PASSWORD_BCRYPT, ['cost' => 12]),
                'nickname' => '管理员',
                'status' => 10,
                'is_platform_admin' => 1,
                'tenant_id' => $tenantId,
            ],
            [
                'account' => 'root',
                'email' => 'root@yii-link.com',
                'password_hash' => password_hash('root123123', PASSWORD_BCRYPT, ['cost' => 12]),
                'nickname' => '用户',
                'status' => 10,
                'is_platform_admin' => 0,
                'tenant_id' => $tenantId,
            ],
            [
                'account' => 'test',
                'email' => 'test@yii-link.com',
                'password_hash' => password_hash('test123456', PASSWORD_BCRYPT, ['cost' => 12]),
                'nickname' => '测试',
                'status' => 10,
                'is_platform_admin' => 0,
                'tenant_id' => $tenantId,
            ],
            [
                'account' => 'demo',
                'email' => 'demo@yii-link.com',
                'password_hash' => password_hash('demo123123', PASSWORD_BCRYPT, ['cost' => 12]),
                'nickname' => '演示',
                'status' => 10,
                'is_platform_admin' => 0,
                'tenant_id' => $tenantId,
            ],
            [
                'account' => 'guest',
                'email' => 'guest@yii-link.com',
                'password_hash' => password_hash('guest123123', PASSWORD_BCRYPT, ['cost' => 12]),
                'nickname' => '访客',
                'status' => 10,
                'is_platform_admin' => 0,
                'tenant_id' => $tenantId,
            ],
            
        ];

        foreach ($userLists as $user) {
            $this->insertIfNotExists($db, $tenantId, $user['email'], $user);
        }
    }

    private function insertIfNotExists(ConnectionInterface $db, string $tenantId, string $email, array $row): void
    {
        $n = (int)$db->createCommand(
            'SELECT COUNT(*) FROM {{%sys_users}} WHERE tenant_id = :tid AND email = :email',
            [':tid' => $tenantId, ':email' => $email]
        )->queryScalar();

        if ($n > 0) {
            return;
        }

        $db->createCommand()->insert('{{%sys_users}}', $row)->execute();
    }
}
