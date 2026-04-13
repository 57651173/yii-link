<?php

declare(strict_types=1);

namespace Domain\Tenant\Entity;

/**
 * 租户领域实体
 */
class Tenant
{
    public const STATUS_ACTIVE = 10;
    public const STATUS_INACTIVE = 9;
    public const STATUS_DISABLED = 1;
    public const STATUS_DELETED = 0;

    public function __construct(
        private readonly string $id,
        private string $name,
        private string $code,
        private int $status = self::STATUS_ACTIVE,
        private ?string $planCode = null,
        private ?int $maxUsers = null,
        private ?string $contactName = null,
        private ?string $contactPhone = null,
        private ?string $contactEmail = null,
        private ?string $settingsJson = null,
        private ?string $remark = null,
        private ?\DateTimeImmutable $expiredAt = null,
        private ?\DateTimeImmutable $deletedAt = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function disable(): self
    {
        $clone = clone $this;
        $clone->status = self::STATUS_DISABLED;
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }

    public function activate(): self
    {
        $clone = clone $this;
        $clone->status = self::STATUS_ACTIVE;
        $clone->updatedAt = new \DateTimeImmutable();
        return $clone;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'plan_code' => $this->planCode,
            'max_users' => $this->maxUsers,
            'contact_name' => $this->contactName,
            'contact_phone' => $this->contactPhone,
            'contact_email' => $this->contactEmail,
            'settings_json' => $this->settingsJson,
            'remark' => $this->remark,
            'expired_at' => $this->expiredAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function getPlanCode(): ?string
    {
        return $this->planCode;
    }

    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    public function getExpiredAt(): ?\DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
