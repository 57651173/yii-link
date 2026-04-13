<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\RateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * RateLimiter 单元测试
 */
class RateLimiterTest extends TestCase
{
    private RateLimiter $rateLimiter;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 使用测试 Redis 配置
        $redisConfig = [
            'host' => $_ENV['REDIS_HOST'] ?? 'redis',
            'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'timeout' => 2.0,
        ];
        
        $this->rateLimiter = new RateLimiter($redisConfig);
    }
    
    public function testRateLimitAllow(): void
    {
        $key = 'test_user_' . time();
        
        // 第一次请求应该允许
        $result = $this->rateLimiter->attempt($key, 10, 60);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(9, $result['remaining']);
    }
    
    public function testRateLimitExceeded(): void
    {
        $key = 'test_limit_' . time();
        $maxAttempts = 3;
        
        // 发送 3 次请求（达到上限）
        for ($i = 0; $i < $maxAttempts; $i++) {
            $result = $this->rateLimiter->attempt($key, $maxAttempts, 60);
            $this->assertTrue($result['allowed']);
        }
        
        // 第 4 次请求应该被拒绝
        $result = $this->rateLimiter->attempt($key, $maxAttempts, 60);
        
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }
    
    public function testClearRateLimit(): void
    {
        $key = 'test_clear_' . time();
        
        // 发送请求
        $this->rateLimiter->attempt($key, 10, 60);
        
        // 清除限流记录
        $this->rateLimiter->clear($key);
        
        // 再次请求应该从头开始计数
        $result = $this->rateLimiter->attempt($key, 10, 60);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(9, $result['remaining']);
    }
    
    public function testRemainingCount(): void
    {
        $key = 'test_remaining_' . time();
        $maxAttempts = 10;
        
        // 发送 3 次请求
        for ($i = 0; $i < 3; $i++) {
            $this->rateLimiter->attempt($key, $maxAttempts, 60);
        }
        
        // 检查剩余次数
        $remaining = $this->rateLimiter->remaining($key, $maxAttempts);
        
        $this->assertEquals(7, $remaining);
    }
    
    protected function tearDown(): void
    {
        // 清理测试数据
        parent::tearDown();
    }
}
