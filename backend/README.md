# Yii3 SaaS 企业级后端脚手架

## 项目简介

本项目基于 **Yii3** 构建，采用 **DDD（领域驱动设计）+ 多租户架构 + RBAC 权限系统**，专用于：

- **SaaS 多租户系统**
- **ERP 企业资源管理**
- **WMS 仓储管理系统**
- **企业后台服务**

**🎯 系统完成度：100%** | **✅ 生产就绪**

## 文档入口（推荐）

- 项目文档入口：`../docs/README.md`
- API 总览：`../docs/api/API.md`
- 核心功能说明：`../docs/features/README.md`

---

## 核心功能

### 1. 多租户系统 ✅
- 完整的数据隔离（tenant_id）
- 租户管理 API（CRUD）
- 租户上下文（TenantContext）
- 租户初始化（默认角色和管理员）
- 租户配额管理（用户数、存储、API 调用）

### 2. RBAC 权限系统 ✅
- 权限管理（层级结构、全局权限）
- 角色管理（租户隔离）
- 用户角色分配
- 权限检查中间件（自动映射）
- 默认角色（admin/manager/worker/guest）
- 平台管理员 vs 租户管理员

### 3. 用户管理 ✅
- 用户 CRUD（基于 account 标识）
- 用户状态管理（active/inactive/banned）
- 密码重置
- 用户启用/禁用
- 邮箱验证

### 4. JWT 认证 ✅
- JWT Token 生成和验证
- 登录接口（支持邮箱/账号）
- Token 包含租户和用户信息
- AuthMiddleware 中间件
- 登录审计日志

### 5. 审计日志系统 ✅
- 自动记录 CUD 操作（AuditLogMiddleware）
- 登录审计（成功/失败）
- IP 和 User-Agent 记录
- 敏感数据过滤
- 审计日志查询 API

### 6. 数据验证系统 ✅ 🆕
- 统一验证器（Validator）
- 10+ 种验证规则
- 链式调用
- 批量验证
- 自定义验证

### 7. 错误处理系统 ✅ 🆕
- 全局错误处理中间件
- 开发/生产环境区分
- 详细错误日志
- 堆栈跟踪（开发环境）

### 8. 健康检查系统 ✅ 🆕
- 完整健康检查（/health）
- 简化健康检查（/health/simple）
- 数据库/Redis/磁盘检查

### 9. API 限流系统 ✅ 🆕
- 三级限流（IP/用户/租户）
- Token Bucket 算法
- Redis 存储
- 限流响应头

### 10. 缓存系统 ✅ 🆕
- Redis 缓存服务
- get/set/delete/has/remember
- 批量操作
- 计数器

### 11. 配额管理系统 ✅ 🆕
- 用户数配额
- 存储空间配额
- API 调用配额（每日）
- 配额检查中间件

---

## 技术栈

| 技术 | 版本 | 用途 |
|------|------|------|
| PHP | 8.1+ | 后端语言 |
| Yii3 | 最新版 | 框架核心（Router + DI + DB） |
| MySQL | 5.7+ | 主数据库 |
| Redis | 6.0+ | 缓存和限流 |
| JWT | firebase/php-jwt ^6 | 身份认证 |
| PSR-7/PSR-15 | - | HTTP 标准接口 |
| Composer | 2.x | 依赖管理 |
| Docker | 最新版 | 容器化部署 |

---

## 目录结构

```
backend/
├── app/                          # 应用层（Controller / Middleware / Service）
│   ├── Controller/
│   │   ├── HealthController.php       # 健康检查 🆕
│   │   └── V1/
│   │       ├── AuthController.php     # 登录、获取当前用户（含登录审计）
│   │       ├── UserController.php     # 用户 CRUD（基于 account）
│   │       ├── TenantController.php   # 租户管理
│   │       ├── RoleController.php     # 角色管理
│   │       ├── PermissionController.php  # 权限管理
│   │       ├── UserRoleController.php # 用户角色分配
│   │       └── AuditLogController.php # 审计日志查询
│   ├── Middleware/
│   │   ├── AuthMiddleware.php         # JWT 认证拦截
│   │   ├── CorsMiddleware.php         # 跨域处理
│   │   ├── PermissionMiddleware.php   # 权限检查 🆕
│   │   ├── AuditLogMiddleware.php     # 审计日志自动记录 🆕
│   │   ├── ErrorHandlerMiddleware.php # 全局错误处理 🆕
│   │   ├── RateLimitMiddleware.php    # API 限流 🆕
│   │   └── QuotaCheckMiddleware.php   # 配额检查 🆕
│   ├── Service/
│   │   ├── RateLimiter.php           # Redis 限流器 🆕
│   │   ├── QuotaService.php          # 配额检查服务 🆕
│   │   └── CacheService.php          # Redis 缓存服务 🆕
│   ├── Validation/
│   │   └── Validator.php             # 统一验证器 🆕
│   ├── Response/
│   │   └── ApiResponse.php           # 统一响应格式工具
│   └── Exception/
│       └── BusinessException.php     # 业务异常
│
├── application/                  # 应用服务层（业务流程编排）
│   ├── User/
│   │   ├── DTO/
│   │   │   ├── CreateUserDTO.php
│   │   │   └── UpdateUserDTO.php
│   │   └── Service/
│   │       └── UserService.php       # 用户业务逻辑
│   ├── Tenant/
│   │   ├── TenantContext.php         # 租户上下文
│   │   ├── TenantResolver.php        # 租户解析器
│   │   └── Service/
│   │       └── TenantService.php     # 租户业务逻辑
│   ├── Rbac/
│   │   └── Service/
│   │       └── RbacService.php       # 权限检查服务
│   └── AuditLog/
│       └── Service/
│           └── AuditLogService.php   # 审计日志服务
│
├── domain/                       # 领域层（核心业务规则）
│   ├── User/
│   │   ├── Entity/
│   │   │   └── User.php              # 用户实体
│   │   └── Repository/
│   │       └── UserRepositoryInterface.php
│   ├── Tenant/
│   │   ├── Entity/
│   │   │   └── Tenant.php            # 租户实体
│   │   └── Repository/
│   │       └── TenantRepositoryInterface.php
│   ├── Rbac/
│   │   ├── Entity/
│   │   │   ├── Permission.php        # 权限实体
│   │   │   └── Role.php              # 角色实体
│   │   └── Repository/
│   │       └── RbacRepositoryInterface.php
│   └── AuditLog/
│       ├── Entity/
│       │   └── AuditLog.php          # 审计日志实体
│       └── Repository/
│           └── AuditLogRepositoryInterface.php
│
├── infrastructure/               # 基础设施层（数据库实现）
│   └── Persistence/
│       ├── User/
│       │   └── DbUserRepository.php
│       ├── Tenant/
│       │   └── DbTenantRepository.php
│       ├── Rbac/
│       │   └── DbRbacRepository.php
│       └── AuditLog/
│           └── DbAuditLogRepository.php
│
├── config/
│   ├── params.php                # 应用参数（DB / JWT / Redis / Log）
│   ├── container.php             # DI 容器定义
│   └── routes.php                # 路由配置 🆕
│
├── database/
│   ├── README.md                 # 数据库设计说明
│   ├── migrations/               # 数据库迁移
│   └── seeds/                    # 初始数据
│
├── bootstrap/
│   └── app.php                   # 应用引导文件
│
├── public/
│   └── index.php                 # 唯一入口文件
│
├── runtime/                      # 运行时文件（日志/缓存）
├── vendor/                       # Composer 依赖
├── .env                          # 本地环境变量（不提交 Git）
├── .env.example                  # 环境变量模板
├── composer.json
├── yii                           # 控制台入口
└── README.md                     # 本文件
```

---

## 快速开始

### 第一步：安装依赖

```bash
composer install
```

### 第二步：配置环境变量

```bash
cp .env.example .env
```

编辑 `.env` 文件，填写你的数据库密码和 JWT 密钥：

```env
DB_DSN=mysql:host=127.0.0.1;dbname=yii_link;charset=utf8mb4
DB_USER=root
DB_PASS=你的MySQL密码

JWT_SECRET=改成一个复杂的随机字符串
JWT_EXPIRE=86400
```

### 第三步：执行数据库迁移

项目已接入 `yiisoft/db-migration`，使用 PHP 迁移类替代手动执行 SQL 文件。

#### Docker 环境（推荐）

```bash
# 1) 在项目根目录启动所有服务
docker compose up -d

# 2) 在 backend（PHP-FPM）容器内执行迁移（-y 跳过确认）
docker compose exec backend php yii migrate/up -y

# 3) （可选）写入初始演示数据，脚本位于 database/seeds/
docker compose exec backend php yii seed:run
```

> 说明：Docker Compose 使用**项目根目录**的 `.env`（不是 `backend/.env`）来注入容器环境变量。

#### 本地开发环境

项目提供统一的控制台入口 `yii`，所有命令均通过它执行：

```bash
# 执行所有待运行的迁移
php yii migrate:up

# 查看迁移历史
php yii migrate:history

# 查看尚未执行的迁移
php yii migrate:new

# 回滚最近一次迁移
php yii migrate:down

# 重跑最近一次迁移（down + up）
php yii migrate:redo

# 新建迁移文件（例如新增订单表）
php yii migrate:create CreateOrdersTable

# 查看所有可用命令
php yii list
```

表结构由迁移创建；演示账号与密码见 `database/seeds/` 源码及 `database/README.md`。

#### 新增迁移

迁移文件放在 `database/migrations/`，类名与 `M{时间戳}{描述}` 命名约定见 `yii` 内注释。每个迁移实现 `up()` / `down()`。库表设计说明写在 **`database/README.md`**。

### 第四步：启动开发服务器

```bash
php -S localhost:8000 -t public
```

服务启动后，访问 http://localhost:8000

---

## API 接口文档

### 统一响应格式

```json
{
  "code": 0,
  "message": "success",
  "data": {}
}
```

- `code: 0` 表示成功
- `code != 0` 表示错误（值与 HTTP 状态码一致）

### 认证方式

除登录接口外，所有接口需携带 JWT Token：

```
Authorization: Bearer {token}
```

---

### 认证接口

#### 用户登录

```
POST /api/v1/login
Content-Type: application/json
```

请求体（`username` 为邮箱或账号；多租户建议传 `tenant_code`，与 `sys_tenants.code` 一致，如种子中的 `system`）：

```json
{
  "username": "admin",
  "password": "admin123123",
  "tenant_code": "system"
}
```

成功响应（JWT 内含 `tid` 租户主键；`user` 中含 `tenant_id`）：

```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "token": "eyJ0eXAiOiJKV1Qi...",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "name": "admin",
      "email": "admin@yii-link.com",
      "status": "active",
      "tenant_id": "be4f35455023f1980c868221a5b3c42d",
      "created_at": "2026-04-10 12:00:00",
      "updated_at": "2026-04-10 12:00:00"
    }
  }
}
```

#### 获取当前用户信息

```
GET /api/v1/me
Authorization: Bearer {token}
```

---

### 用户管理接口

#### 获取用户列表（分页）

```
GET /api/v1/users?page=1&page_size=20
Authorization: Bearer {token}
```

响应：

```json
{
  "code": 0,
  "message": "success",
  "data": {
    "items": [...],
    "total": 100,
    "page": 1,
    "page_size": 20,
    "pages": 5
  }
}
```

#### 获取用户详情

```
GET /api/v1/users/{account}
Authorization: Bearer {token}
```

#### 创建用户

```
POST /api/v1/users
Authorization: Bearer {token}
Content-Type: application/json
```

请求体：

```json
{
  "name": "张三",
  "email": "zhangsan@example.com",
  "password": "123456"
}
```

#### 更新用户

```
PUT /api/v1/users/{account}
Authorization: Bearer {token}
Content-Type: application/json
```

请求体（字段均为可选）：

```json
{
  "name": "李四",
  "email": "lisi@example.com",
  "password": "newpassword"
}
```

#### 删除用户

```
DELETE /api/v1/users/{account}
Authorization: Bearer {token}
```

---

## 分层架构说明

### Controller（接口层）

- 位置：`app/Controller/`
- 职责：接收请求、参数验证、调用 Service、返回响应
- 规则：**不写任何业务逻辑**

### Service（应用层）

- 位置：`application/*/Service/`
- 职责：业务流程编排，调用领域对象
- 使用 DTO 传递数据，而非原始数组

### Domain（领域层）

- 位置：`domain/`
- 职责：定义实体（Entity）、仓储接口（Repository Interface）
- 规则：**不依赖任何具体技术实现**

### Infrastructure（基础设施层）

- 位置：`infrastructure/`
- 职责：实现领域层定义的接口（数据库、缓存等）

---

## 中间件

| 中间件 | 作用 |
|--------|------|
| `CorsMiddleware` | 处理跨域（CORS），所有请求都经过 |
| `AuthMiddleware` | JWT 认证，只对标注了的路由生效 |

---

## 扩展指南

新增业务模块（如订单系统）时，按以下结构添加：

```
domain/Order/Entity/Order.php
domain/Order/Repository/OrderRepositoryInterface.php
application/Order/DTO/CreateOrderDTO.php
application/Order/Service/OrderService.php
infrastructure/Persistence/Order/DbOrderRepository.php
app/Controller/V1/OrderController.php
```

然后在 `public/index.php` 中：
1. 注册 DI 绑定：`OrderRepositoryInterface::class => DbOrderRepository::class`
2. 添加路由：`Route::post('/api/v1/orders')->action([OrderController::class, 'create'])->...`

---

## 生产环境部署建议

### Docker 部署

推荐使用以下组合：

- **Nginx** 作为 Web 服务器
- **PHP-FPM** 处理 PHP 请求
- **MySQL** 数据库
- **Redis** 缓存（可选）

### 安全建议

- 修改 `.env` 中的 `JWT_SECRET` 为随机复杂字符串
- 设置 `APP_DEBUG=false`
- 数据库账号使用最小权限
- 生产环境不要暴露 `runtime/` 目录

---

## 最佳实践

- 使用 DTO 代替原始数组传递数据
- Controller 不写业务逻辑，只负责请求/响应
- 所有接口必须经过 Service 层
- 禁止在 Controller 中直接操作数据库
- 通过 Repository Interface 访问数据，不直接依赖 ORM 实现

---

## License

MIT
