<?php

declare(strict_types=1);

namespace Domain\AuditLog\Entity;

/**
 * 审计日志领域实体
 * 
 * 记录系统中所有关键操作，用于安全审计和问题追溯。
 */
class AuditLog
{
    // 操作类型常量
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_EXPORT = 'export';
    public const ACTION_IMPORT = 'import';
    
    // 操作结果常量
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    
    private ?int $id;
    private string $tenantId;
    private ?int $userId;
    private ?string $userAccount;
    private string $action;
    private string $resourceType;
    private ?string $resourceId;
    private ?string $httpMethod;
    private ?string $httpPath;
    private ?string $ipAddress;
    private ?string $userAgent;
    private ?array $oldValues;
    private ?array $newValues;
    private string $status;
    private ?string $errorMessage;
    private \DateTimeImmutable $createdAt;
    
    public function __construct(
        string $tenantId,
        string $action,
        string $resourceType,
        string $status = self::STATUS_SUCCESS,
        ?int $userId = null,
        ?string $userAccount = null,
        ?string $resourceId = null,
        ?string $httpMethod = null,
        ?string $httpPath = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $errorMessage = null,
        ?int $id = null,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->userAccount = $userAccount;
        $this->action = $action;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->httpMethod = $httpMethod;
        $this->httpPath = $httpPath;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->oldValues = $oldValues;
        $this->newValues = $newValues;
        $this->status = $status;
        $this->errorMessage = $errorMessage;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getTenantId(): string
    {
        return $this->tenantId;
    }
    
    public function getUserId(): ?int
    {
        return $this->userId;
    }
    
    public function getUserAccount(): ?string
    {
        return $this->userAccount;
    }
    
    public function getAction(): string
    {
        return $this->action;
    }
    
    public function getResourceType(): string
    {
        return $this->resourceType;
    }
    
    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }
    
    public function getHttpMethod(): ?string
    {
        return $this->httpMethod;
    }
    
    public function getHttpPath(): ?string
    {
        return $this->httpPath;
    }
    
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }
    
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
    
    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }
    
    public function getNewValues(): ?array
    {
        return $this->newValues;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
    
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }
    
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'user_account' => $this->userAccount,
            'action' => $this->action,
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'http_method' => $this->httpMethod,
            'http_path' => $this->httpPath,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'status' => $this->status,
            'error_message' => $this->errorMessage,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
