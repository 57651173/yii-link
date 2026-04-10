<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;

/**
 * CORS 跨域中间件
 *
 * 处理跨域请求，允许前端 Vue 应用跨域调用后端 API。
 * 对 OPTIONS 预检请求直接返回 200，避免被拦截。
 */
class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;

    public function __construct(array $params = [])
    {
        $this->allowedOrigins = $params['cors']['allowed_origins'] ?? ['*'];
        $this->allowedMethods = $params['cors']['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $this->allowedHeaders = $params['cors']['allowed_headers'] ?? ['Content-Type', 'Authorization', 'X-Requested-With'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // OPTIONS 预检请求直接返回
        if ($request->getMethod() === 'OPTIONS') {
            return $this->addCorsHeaders(new Response(200));
        }

        $response = $handler->handle($request);

        return $this->addCorsHeaders($response);
    }

    private function addCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        $origin = implode(', ', $this->allowedOrigins);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders))
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Max-Age', '86400');
    }
}
