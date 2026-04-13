# 数据库说明

系统表统一使用 `sys_` 前缀（与 Yii 表前缀 `{{%…}}` 组合后的物理表名一致）。**表结构与约束以 `migrations/` 下 PHP 迁移为准**；本文档便于快速查阅设计与约定。

## 迁移执行顺序

按文件名 `M…` 升序：

| 文件 | 物理表 |
|------|--------|
| `M240100000000CreateTenantsTable.php` | `sys_tenants` |
| `M240101000000CreateUsersTable.php` | `sys_users` |
| `M240102000000CreatePermissionsTable.php` | `sys_permissions` |
| `M240102000001CreateRolesTable.php` | `sys_roles` |
| `M240102000002CreateRolePermissionTable.php` | `sys_role_permission` |
| `M240102000003CreateUserRoleTable.php` | `sys_user_role` |
| `M240103000000UsersGlobalUniqueAccountEmail.php` | `sys_users` 登录字段全局唯一 |

## 核心约定

### 1. 租户主键与外键
- **租户主键**：`sys_tenants.id` 为 `char(32)`，各表 `tenant_id` 同型，外键指向 `sys_tenants.id`。

### 2. 用户与 RBAC 关联
- **用户关联**：`sys_user_role` 使用 `(tenant_id, user_account)` 对应 `sys_users(tenant_id, account)`，**不使用** `user_id`。

### 3. SaaS 角色隔离设计 🔴 重要
- **角色租户隔离**：每个租户的角色完全独立（`sys_roles.tenant_id` + 组合唯一索引）
- **不存在跨租户角色**：租户 A 的"admin"角色与租户 B 的"admin"角色是**两个不同的记录**
- **平台管理员不是角色**：平台权限由 `sys_users.is_platform_admin` 控制（不在 RBAC 中）

### 4. 默认租户 ID
- **开发/种子**：与 `config/params.php` 的 `default_tenant_id` 一致（默认 `md5('sys_tenant_default')`，可通过 `DEFAULT_TENANT_ID` 覆盖），种子中 `code = 'system'`。

## 表与关系摘要

### `sys_tenants`

租户。`status` 注释为：`10 正常 9 未激活 1 禁用 0 删除`（与迁移内注释一致）。

### `sys_users`

用户。`tenant_id` → `sys_tenants.id`；**`account`、`email` 各有全局唯一索引**（`M240103000000`）；同时保留 **`(tenant_id, account)` 组合唯一** 以满足 `sys_user_role` 外键。全局唯一保证同一登录名只对应一个租户，登录可不传 `tenant_code`；`mobile` 全局唯一（见迁移）。

**关键字段**：
- `status`：`10 正常 9 未激活 1 禁用 0 删除`
- **`is_platform_admin`**：`1` 表示平台管理员（可管理租户本身），不依赖 RBAC 角色

**说明**：平台管理员可以跨租户操作（如创建租户、查看所有租户），但在访问租户业务数据时仍需租户上下文。

### `sys_permissions`

全局权限点（无 `tenant_id`）。`slug` 唯一。

### `sys_roles`

租户内角色。`tenant_id` → `sys_tenants.id`；`(tenant_id, slug)` 唯一。

**重要**：
- 角色通过 `tenant_id` **完全隔离**
- 租户 A 的"admin"角色与租户 B 的"admin"角色是**两个独立的记录**
- **不存在跨租户的"超级管理员"角色**（平台权限用 `users.is_platform_admin` 控制）

### `sys_role_permission`

角色与权限多对多。`(role_id, permission_id)` 为主键，分别引用 `sys_roles`、`sys_permissions`。

### `sys_user_role`

用户与角色多对多。主键 `(tenant_id, user_account, role_id)`；组合外键指向 `sys_users(tenant_id, account)` 与 `sys_roles.id`。

## 种子数据

按文件名 `M*.php` 升序执行（`php yii seed:run`）：

- `seeds/M240100000001DefaultTenantSeeder.php`：写入 `code = system` 的默认租户（若不存在）。
- `seeds/M240101000001UserDefaultsSeeder.php`：在 `system` 租户下插入演示账号（若邮箱不存在）；**明文密码见该文件内 `password_hash` 调用**。
- `seeds/M240101000002RbacDefaultSeeder.php`：为 `system` 租户创建默认角色（`admin`/`manager`/`worker`/`guest`）并分配给用户。

**重要**：角色是租户独立的，其他租户需自行创建角色。`admin` 角色是**租户管理员**（非平台管理员）。

## 命令

```bash
# 在 backend 目录
php yii migrate:up -y
php yii seed:run
```

Docker 下见项目根目录 `docs/Docker.md`。
