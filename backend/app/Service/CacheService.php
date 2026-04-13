<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Redis 缓存服务
 * 
 * 提供统一的缓存接口，支持常见的缓存操作。
 */
class CacheService
{
    private \Redis $redis;
    private string $prefix;
    
    public function __construct(array $redisConfig, string $prefix = 'cache:')
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
        
        $this->prefix = $prefix;
    }
    
    /**
     * 获取缓存键（添加前缀）
     */
    private function getKey(string $key): string
    {
        return $this->prefix . $key;
    }
    
    /**
     * 获取缓存
     * 
     * @return mixed|null
     */
    public function get(string $key)
    {
        $value = $this->redis->get($this->getKey($key));
        
        if ($value === false) {
            return null;
        }
        
        return $this->unserialize($value);
    }
    
    /**
     * 设置缓存
     * 
     * @param mixed $value 要缓存的值
     * @param int $ttl 过期时间（秒），0 表示永不过期
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $serialized = $this->serialize($value);
        
        if ($ttl > 0) {
            return $this->redis->setex($this->getKey($key), $ttl, $serialized);
        }
        
        return $this->redis->set($this->getKey($key), $serialized);
    }
    
    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        return (bool)$this->redis->del($this->getKey($key));
    }
    
    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        return (bool)$this->redis->exists($this->getKey($key));
    }
    
    /**
     * 获取或设置缓存（如果不存在则调用回调函数）
     * 
     * @param string $key
     * @param callable $callback 当缓存不存在时调用此回调获取数据
     * @param int $ttl
     * @return mixed
     */
    public function remember(string $key, callable $callback, int $ttl = 3600)
    {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * 批量删除（按模式）
     * 
     * 例如：clear('user:*') 删除所有以 user: 开头的缓存
     */
    public function clear(string $pattern): int
    {
        $fullPattern = $this->getKey($pattern);
        $keys = $this->redis->keys($fullPattern);
        
        if (empty($keys)) {
            return 0;
        }
        
        return $this->redis->del(...$keys);
    }
    
    /**
     * 增加计数
     */
    public function increment(string $key, int $value = 1): int
    {
        return $this->redis->incrBy($this->getKey($key), $value);
    }
    
    /**
     * 减少计数
     */
    public function decrement(string $key, int $value = 1): int
    {
        return $this->redis->decrBy($this->getKey($key), $value);
    }
    
    /**
     * 序列化
     */
    private function serialize($value): string
    {
        return serialize($value);
    }
    
    /**
     * 反序列化
     */
    private function unserialize(string $value)
    {
        return unserialize($value);
    }
    
    /**
     * 清空所有缓存（谨慎使用）
     */
    public function flush(): bool
    {
        return $this->redis->flushDB();
    }
}
