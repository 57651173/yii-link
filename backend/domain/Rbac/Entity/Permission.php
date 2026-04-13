<?php

declare(strict_types=1);

namespace Domain\Rbac\Entity;

/**
 * 权限领域实体
 */
class Permission
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?int $parentId,
        private string $name,
        private string $slug,
        private string $type = 'api',
        private ?string $httpMethod = null,
        private ?string $httpPath = null,
        private int $sortOrder = 0,
        private ?string $description = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHttpMethod(): ?string
    {
        return $this->httpMethod;
    }

    public function getHttpPath(): ?string
    {
        return $this->httpPath;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'http_method' => $this->httpMethod,
            'http_path' => $this->httpPath,
            'sort_order' => $this->sortOrder,
            'description' => $this->description,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
