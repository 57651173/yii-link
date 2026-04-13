<?php

declare(strict_types=1);

namespace Domain\User\Entity;

/**
 * 用户领域实体
 *
 * 代表系统中的用户对象，包含所有用户相关的业务规则和行为。
 */
class User
{
    private ?int $id;
    private string $name;
    private string $email;
    private string $passwordHash;
    private string $status;
    private ?string $tenantId;
    private bool $isPlatformAdmin;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BANNED   = 'banned';

    public function __construct(
        string $name,
        string $email,
        string $passwordHash,
        string $status = self::STATUS_ACTIVE,
        ?int $id = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
        ?string $tenantId = null,
        bool $isPlatformAdmin = false,
    ) {
        $this->id           = $id;
        $this->name         = $name;
        $this->email        = $email;
        $this->passwordHash = $passwordHash;
        $this->status       = $status;
        $this->tenantId     = $tenantId;
        $this->isPlatformAdmin = $isPlatformAdmin;
        $this->createdAt    = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt    = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPlatformAdmin(): bool
    {
        return $this->isPlatformAdmin;
    }

    public function rename(string $name): self
    {
        $clone       = clone $this;
        $clone->name = $name;
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }

    public function changeEmail(string $email): self
    {
        $clone        = clone $this;
        $clone->email = $email;
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }

    public function changePassword(string $passwordHash): self
    {
        $clone               = clone $this;
        $clone->passwordHash = $passwordHash;
        $clone->updatedAt    = new \DateTimeImmutable();
        return $clone;
    }

    public function deactivate(): self
    {
        $clone         = clone $this;
        $clone->status = self::STATUS_INACTIVE;
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }

    public function activate(): self
    {
        $clone         = clone $this;
        $clone->status = self::STATUS_ACTIVE;
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }

    public function ban(): self
    {
        $clone         = clone $this;
        $clone->status = self::STATUS_BANNED;
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'status'     => $this->status,
            'tenant_id'  => $this->tenantId,
            'is_platform_admin' => $this->isPlatformAdmin,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
