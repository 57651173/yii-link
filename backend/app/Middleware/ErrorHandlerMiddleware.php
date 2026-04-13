<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exception\BusinessException;
use App\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 全局错误处理中间件
 * 
 * 捕获所有未处理的异常，返回统一的错误响应。
 * 开发环境显示详细错误信息，生产环境隐藏敏感信息。
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private bool $debug;
    private string $env;
    private string $logPath;
    
    public function __construct(array $params = [])
    {
        $this->debug = $params['app']['debug'] ?? false;
        $this->env = $params['app']['env'] ?? 'production';
        $this->logPath = $params['log']['path'] ?? __DIR__ . '/../../runtime/logs/app.log';
    }
    
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (BusinessException $e) {
            // 业务异常：直接返回
            $this->logError($request, $e, 'business');
            return ApiResponse::error($e->getMessage(), $e->getStatusCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            // 系统异常：记录详细日志
            $this->logError($request, $e, 'system');
            
            // 开发环境：返回详细错误信息
            if ($this->debug || $this->env === 'development') {
                return ApiResponse::error(
                    '系统错误: ' . $e->getMessage(),
                    500,
                    500,
                    [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }
            
            // 生产环境：返回通用错误信息
            return ApiResponse::error('服务器内部错误，请稍后重试', 500, 500);
        }
    }
    
    /**
     * 记录错误日志
     */
    private function logError(ServerRequestInterface $request, \Throwable $e, string $type): void
    {
        try {
            $logDir = dirname($this->logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            
            $logMessage = sprintf(
                "[%s] [%s] %s:%d %s\nRequest: %s %s\nTrace:\n%s\n\n",
                date('Y-m-d H:i:s'),
                strtoupper($type),
                $e->getFile(),
                $e->getLine(),
                $e->getMessage(),
                $request->getMethod(),
                $request->getUri()->getPath(),
                $e->getTraceAsString()
            );
            
            @file_put_contents($this->logPath, $logMessage, FILE_APPEND);
        } catch (\Throwable $logException) {
            // 日志记录失败不应该抛出异常
            error_log("Failed to write error log: " . $logException->getMessage());
        }
    }
}
