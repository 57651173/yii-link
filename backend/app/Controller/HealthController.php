<?php

declare(strict_types=1);

namespace App\Controller;

use App\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * 健康检查控制器
 * 
 * 用于监控系统状态，检查各项服务是否正常。
 */
class HealthController
{
    private array $params;
    
    public function __construct(
        private readonly ConnectionInterface $db,
        array $params = [],
    ) {
        $this->params = $params;
    }
    
    /**
     * GET /health
     * 
     * 返回系统健康状态
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $startTime = microtime(true);
        
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'app' => [
                'name' => $this->params['app']['name'] ?? 'Yii-Link API',
                'version' => $this->params['app']['version'] ?? '1.0.0',
                'env' => $this->params['app']['env'] ?? 'production',
                'debug' => $this->params['app']['debug'] ?? false,
            ],
            'checks' => [],
        ];
        
        // 1. 检查数据库连接
        $dbStatus = $this->checkDatabase();
        $health['checks']['database'] = $dbStatus;
        
        // 2. 检查 Redis 连接
        $redisStatus = $this->checkRedis();
        $health['checks']['redis'] = $redisStatus;
        
        // 3. 检查磁盘空间（可选）
        $diskStatus = $this->checkDisk();
        $health['checks']['disk'] = $diskStatus;
        
        // 4. 计算响应时间
        $health['response_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
        
        // 判断整体状态
        $allHealthy = $dbStatus['status'] === 'ok';
        
        if (!$allHealthy) {
            $health['status'] = 'unhealthy';
            return ApiResponse::error('系统部分组件异常', 503, 503, $health);
        }
        
        return ApiResponse::success($health, '系统运行正常');
    }
    
    /**
     * GET /health/simple
     * 
     * 简化版健康检查（只返回状态码）
     */
    public function simple(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // 只检查数据库
            $this->db->createCommand('SELECT 1')->queryScalar();
            
            return ApiResponse::success(['status' => 'ok']);
        } catch (\Throwable $e) {
            return ApiResponse::error('unhealthy', 503, 503);
        }
    }
    
    /**
     * 检查数据库连接
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            $result = $this->db->createCommand('SELECT 1')->queryScalar();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result == 1) {
                return [
                    'status' => 'ok',
                    'message' => '数据库连接正常',
                    'response_time_ms' => $responseTime,
                ];
            }
            
            return [
                'status' => 'error',
                'message' => '数据库查询异常',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => '数据库连接失败: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * 检查 Redis 连接
     */
    private function checkRedis(): array
    {
        try {
            $redisHost = $this->params['redis']['host'] ?? 'redis';
            $redisPort = $this->params['redis']['port'] ?? 6379;
            
            $startTime = microtime(true);
            
            $redis = new \Redis();
            $connected = $redis->connect($redisHost, $redisPort, 2);
            
            if (!$connected) {
                return [
                    'status' => 'error',
                    'message' => 'Redis 连接失败',
                ];
            }
            
            $redis->ping();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'status' => 'ok',
                'message' => 'Redis 连接正常',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'warning',
                'message' => 'Redis 不可用（可选组件）: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * 检查磁盘空间
     */
    private function checkDisk(): array
    {
        try {
            $path = dirname(__DIR__, 2); // backend 目录
            $totalSpace = disk_total_space($path);
            $freeSpace = disk_free_space($path);
            $usedSpace = $totalSpace - $freeSpace;
            $usedPercent = round(($usedSpace / $totalSpace) * 100, 2);
            
            $status = 'ok';
            $message = '磁盘空间充足';
            
            if ($usedPercent > 90) {
                $status = 'warning';
                $message = '磁盘空间不足（超过90%）';
            }
            
            return [
                'status' => $status,
                'message' => $message,
                'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'used_percent' => $usedPercent,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unknown',
                'message' => '无法获取磁盘信息: ' . $e->getMessage(),
            ];
        }
    }
}
