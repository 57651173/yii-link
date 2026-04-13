## Docker 使用说明（本项目）

本项目使用 `docker compose` 一键启动开发环境，包含：
- **backend**：PHP-FPM（运行后端代码）
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

执行迁移：

```bash
docker compose exec backend php yii migrate/up -y
```

回滚最近一次迁移：

```bash
docker compose exec backend php yii migrate/down -y
```

查看迁移历史：

```bash
docker compose exec backend php yii migrate/history
```

> 说明：项目的 `backend/yii` 已做兼容，你可以用 `migrate/up`（斜杠风格），它会自动映射到 `migrate:up`（冒号风格）。

### 5.1 初始数据（Seeds）

表结构由迁移创建；**演示账号等初始数据**放在 `backend/database/seeds/`，需单独执行：

```bash
docker compose exec backend php yii seed:run
```

演示账号的明文密码写在 `backend/database/seeds/M*.php` 源码常量中（入库仍为 bcrypt）。种子按文件名 `M…` 升序执行，可重复执行：相同邮箱已存在则跳过插入。

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

#### 9.2 Backend 容器没起来 / 依赖服务未就绪
`backend` 会等待 `mysql` 通过 healthcheck 后再启动。你可以看 mysql 日志：

```bash
docker compose logs -f mysql
```

#### 9.3 迁移或接口报错：`getaddrinfo for mysql failed: Name does not resolve`

说明 **`backend` 容器里解析不到主机名 `mysql`**，几乎总是 **MySQL 容器没和 backend 在同一个 Docker 网络上**（常见于历史容器、网络名变化、手动改过容器）。

**推荐修复（重建网络与容器）：**

```bash
docker compose down
docker compose up -d --force-recreate
```

**自检（应能看到 `mysql` 与 `backend` 在同一网络里）：**

```bash
docker network inspect yii-link-net --format '{{range .Containers}}{{.Name}} {{end}}'
```

若列表里**没有** `mysql`，说明 MySQL 未挂到 `yii-link-net`，请再执行上面的 `down` + `up --force-recreate`。

