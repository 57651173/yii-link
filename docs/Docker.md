## Docker 使用说明（本项目）

本项目使用 `docker compose` 一键启动开发环境，包含：
- **php**：PHP-FPM（运行后端代码）
- **nginx**：Web 入口（反向代理到 PHP-FPM）
- **mysql**：数据库（带 healthcheck）
- **redis**：缓存（可选）

> 说明：`nginx/mysql/redis` 都使用我们自己的 `Dockerfile` 构建；其中 Nginx 的配置目录 `docker/nginx/conf.d/` 通过挂载注入到容器（不打进镜像）。

### 前置条件
- 安装 Docker Desktop（macOS/Windows）或 Docker Engine（Linux）
- 确保本机有 `docker` 与 `docker compose` 命令

### 1. 初始化环境变量

项目根目录提供了 `.env.example`，请复制为 `.env` 后按需修改：

```bash
cp .env.example .env
```

常用配置项（在 `.env` 里改）：
- **`NGINX_HTTP_PORT`**：对外访问端口（默认 `8000`）
- **`MYSQL_PORT`**：MySQL 映射到宿主机端口（默认 `3306`）
- **`DB_NAME/DB_USER/DB_PASS`**：数据库名、账号、密码
- **`JWT_SECRET/JWT_EXPIRE`**：JWT 密钥与过期时间

### 2. 构建镜像

首次启动或 Dockerfile 变更后建议先构建：

```bash
docker compose build
```

### 3. 启动服务

后台启动全部服务：

```bash
docker compose up -d
```

查看运行状态：

```bash
docker compose ps
```

查看日志（示例：Nginx）：

```bash
docker compose logs -f nginx
```

停止并移除容器（保留数据卷目录）：

```bash
docker compose down
```

### 4. 访问项目

默认使用：
- **Web**：`http://localhost:${NGINX_HTTP_PORT:-8000}`

### 5. 数据库迁移（Migration）

本项目把迁移命令与 `php` 共用同一个容器执行。

执行迁移：

```bash
docker compose exec php php yii migrate/up --no-interaction
```

回滚最近一次迁移：

```bash
docker compose exec php php yii migrate/down --no-interaction
```

查看迁移历史：

```bash
docker compose exec php php yii migrate/history
```

> 说明：项目的 `backend/yii` 已做兼容，你可以用 `migrate/up`（斜杠风格），它会自动映射到 `migrate:up`（冒号风格）。

### 6. Nginx 配置（conf.d 目录挂载）

Nginx 配置目录在宿主机：
- `docker/nginx/conf.d/`

容器内路径：
- `/etc/nginx/conf.d/`

修改配置后，重载 Nginx：

```bash
docker compose exec nginx nginx -s reload
```

### 7. MySQL 数据持久化

MySQL 数据目录映射到：
- `docker/mysql/data/`

所以 **`docker compose down` 不会清空数据**。如需重置数据库，请停止服务后清空该目录（谨慎操作）。

### 8. Redis 数据持久化（AOF）

Redis 数据目录映射到：
- `docker/redis/data/`

Redis 配置文件为：
- `docker/redis/redis.conf`

如需启用 Redis 密码，可在 `redis.conf` 中设置 `requirepass`（生产环境建议启用）。

### 9. 常见问题排查

#### 9.1 端口被占用
如果 `8000/3306/6379` 被占用，修改 `.env` 里的：
- `NGINX_HTTP_PORT`
- `MYSQL_PORT`
- `REDIS_PORT`

然后重启：

```bash
docker compose up -d
```

#### 9.2 PHP 容器没起来 / 依赖服务未就绪
`php` 会等待 `mysql` 通过 healthcheck 后再启动。你可以看 mysql 日志：

```bash
docker compose logs -f mysql
```

