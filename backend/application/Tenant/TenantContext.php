<?php

declare(strict_types=1);

namespace Application\Tenant;

/**
 * 当前 HTTP 请求绑定的租户 ID（由登录或 JWT 中间件写入）。
 *
 * 在 PHP-FPM 下单次请求内通过 DI 复用同一实例，实现仓储层按租户隔离。
 */
final class TenantContext
{
    private ?string $tenantId = null;

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function setTenantId(string $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function clear(): void
    {
        $this->tenantId = null;
    }

    public function requireTenantId(): string
    {
        if ($this->tenantId === null || $this->tenantId === '') {
            throw new \RuntimeException('租户上下文未设置，无法访问租户数据');
        }

        return $this->tenantId;
    }
}
