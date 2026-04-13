# 租户配额管理

## 概述

租户配额系统用于限制每个租户的资源使用，适用于 SaaS 商业模式。支持用户数、存储空间、API 调用次数等多维度配额。

---

## 配额类型

### 1. 用户数配额
- **字段**：`quota_users`
- **默认值**：100
- **说明**：租户最多可创建的用户数
- **特殊值**：0 表示无限制

### 2. 存储空间配额
- **字段**：`quota_storage_mb`
- **默认值**：1024 (1GB)
- **说明**：租户可使用的存储空间（MB）
- **特殊值**：0 表示无限制

### 3. API 调用配额
- **字段**：`quota_api_calls`
- **默认值**：10000
- **说明**：租户每日 API 调用次数限制
- **特殊值**：0 表示无限制

---

## 配额检查

### 自动检查

系统会在以下时机自动检查配额：

1. **创建用户时** - 检查用户数配额
2. **每次 API 调用时** - 检查 API 调用配额
3. **上传文件时** - 检查存储空间配额（待实现）

### 超限响应

**HTTP 状态码**：`429 Too Many Requests`

```json
{
  "code": 429,
  "message": "用户数已达配额上限",
  "data": {
    "quota_type": "users",
    "current": 100,
    "quota": 100
  }
}
```

---

## 配额管理 API

### 1. 查询配额使用情况

**请求**：
```http
GET /api/v1/tenants/{tenant_code}/quota
Authorization: Bearer {token}
```

**响应示例**：
```json
{
  "code": 0,
  "data": {
    "users": {
      "quota": 100,
      "used": 45,
      "remaining": 55,
      "unlimited": false
    },
    "storage": {
      "quota_mb": 1024,
      "used_mb": 256,
      "unlimited": false
    },
    "api_calls": {
      "quota_daily": 10000,
      "remaining_today": 7523,
      "unlimited": false
    }
  }
}
```

### 2. 更新配额（平台管理员）

**请求**：
```http
PUT /api/v1/tenants/{tenant_code}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "quota_users": 200,
  "quota_storage_mb": 2048,
  "quota_api_calls": 20000
}
```

---

## 数据库结构

```sql
ALTER TABLE sys_tenants ADD COLUMN:
- quota_users INT UNSIGNED DEFAULT 100
- quota_storage_mb INT UNSIGNED DEFAULT 1024
- quota_api_calls INT UNSIGNED DEFAULT 10000
```

---

## 配额套餐设计（示例）

### 免费套餐
```sql
UPDATE sys_tenants SET 
  quota_users = 10,
  quota_storage_mb = 100,
  quota_api_calls = 1000
WHERE plan = 'free';
```

### 基础套餐
```sql
UPDATE sys_tenants SET 
  quota_users = 50,
  quota_storage_mb = 1024,
  quota_api_calls = 10000
WHERE plan = 'basic';
```

### 专业套餐
```sql
UPDATE sys_tenants SET 
  quota_users = 200,
  quota_storage_mb = 5120,
  quota_api_calls = 50000
WHERE plan = 'pro';
```

### 企业套餐
```sql
UPDATE sys_tenants SET 
  quota_users = 0,        -- 无限制
  quota_storage_mb = 0,   -- 无限制
  quota_api_calls = 0     -- 无限制
WHERE plan = 'enterprise';
```

---

## 实现细节

### 用户数检查

```php
// app/Service/QuotaService.php
public function checkUserQuota(): array
{
    $tenant = $this->db->query('SELECT quota_users FROM sys_tenants...');
    $currentUsers = $this->db->query('SELECT COUNT(*) FROM sys_users...');
    
    if ($currentUsers >= $quota && $quota > 0) {
        return ['allowed' => false, 'message' => '用户数已达配额上限'];
    }
    
    return ['allowed' => true];
}
```

### API 调用检查

```php
// 使用 RateLimiter 实现每日配额
$result = $this->rateLimiter->attempt(
    "tenant_api_daily:{$tenantId}",
    $quotaApiCalls,
    86400  // 24小时
);
```

---

## 配额监控

### 1. 查询接近配额上限的租户

```sql
SELECT 
    t.code AS tenant_code,
    t.name AS tenant_name,
    t.quota_users,
    (SELECT COUNT(*) FROM sys_users WHERE tenant_id = t.id) AS current_users,
    ROUND((SELECT COUNT(*) FROM sys_users WHERE tenant_id = t.id) * 100.0 / t.quota_users, 2) AS usage_percent
FROM sys_tenants t
WHERE t.quota_users > 0
HAVING usage_percent > 80
ORDER BY usage_percent DESC;
```

### 2. Redis 中的 API 调用记录

```bash
# 查看租户的 API 调用次数
redis-cli GET "tenant_api_daily:xxx"

# 查看所有租户的 API 调用记录
redis-cli --scan --pattern "tenant_api_daily:*"
```

---

## 升级配额流程

### 场景：租户申请升级套餐

1. **用户提交升级请求**
2. **管理员审核通过**
3. **更新租户配额**：
```sql
UPDATE sys_tenants SET 
  quota_users = 200,
  quota_storage_mb = 5120,
  quota_api_calls = 50000,
  plan = 'pro',
  updated_at = NOW()
WHERE code = 'tenant_code';
```
4. **通知用户升级成功**

---

## 自动告警

### 配额使用率告警

```php
// 定时任务：检查配额使用情况
foreach ($tenants as $tenant) {
    $usage = $quotaService->getQuotaUsage();
    
    if ($usage['users']['used'] / $usage['users']['quota'] > 0.9) {
        // 发送告警邮件
        $mailer->send([
            'to' => $tenant->admin_email,
            'subject' => '配额告警：用户数即将达到上限',
            'body' => '您的用户数已使用 90%，请及时升级套餐。',
        ]);
    }
}
```

---

## 常见问题

### Q: 如何设置无限制配额？
A: 将配额字段设置为 `0`。

### Q: 配额是硬限制还是软限制？
A: 硬限制，超过配额后无法继续操作。

### Q: 可以临时提升配额吗？
A: 可以，直接更新数据库中的配额字段即可生效。

### Q: 配额重置时间是什么时候？
A: API 调用配额每 24 小时重置，用户数和存储配额不重置。

---

## 扩展建议

### 1. 配额预警
- 使用率达到 80% 时发送提醒
- 使用率达到 90% 时发送警告

### 2. 配额历史记录
- 记录配额变更历史
- 记录配额超限次数

### 3. 配额分析
- 统计各租户配额使用趋势
- 预测配额需求

### 4. 自动升级
- 根据使用情况自动推荐升级套餐
- 提供一键升级功能
