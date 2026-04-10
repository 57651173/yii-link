<?php

declare(strict_types=1);

namespace App\Response;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * 统一 API 响应工具类
 *
 * 所有接口的返回格式必须通过此类构建，确保响应结构一致。
 *
 * 响应格式：
 * {
 *   "code": 0,          // 0 表示成功，非 0 表示错误
 *   "message": "success",
 *   "data": {}
 * }
 */
class ApiResponse
{
    /**
     * 成功响应
     */
    public static function success(mixed $data = null, string $message = 'success', int $httpCode = 200): ResponseInterface
    {
        return self::json([
            'code'    => 0,
            'message' => $message,
            'data'    => $data,
        ], $httpCode);
    }

    /**
     * 分页数据响应
     */
    public static function paginate(array $items, int $total, int $page, int $pageSize): ResponseInterface
    {
        return self::success([
            'items'     => $items,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
            'pages'     => (int)ceil($total / $pageSize),
        ]);
    }

    /**
     * 错误响应
     */
    public static function error(string $message, int $code = 400, int $httpCode = 400): ResponseInterface
    {
        return self::json([
            'code'    => $code,
            'message' => $message,
            'data'    => null,
        ], $httpCode);
    }

    /**
     * 未授权响应（401）
     */
    public static function unauthorized(string $message = '未授权，请先登录'): ResponseInterface
    {
        return self::error($message, 401, 401);
    }

    /**
     * 禁止访问响应（403）
     */
    public static function forbidden(string $message = '无权限访问'): ResponseInterface
    {
        return self::error($message, 403, 403);
    }

    /**
     * 资源不存在响应（404）
     */
    public static function notFound(string $message = '资源不存在'): ResponseInterface
    {
        return self::error($message, 404, 404);
    }

    /**
     * 构建 JSON 响应
     */
    private static function json(array $data, int $statusCode): ResponseInterface
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new Response(
            status: $statusCode,
            headers: ['Content-Type' => 'application/json; charset=utf-8'],
            body: $body,
        );
    }
}
