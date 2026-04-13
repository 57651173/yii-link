<?php

declare(strict_types=1);

namespace Application\Tenant\Service;

use App\Exception\BusinessException;
use Application\Rbac\Service\RoleService;
use Application\Rbac\Service\UserRoleService;
use Application\Tenant\TenantContext;
use Application\User\Service\UserService;
use Domain\Tenant\Entity\Tenant;
use Domain\Tenant\Repository\TenantRepositoryInterface;

/**
 * 租户管理服务
 */
class TenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly UserService $userService,
        private readonly RoleService $roleService,
        private readonly UserRoleService $userRoleService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * 创建租户（含初始管理员账号和角色）
     *
     * 请求参数：
     * - code: 租户编码（必填）
     * - name: 租户名称（必填）
     * - admin_account: 管理员账号（可选，默认 admin）
     * - admin_email: 管理员邮箱（可选，默认 admin@{code}.local）
     * - admin_password: 管理员密码（可选，默认 password123）
     * - contact_name: 联系人姓名
     * - contact_phone: 联系电话
     * - contact_email: 联系邮箱
     * - ... 其他租户字段
     */
    public function create(array $data): Tenant
    {
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');

        if (empty($code) || empty($name)) {
            throw new BusinessException('租户编码和名称不能为空', 422);
        }

        if ($this->tenantRepository->codeExists($code)) {
            throw new BusinessException("租户编码 {$code} 已存在", 409);
        }

        $id = $data['id'] ?? md5('tenant_' . $code . '_' . time());

        $tenant = new Tenant(
            id: $id,
            name: $name,
            code: $code,
            status: (int)($data['status'] ?? Tenant::STATUS_ACTIVE),
            planCode: $data['plan_code'] ?? null,
            maxUsers: isset($data['max_users']) ? (int)$data['max_users'] : null,
            contactName: $data['contact_name'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            remark: $data['remark'] ?? null,
            expiredAt: isset($data['expired_at']) ? new \DateTimeImmutable($data['expired_at']) : null,
        );

        $savedTenant = $this->tenantRepository->save($tenant);

        // 初始化租户管理员账号和角色
        $this->initializeTenantDefaults($savedTenant, $data);

        return $savedTenant;
    }

    /**
     * 初始化租户默认数据（管理员账号 + 角色）
     */
    private function initializeTenantDefaults(Tenant $tenant, array $data): void
    {
        $tenantId = $tenant->getId();

        // 临时设置租户上下文（用于创建角色和用户）
        $this->tenantContext->setTenantId($tenantId);

        try {
            // 1. 创建默认角色
            $adminRole = $this->createDefaultRoles();

            // 2. 创建管理员账号
            $adminAccount = $this->createAdminUser($tenant, $data);

            // 3. 为管理员分配角色
            $this->userRoleService->assignRoles($adminAccount, [$adminRole->getId()]);
        } finally {
            // 清理租户上下文
            $this->tenantContext->clear();
        }
    }

    /**
     * 创建默认角色（admin, worker, guest）
     *
     * @return \Domain\Rbac\Entity\Role 返回 admin 角色
     */
    private function createDefaultRoles(): \Domain\Rbac\Entity\Role
    {
        $roles = [
            ['slug' => 'admin', 'name' => '租户管理员', 'description' => '租户内最高权限'],
            ['slug' => 'worker', 'name' => '普通员工', 'description' => '日常业务操作'],
            ['slug' => 'guest', 'name' => '访客', 'description' => '只读访问'],
        ];

        $adminRole = null;
        foreach ($roles as $roleData) {
            $role = $this->roleService->create($roleData);
            if ($roleData['slug'] === 'admin') {
                $adminRole = $role;
            }
        }

        return $adminRole;
    }

    /**
     * 创建租户管理员账号
     */
    private function createAdminUser(Tenant $tenant, array $data): string
    {
        $adminAccount = trim($data['admin_account'] ?? 'admin');
        $adminEmail = trim($data['admin_email'] ?? "admin@{$tenant->getCode()}.local");
        $adminPassword = trim($data['admin_password'] ?? 'password123');

        $userData = [
            'account' => $adminAccount,
            'email' => $adminEmail,
            'password' => $adminPassword,
            'nickname' => $data['admin_nickname'] ?? "{$tenant->getName()} 管理员",
            'status' => 'active',
        ];

        $user = $this->userService->createUser($userData);

        return $user->getName(); // 返回 account
    }

    /**
     * 更新租户
     */
    public function update(string $id, array $data): Tenant
    {
        $tenant = $this->tenantRepository->findById($id);
        if ($tenant === null) {
            throw new BusinessException('租户不存在', 404);
        }

        $code = trim($data['code'] ?? $tenant->getCode());
        if ($code !== $tenant->getCode() && $this->tenantRepository->codeExists($code, $id)) {
            throw new BusinessException("租户编码 {$code} 已被其他租户使用", 409);
        }

        $updated = new Tenant(
            id: $id,
            name: trim($data['name'] ?? $tenant->getName()),
            code: $code,
            status: (int)($data['status'] ?? $tenant->getStatus()),
            planCode: $data['plan_code'] ?? $tenant->getPlanCode(),
            maxUsers: isset($data['max_users']) ? (int)$data['max_users'] : $tenant->getMaxUsers(),
            contactName: $data['contact_name'] ?? null,
            contactPhone: $data['contact_phone'] ?? null,
            contactEmail: $data['contact_email'] ?? null,
            remark: $data['remark'] ?? null,
            expiredAt: isset($data['expired_at']) ? new \DateTimeImmutable($data['expired_at']) : $tenant->getExpiredAt(),
            updatedAt: new \DateTimeImmutable(),
        );

        return $this->tenantRepository->save($updated);
    }

    /**
     * 获取租户详情
     */
    public function getById(string $id): Tenant
    {
        $tenant = $this->tenantRepository->findById($id);
        if ($tenant === null) {
            throw new BusinessException('租户不存在', 404);
        }

        return $tenant;
    }

    /**
     * 获取租户列表
     */
    public function getList(int $page = 1, int $pageSize = 20): array
    {
        return $this->tenantRepository->findAll($page, $pageSize);
    }

    /**
     * 删除租户（软删除）
     */
    public function delete(string $id): bool
    {
        $tenant = $this->tenantRepository->findById($id);
        if ($tenant === null) {
            throw new BusinessException('租户不存在', 404);
        }

        return $this->tenantRepository->delete($id);
    }

    /**
     * 禁用租户
     */
    public function disable(string $id): Tenant
    {
        $tenant = $this->tenantRepository->findById($id);
        if ($tenant === null) {
            throw new BusinessException('租户不存在', 404);
        }

        $disabled = $tenant->disable();
        return $this->tenantRepository->save($disabled);
    }

    /**
     * 启用租户
     */
    public function enable(string $id): Tenant
    {
        $tenant = $this->tenantRepository->findById($id);
        if ($tenant === null) {
            throw new BusinessException('租户不存在', 404);
        }

        $enabled = $tenant->activate();
        return $this->tenantRepository->save($enabled);
    }
}
