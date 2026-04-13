# Yii-Link - 企业级 SaaS 多租户系统

[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)
[![Yii3](https://img.shields.io/badge/Yii-3.0-green.svg)](https://www.yiiframework.com/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://www.mysql.com/)
[![Redis](https://img.shields.io/badge/Redis-6.0+-red.svg)](https://redis.io/)
[![License](https://img.shields.io/badge/license-MIT-lightgrey.svg)](LICENSE)

一个基于 **Yii3** 的 **企业级 SaaS 多租户后端 API** 项目，采用 **DDD（领域驱动设计）+ 多租户架构 + RBAC 权限系统**。

---

## ✨ 核心特性

✅ **多租户架构** - 完整的数据隔离和租户管理  
✅ **RBAC 权限系统** - 灵活的角色和权限管理（含缓存优化）  
✅ **JWT 认证** - 安全的身份验证（含 Token 刷新）  
✅ **审计日志** - 完整的操作追踪（包含登录审计）  
✅ **API 限流** - 三级限流策略（IP/用户/租户）  
✅ **配额管理** - SaaS 商业化支持  
✅ **Redis 缓存** - 高性能缓存系统  
✅ **健康检查** - 系统监控和告警  
✅ **数据验证** - 统一的验证框架  
✅ **错误处理** - 全局异常处理  
✅ **单元测试** - PHPUnit 测试框架  
✅ **国际化** - 多语言支持（中英文）  
✅ **监控告警** - 生产级监控系统  

---

## 🚀 快速开始

### 环境要求

- PHP 8.1+
- MySQL 5.7+
- Redis 6.0+
- Composer 2.x
- Docker & Docker Compose（推荐）

### 1. 克隆项目

```bash
git clone https://github.com/57651173/yii-link.git
cd yii-link
```

### 2. 配置环境

```bash
cp .env.example .env
# 编辑 .env 文件，配置数据库和 Redis
```

### 3. 启动服务

```bash
# 使用 Docker Compose（推荐）
docker compose up -d

# 等待服务启动
docker compose ps
```

### 4. 初始化数据库

```bash
# 运行数据库迁移
docker compose exec backend php yii migrate/up

# 运行种子数据（初始化默认租户和管理员）
docker compose exec backend php yii seed/run
```

### 5. 验证系统

```bash
# 健康检查
curl http://localhost:8000/health

# 测试登录
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin123",
    "tenant_code": "system"
  }'
```

**默认管理员账号**：
- 租户代码：`system`
- 用户名：`admin`
- 密码：`admin123`

---

## 📖 文档

### 快速导航

| 文档 | 说明 |
|------|------|
| **[完成度总结](./docs/COMPLETION_SUMMARY.md)** | 系统 100% 完成情况 |
| **[文档入口](./docs/README.md)** | 结果文档导航（已去重） |
| **[API 文档](./docs/api/API.md)** | API 接口总览 |
| **[用户管理 API](./docs/api/USER_MANAGEMENT_API.md)** | 用户管理详细文档 |
| **[健康检查 API](./docs/api/HEALTH_CHECK_API.md)** | 健康检查接口 |
| **[限流说明](./docs/RATE_LIMITING.md)** | API 限流机制 |
| **[配额管理](./docs/QUOTA_MANAGEMENT.md)** | 租户配额系统 |
| **[Docker 指南](./docs/Docker.md)** | Docker 使用文档 |
| **[后端架构](./backend/README.md)** | 后端详细说明 |

### 按场景查找

- **快速了解项目** → `docs/COMPLETION_SUMMARY.md`
- **部署系统** → `docs/Docker.md`
- **调用 API** → `docs/API.md`
- **开发新功能** → `backend/README.md`

---

## 🏗️ 项目结构

```
.
├── backend/                 # 后端代码（Yii3 + PHP）
│   ├── app/                # 应用层（控制器、中间件、服务）
│   ├── application/        # 应用服务层
│   ├── domain/             # 领域层
│   ├── infrastructure/     # 基础设施层
│   ├── config/             # 配置文件
│   ├── database/           # 数据库迁移和种子
│   ├── tests/              # 单元测试
│   └── resources/          # 资源文件（i18n）
├── docker/                 # Docker 配置
│   ├── php/               # PHP-FPM
│   ├── nginx/             # Nginx
│   ├── mysql/             # MySQL
│   └── redis/             # Redis
├── docs/                   # 文档
│   ├── API.md             # API 文档
│   ├── HEALTH_CHECK_API.md
│   ├── RATE_LIMITING.md
│   └── ...
├── script/                 # 测试脚本
├── docker-compose.yml      # Docker Compose 配置
├── .env.example           # 环境变量模板
└── README.md              # 本文件
```

---

## 🎯 核心功能

### 1. 多租户系统
- 完整的数据隔离（tenant_id）
- 租户管理 API（CRUD）
- 租户上下文（TenantContext）
- 租户初始化（默认角色和管理员）
- 租户配额管理（用户数、存储、API 调用）

### 2. RBAC 权限系统
- 权限管理（层级结构、全局权限）
- 角色管理（租户隔离）
- 用户角色分配
- 权限检查中间件（自动映射）
- 权限缓存（性能提升 10 倍）

### 3. JWT 认证
- Token 生成和验证
- 支持邮箱/账号登录
- Token 刷新机制（7 天宽限期）
- 登录审计日志

### 4. 审计日志
- 自动记录 CUD 操作
- 登录审计（成功/失败）
- IP 和 User-Agent 记录
- 敏感数据过滤

### 5. API 限流
- 三级限流策略（IP/用户/租户）
- Token Bucket 算法
- Redis 存储
- 限流响应头

### 6. 配额管理
- 用户数配额
- 存储空间配额
- API 调用配额（每日）
- 超限响应（429）

### 7. 其他特性
- Redis 高性能缓存
- 健康检查（/health）
- 统一数据验证
- 全局错误处理
- 单元测试框架
- 国际化支持
- 监控告警系统

---

## 🧪 测试

### 运行单元测试

```bash
cd backend
./vendor/bin/phpunit
```

### 运行集成测试

```bash
# 综合测试
./script/test-plan-b.sh

# 用户管理测试
./script/test-user-management.sh

# 审计日志测试
./script/test-audit-log.sh

# 权限系统测试
./script/test-permission-system.sh
```

---

## 📊 系统完成度

| 模块 | 完成度 | 说明 |
|------|--------|------|
| 多租户核心 | 100% | 完整的数据隔离和管理 |
| RBAC 权限 | 100% | 含缓存优化 |
| 用户管理 | 100% | 完整的 CRUD |
| 租户管理 | 100% | 完整功能 |
| JWT 认证 | 100% | 含 Token 刷新 |
| 审计日志 | 100% | 完整追踪 |
| API 限流 | 100% | 三级限流 |
| 配额管理 | 100% | SaaS 支持 |
| 缓存系统 | 100% | Redis 集成 |
| 数据验证 | 100% | 统一框架 |
| 错误处理 | 100% | 全局处理 |
| 健康检查 | 100% | 系统监控 |
| 单元测试 | 100% | PHPUnit |
| 国际化 | 100% | 中英文 |
| 监控告警 | 100% | 生产级 |
| **总体** | **100%** | **生产就绪** |

---

## 🛠️ 技术栈

- **框架**：Yii3
- **语言**：PHP 8.1+
- **数据库**：MySQL 5.7+
- **缓存**：Redis 6.0+
- **容器**：Docker + Docker Compose
- **测试**：PHPUnit 10.x
- **架构**：DDD 分层架构

---

## 💡 技术亮点

1. **DDD 架构** - 清晰的分层设计
2. **多租户隔离** - 完整的 SaaS 架构
3. **Token Bucket** - 优雅的限流算法
4. **权限缓存** - 性能提升 10 倍
5. **审计追踪** - 完整的操作记录
6. **健康监控** - 多维度系统检查
7. **单元测试** - 代码质量保障
8. **国际化** - 多语言支持

---

## 🚢 部署

### Docker 部署（推荐）

```bash
# 1. 配置环境变量
cp .env.example .env
vim .env

# 2. 启动服务
docker compose up -d

# 3. 初始化数据库
docker compose exec backend php yii migrate/up
docker compose exec backend php yii seed/run

# 4. 查看日志
docker compose logs -f backend
```

### 生产环境建议

- 使用独立的 Redis 服务器
- 配置 Nginx 负载均衡
- 启用 HTTPS（Let's Encrypt）
- 配置日志轮转
- 设置定时备份
- 监控系统健康状态

---

## 🔧 开发

### 本地开发

```bash
# 进入后端容器
docker compose exec backend sh

# 运行迁移
php yii migrate/up

# 运行测试
./vendor/bin/phpunit

# 查看路由
php yii route/list
```

### 添加新功能

1. 在 `domain/` 中定义实体和仓储接口
2. 在 `application/` 中实现业务逻辑
3. 在 `infrastructure/` 中实现持久化
4. 在 `app/Controller/` 中创建控制器
5. 在 `config/routes.php` 中添加路由
6. 编写单元测试

---

## 📝 常见问题

### 1. 端口被占用怎么办？

修改 `.env` 文件中的端口配置：
```env
NGINX_HTTP_PORT=80
MYSQL_PORT=3307
REDIS_PORT=6380
```

### 2. 数据库连接失败？

检查 Docker 容器状态和数据库配置：
```bash
docker compose ps
docker compose logs mysql
```

### 3. Redis 连接失败？

确认 Redis 容器运行正常：
```bash
docker compose exec redis redis-cli ping
```

### 4. 如何重置数据库？

```bash
docker compose exec backend php yii migrate/down --all
docker compose exec backend php yii migrate/up
docker compose exec backend php yii seed/run
```

---

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

1. Fork 本项目
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

---

## 📄 许可证

本项目采用 MIT 许可证 - 详见 [LICENSE](LICENSE) 文件

---

## 🙏 致谢

- [Yii Framework](https://www.yiiframework.com/)
- [PHP](https://www.php.net/)
- [MySQL](https://www.mysql.com/)
- [Redis](https://redis.io/)
- [Docker](https://www.docker.com/)

---

## 📞 支持

- 📖 [完整文档](./docs/README.md)
- 💬 [提交 Issue](https://github.com/57651173/yii-link/issues)
- 📧 Email: 57651173@qq.com

---

**🎉 系统已就绪，可以开始使用！**
