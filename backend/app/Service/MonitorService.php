<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 监控服务
 * 
 * 记录性能指标、发送告警、记录慢查询
 */
class MonitorService
{
    private string $logPath;
    private bool $enabled;
    
    public function __construct(array $config = [])
    {
        $this->logPath = $config['log_path'] ?? __DIR__ . '/../../runtime/logs/monitor.log';
        $this->enabled = $config['enabled'] ?? true;
    }
    
    /**
     * 记录性能指标
     */
    public function recordMetric(string $name, float $value, array $tags = []): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $data = [
            'type' => 'metric',
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        $this->writeLog($data);
    }
    
    /**
     * 发送告警
     */
    public function sendAlert(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $data = [
            'type' => 'alert',
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        $this->writeLog($data);
        
        // 在这里可以集成钉钉、企业微信、Slack 等告警渠道
        // $this->sendToDingTalk($level, $message, $context);
    }
    
    /**
     * 记录慢查询
     */
    public function logSlowQuery(string $sql, float $duration, array $bindings = []): void
    {
        if (!$this->enabled || $duration < 1000) { // < 1 秒不记录
            return;
        }
        
        $data = [
            'type' => 'slow_query',
            'sql' => $sql,
            'duration_ms' => $duration,
            'bindings' => $bindings,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        $this->writeLog($data);
        
        // 如果超过 3 秒，发送告警
        if ($duration > 3000) {
            $this->sendAlert('warning', "Slow query detected: {$duration}ms", [
                'sql' => $sql,
            ]);
        }
    }
    
    /**
     * 记录 API 响应时间
     */
    public function recordApiResponse(string $method, string $path, int $statusCode, float $duration): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $this->recordMetric('api.response_time', $duration, [
            'method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
        ]);
        
        // 如果响应时间超过 1 秒，记录告警
        if ($duration > 1000) {
            $this->sendAlert('info', "Slow API response: {$method} {$path} took {$duration}ms");
        }
    }
    
    /**
     * 记录错误率
     */
    public function recordError(string $type, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }
        
        $data = [
            'type' => 'error',
            'error_type' => $type,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        $this->writeLog($data);
    }
    
    /**
     * 写入日志
     */
    private function writeLog(array $data): void
    {
        try {
            $logDir = dirname($this->logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            
            $logLine = json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
            @file_put_contents($this->logPath, $logLine, FILE_APPEND);
        } catch (\Throwable $e) {
            error_log("Failed to write monitor log: " . $e->getMessage());
        }
    }
    
    /**
     * 获取监控统计（示例）
     */
    public function getStats(string $startDate, string $endDate): array
    {
        // 这里可以实现读取日志并生成统计报告
        // 或者从 Prometheus/InfluxDB 等监控系统获取数据
        
        return [
            'api_requests' => 0,
            'avg_response_time' => 0,
            'error_count' => 0,
            'slow_queries' => 0,
        ];
    }
}
