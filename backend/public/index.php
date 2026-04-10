<?php

declare(strict_types=1);

use App\Middleware\CorsMiddleware;
use App\Response\ApiResponse;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\FastRoute\UrlMatcher;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// 引导：加载 env、构建容器与路由集合
['container' => $container, 'routeCollection' => $routeCollection]
    = require dirname(__DIR__) . '/bootstrap/app.php';

// ── 解析 HTTP 请求 ─────────────────────────────────────────────────
$psr17   = new Psr17Factory();
$request = (new ServerRequestCreator($psr17, $psr17, $psr17, $psr17))->fromGlobals();

// OPTIONS 预检请求（CORS）直接放行
if ($request->getMethod() === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    header('Content-Length: 0');
    exit;
}

// 自动解析 JSON 请求体
if (str_contains($request->getHeaderLine('Content-Type'), 'application/json')) {
    $raw = (string)$request->getBody();
    if ($raw !== '') {
        $parsed = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $request = $request->withParsedBody($parsed);
        }
    }
}

// ── 路由匹配 ───────────────────────────────────────────────────────
$result = (new UrlMatcher($routeCollection))->match($request);

if (!$result->isSuccess()) {
    $response = $result->isMethodFailure()
        ? ApiResponse::error('Method Not Allowed', 405, 405)
        : ApiResponse::error('接口不存在', 404, 404);
    emitResponse(withCorsHeaders($response));
    exit;
}

// 将路由参数（如 {id}）注入请求属性
foreach ($result->arguments() as $name => $value) {
    $request = $request->withAttribute($name, $value);
}

// ── 执行中间件链（AuthMiddleware + Action）─────────────────────────
$middlewareFactory = new MiddlewareFactory($container);
$result            = $result->withDispatcher(new MiddlewareDispatcher($middlewareFactory));

$fallback      = new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ApiResponse::error('请求未被处理', 500, 500);
    }
};
$routeResponse = $result->process($request, $fallback);

// ── 添加 CORS 响应头并发送 ─────────────────────────────────────────
$response = $container->get(CorsMiddleware::class)->process(
    $request,
    new class($routeResponse) implements RequestHandlerInterface {
        public function __construct(private readonly ResponseInterface $inner) {}
        public function handle(ServerRequestInterface $r): ResponseInterface { return $this->inner; }
    }
);

emitResponse($response);

// ── 辅助函数 ───────────────────────────────────────────────────────

function withCorsHeaders(ResponseInterface $response): ResponseInterface
{
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
}

function emitResponse(ResponseInterface $response): void
{
    if (!headers_sent()) {
        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
        ), true, $response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }
    }

    echo $response->getBody();
}
