## Yii-Link（Yii3 API 脚手架）

一个基于 **Yii3** 的后端 API 项目骨架，面向 **ERP/WMS/SaaS 后台服务**场景，采用 **API First + 分层架构 + DDD（领域驱动设计）**，内置 JWT 认证、用户示例、数据库迁移等基础能力。

### 项目结构

```text
.
├── backend/              # 后端代码（Yii3 / PHP）
├── docker/               # 各服务 Dockerfile/配置
├── docker-compose.yml    # 一键启动开发环境
├── docs/
│   └── Docker.md         # Docker 使用文档（推荐先看）
├── .env.example          # 环境变量模板（复制为 .env）
└── README.md
```

### 快速开始（推荐：Docker）

1) 初始化环境变量（根目录）

```bash
cp .env.example .env
```

2) 构建并启动

```bash
docker compose build
docker compose up -d
```

3) 执行数据库迁移（在 `php` 容器内）

```bash
docker compose exec php php yii migrate/up --no-interaction
```

4) 访问服务

- Web：`http://localhost:${NGINX_HTTP_PORT:-8000}`

### 文档

- **Docker 使用与常见问题**：见 `docs/Docker.md`
- **后端详细说明（目录/接口/迁移等）**：见 `backend/README.md`

### 常用命令速查

```bash
# 查看容器状态
docker compose ps

# 查看日志（示例：nginx）
docker compose logs -f nginx

# 重载 Nginx（修改 docker/nginx/conf.d 后）
docker compose exec nginx nginx -s reload

# 进入 php 容器执行命令
docker compose exec php sh
```

