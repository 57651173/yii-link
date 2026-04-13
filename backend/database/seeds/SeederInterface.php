<?php

declare(strict_types=1);

namespace Database\Seeds;

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 数据库种子（初始/演示数据）接口。
 *
 * 实现类建议放在 `database/seeds/` 下，文件名与 migrations 一致使用 `M{序}` 前缀，
 * 例如 `M240101000001UserDefaultsSeeder.php`，由 `seed:run` 按文件名排序执行。
 */
interface SeederInterface
{
    public function run(ConnectionInterface $db): void;
}
