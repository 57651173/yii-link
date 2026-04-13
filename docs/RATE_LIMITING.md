# API 限流说明

## 概述

系统实现了三级限流策略，基于 Redis 的 Token Bucket 算法，保护系统免受恶意攻击和资源滥用。

---

## 限流策略

### 1. IP 限流（最宽松）
- **限制**：100 次/分钟
- **适用**：所有请求（包括未登录用户）
- **目的**：防止 DDoS 攻击

### 2. 用户限流
- **限制**：200 次/分钟
- **适用**：已登录用户
- **目的**：防止单用户滥用

### 3. 租户限流
- **限制**：500 次/分钟
- **适用**：租户内所有用户
- **目的**：防止单租户占用过多资源

---

## 响应头

当请求接近限流时，API 会返回以下响应头：

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1681382400
```

| 响应头 | 说明 |
|--------|------|
| `X-RateLimit-Limit` | 限流上限（次数） |
| `X-RateLimit-Remaining` | 剩余可用次数 |
| `X-RateLimit-Reset` | 限流重置时间（Unix 时间戳） |

---

## 限流响应

### 超限响应示例

**HTTP 状态码**：`429 Too Many Requests`

```json
{
  "code": 429,
  "message": "请求过于频繁，请稍后重试",
  "data": {
    "type": "IP",
    "retry_after": 45
  }
}
```

**响应头**：
```http
HTTP/1.1 429 Too Many Requests
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1681382400
Retry-After: 45
```

---

## 跳过限流的路径

以下路径不受限流限制：

- `/health` - 健康检查
- `/health/simple` - 简化健康检查

---

## 测试限流

### 示例：快速发送请求

```bash
#!/bin/bash
TOKEN="your_jwt_token"

for i in {1..110}; do
  echo "请求 $i"
  curl -s -X GET "http://localhost:8000/api/v1/users" \
    -H "Authorization: Bearer $TOKEN" \
    -i | grep -E "(HTTP|X-RateLimit)"
  sleep 0.5
done
```

### 预期结果

- 前 100 次请求：正常返回（200 OK）
- 第 101 次开始：返回 429 Too Many Requests
- 1 分钟后：限流重置，可继续请求

---

## Redis 键结构

限流记录存储在 Redis 中：

```
rate_limit:ip:192.168.1.1       # IP 限流
rate_limit:user:123             # 用户限流
rate_limit:tenant:xxx           # 租户限流
```

每个键都有 TTL（Time To Live），自动过期。

---

## 调整限流参数

如需调整限流策略，修改 `RateLimitMiddleware.php`：

```php
private const LIMITS = [
    'ip' => ['max' => 100, 'decay' => 60],      // IP: 100次/分钟
    'user' => ['max' => 200, 'decay' => 60],    // 用户: 200次/分钟
    'tenant' => ['max' => 500, 'decay' => 60],  // 租户: 500次/分钟
];
```

**参数说明**：
- `max`: 最大请求次数
- `decay`: 时间窗口（秒）

---

## 生产环境建议

### 1. 根据业务调整限流
```php
// 对于 API 密集型应用
'user' => ['max' => 500, 'decay' => 60],

// 对于轻量级应用
'user' => ['max' => 50, 'decay' => 60],
```

### 2. 监控限流触发
```bash
# 查看 Redis 中的限流键
redis-cli --scan --pattern "rate_limit:*"
```

### 3. 白名单机制
对于特殊用户或 IP，可在中间件中添加白名单逻辑：

```php
// 在 RateLimitMiddleware.php 中
private const WHITELIST_IPS = [
    '127.0.0.1',
    '10.0.0.0/8',
];
```

---

## 常见问题

### Q: 如何完全禁用限流？
A: 注释掉 `bootstrap/app.php` 中的 `RateLimitMiddleware`。

### Q: 限流记录会永久保存吗？
A: 不会，每个记录都有 TTL，自动过期删除。

### Q: 可以对不同 API 设置不同限流吗？
A: 可以，在中间件中根据路径设置不同的 `max` 值。

### Q: Redis 不可用会影响系统吗？
A: 会，限流依赖 Redis。建议配置 Redis 高可用。
