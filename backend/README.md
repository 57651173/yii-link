# Yii3 API 企业级脚手架

## 项目简介

本项目基于 **Yii3** 构建，采用 **API First + 分层架构 + DDD（领域驱动设计）**，专用于：

- ERP 系统
- WMS 仓储系统
- SaaS 后台服务

---

## 技术栈

| 技术 | 版本 | 用途 |
|------|------|------|
| PHP | 8.1+ | 后端语言 |
| Yii3 | Router + DI + DB | 框架核心 |
| PSR-7 / PSR-15 | - | HTTP 标准接口 |
| JWT | firebase/php-jwt ^6 | 身份认证 |
| MySQL | 5.7+ | 数据库 |
| Composer | 2.x | 依赖管理 |

---

## 目录结构

```
backend/
├── app/                          # 接口层（Controller / Middleware / Request / Response）
│   ├── Controller/
│   │   └── V1/
│   │       ├── AuthController.php    # 登录、获取当前用户
│   │       └── UserController.php    # 用户 CRUD
│   ├── Middleware/
│   │   ├── AuthMiddleware.php        # JWT 认证拦截
│   │   └── CorsMiddleware.php        # 跨域处理
│   ├── Response/
│   │   └── ApiResponse.php           # 统一响应格式工具
│   └── Exception/
│       └── BusinessException.php     # 业务异常
│
├── application/                  # 应用层（业务流程编排）
│   └── User/
│       ├── DTO/
│       │   ├── CreateUserDTO.php
│       │   └── UpdateUserDTO.php
│       └── Service/
│           └── UserService.php
│
├── domain/                       # 领域层（核心业务规则）
│   └── User/
│       ├── Entity/
│       │   └── User.php              # 用户实体
│       └── Repository/
│           └── UserRepositoryInterface.php   # 仓储接口
│
├── infrastructure/               # 基础设施层（数据库实现）
│   └── Persistence/
│       └── User/
│           └── DbUserRepository.php  # 数据库仓储实现
│
├── routes/
│   └── api.php                   # 路由定义（仅文档用，实际路由在 public/index.php）
│
├── config/
│   ├── params.php                # 应用参数（DB / JWT / Log）
│   └── common.php                # DI 容器定义
│
├── database/
│   ├── migrations/               # Yii Migration 迁移类目录
│   │   └── M240101000000CreateUsersTable.php
│   └── migrations.sql            # 旧版 SQL（已由 migrate 替代，仅供参考）
│
├── public/
│   └── index.php                 # 唯一入口文件
│
├── runtime/                      # 运行时文件（日志/缓存）
├── vendor/                       # Composer 依赖
├── .env                          # 本地环境变量（不提交 Git）
├── .env.example                  # 环境变量模板
└── composer.json
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
# 启动所有服务（migrate 容器会在 MySQL 就绪后自动执行迁移，php 容器等迁移完成后再启动）
docker compose up -d
```

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

初始化后会创建 `users` 表，并插入两个测试账号：

| 邮箱 | 密码 |
|------|------|
| admin@yii-link.com | password |
| test@yii-link.com | password |

#### 新增迁移文件说明

迁移文件存放于 `database/migrations/`，命名格式为 `M{年月日时分秒}{描述}.php`，例如：

```
database/migrations/
├── M240101000000CreateUsersTable.php   # 创建用户表（含测试数据）
└── M240201000000CreateOrdersTable.php  # 创建订单表（按需添加）
```

每个迁移类需实现 `up()` 建表 和 `down()` 回滚两个方法。控制台入口：`yii`（项目根目录）。

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

请求体：

```json
{
  "email": "admin@yii-link.com",
  "password": "password"
}
```

成功响应：

```json
{
  "code": 0,
  "message": "登录成功",
  "data": {
    "token": "eyJ0eXAiOiJKV1Qi...",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "name": "超级管理员",
      "email": "admin@yii-link.com",
      "status": "active",
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
GET /api/v1/users/{id}
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
PUT /api/v1/users/{id}
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
DELETE /api/v1/users/{id}
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
