<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\CacheService;
use PHPUnit\Framework\TestCase;

/**
 * CacheService 单元测试
 */
class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $redisConfig = [
            'host' => $_ENV['REDIS_HOST'] ?? 'redis',
            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'timeout' => 2.0,
        ];
        
        $this->cacheService = new CacheService($redisConfig, 'test:');
    }
    
    public function testSetAndGet(): void
    {
        $key = 'test_key_' . time();
        $value = ['name' => 'test', 'age' => 25];
        
        // 设置缓存
        $result = $this->cacheService->set($key, $value, 60);
        $this->assertTrue($result);
        
        // 获取缓存
        $cached = $this->cacheService->get($key);
        $this->assertEquals($value, $cached);
    }
    
    public function testHas(): void
    {
        $key = 'test_has_' . time();
        
        $this->assertFalse($this->cacheService->has($key));
        
        $this->cacheService->set($key, 'value', 60);
        
        $this->assertTrue($this->cacheService->has($key));
    }
    
    public function testDelete(): void
    {
        $key = 'test_delete_' . time();
        
        $this->cacheService->set($key, 'value', 60);
        $this->assertTrue($this->cacheService->has($key));
        
        $this->cacheService->delete($key);
        $this->assertFalse($this->cacheService->has($key));
    }
    
    public function testRemember(): void
    {
        $key = 'test_remember_' . time();
        $callCount = 0;
        
        $callback = function() use (&$callCount) {
            $callCount++;
            return 'computed_value';
        };
        
        // 第一次调用：缓存不存在，执行回调
        $result1 = $this->cacheService->remember($key, $callback, 60);
        $this->assertEquals('computed_value', $result1);
        $this->assertEquals(1, $callCount);
        
        // 第二次调用：缓存存在，不执行回调
        $result2 = $this->cacheService->remember($key, $callback, 60);
        $this->assertEquals('computed_value', $result2);
        $this->assertEquals(1, $callCount); // 回调没有被再次执行
    }
    
    public function testIncrement(): void
    {
        $key = 'test_increment_' . time();
        
        $value1 = $this->cacheService->increment($key, 1);
        $this->assertEquals(1, $value1);
        
        $value2 = $this->cacheService->increment($key, 5);
        $this->assertEquals(6, $value2);
    }
    
    public function testDecrement(): void
    {
        $key = 'test_decrement_' . time();
        
        $this->cacheService->increment($key, 10);
        
        $value = $this->cacheService->decrement($key, 3);
        $this->assertEquals(7, $value);
    }
    
    protected function tearDown(): void
    {
        // 清理测试数据
        $this->cacheService->clear('test_*');
        parent::tearDown();
    }
}
