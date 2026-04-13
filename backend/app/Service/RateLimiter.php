<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Redis 限流器
 * 
 * 基于 Token Bucket 算法实现 API 限流。
 */
class RateLimiter
{
    private \Redis $redis;
    
    public function __construct(array $redisConfig)
    {
        $this->redis = new \Redis();
        $this->redis->connect(
            $redisConfig['host'] ?? 'redis',
            $redisConfig['port'] ?? 6379,
            $redisConfig['timeout'] ?? 2.0
        );
        
        if (!empty($redisConfig['password'])) {
            $this->redis->auth($redisConfig['password']);
        }
        
        if (isset($redisConfig['database'])) {
            $this->redis->select($redisConfig['database']);
        }
    }
    
    /**
     * 检查是否超过限流
     * 
     * @param string $key 限流键（如 ip:192.168.1.1, user:123, tenant:xxx）
     * @param int $maxAttempts 最大尝试次数
     * @param int $decaySeconds 时间窗口（秒）
     * @return array{allowed: bool, remaining: int, reset_at: int}
     */
    public function attempt(string $key, int $maxAttempts, int $decaySeconds): array
    {
        $cacheKey = "rate_limit:{$key}";
        $now = time();
        
        // 获取当前计数
        $current = (int)$this->redis->get($cacheKey);
        
        if ($current === 0) {
            // 首次请求，设置计数器和过期时间
            $this->redis->setex($cacheKey, $decaySeconds, 1);
            
            return [
                'allowed' => true,
                'remaining' => $maxAttempts - 1,
                'reset_at' => $now + $decaySeconds,
            ];
        }
        
        if ($current < $maxAttempts) {
            // 未超限，增加计数
            $this->redis->incr($cacheKey);
            $ttl = $this->redis->ttl($cacheKey);
            
            return [
                'allowed' => true,
                'remaining' => $maxAttempts - $current - 1,
                'reset_at' => $now + $ttl,
            ];
        }
        
        // 超限
        $ttl = $this->redis->ttl($cacheKey);
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'reset_at' => $now + $ttl,
        ];
    }
    
    /**
     * 清除限流记录
     */
    public function clear(string $key): void
    {
        $cacheKey = "rate_limit:{$key}";
        $this->redis->del($cacheKey);
    }
    
    /**
     * 获取剩余次数
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        $cacheKey = "rate_limit:{$key}";
        $current = (int)$this->redis->get($cacheKey);
        
        return max(0, $maxAttempts - $current);
    }
}
