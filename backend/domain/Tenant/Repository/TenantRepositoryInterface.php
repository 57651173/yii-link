<?php

declare(strict_types=1);

namespace Domain\Tenant\Repository;

use Domain\Tenant\Entity\Tenant;

/**
 * 租户仓储接口
 */
interface TenantRepositoryInterface
{
    public function findById(string $id): ?Tenant;

    public function findByCode(string $code): ?Tenant;

    /**
     * @return array{items: Tenant[], total: int}
     */
    public function findAll(int $page = 1, int $pageSize = 20): array;

    public function save(Tenant $tenant): Tenant;

    public function delete(string $id): bool;

    public function codeExists(string $code, ?string $excludeId = null): bool;
}
