<?php

declare(strict_types=1);

/**
 * 路由注册文件
 *
 * 返回一个闭包，接收 RouteCollector 并向其中注册所有路由。
 * 新增模块时，在此文件追加路由即可，无需修改入口文件。
 *
 * 路由规则：
 *  - 公开接口：直接 ->action(...)
 *  - 需要认证：先 ->middleware(AuthMiddleware::class)，再 ->action(...)
 */

use App\Controller\V1\AuthController;
use App\Controller\V1\UserController;
use App\Middleware\AuthMiddleware;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollector;

return static function (RouteCollector $collector): void {

    // ── 公开接口（无需登录）────────────────────────────────────────
    $collector->addRoute(
        Route::post('/api/v1/login')
            ->action([AuthController::class, 'login'])
            ->name('auth.login'),
    );

    // ── 需要 JWT 认证的接口 ────────────────────────────────────────
    $collector->addRoute(

        // 当前用户信息
        Route::get('/api/v1/me')
            ->middleware(AuthMiddleware::class)
            ->action([AuthController::class, 'me'])
            ->name('auth.me'),

        // 用户列表
        Route::get('/api/v1/users')
            ->middleware(AuthMiddleware::class)
            ->action([UserController::class, 'index'])
            ->name('user.index'),

        // 用户详情
        Route::get('/api/v1/users/{id:\d+}')
            ->middleware(AuthMiddleware::class)
            ->action([UserController::class, 'show'])
            ->name('user.show'),

        // 创建用户
        Route::post('/api/v1/users')
            ->middleware(AuthMiddleware::class)
            ->action([UserController::class, 'create'])
            ->name('user.create'),

        // 更新用户
        Route::put('/api/v1/users/{id:\d+}')
            ->middleware(AuthMiddleware::class)
            ->action([UserController::class, 'update'])
            ->name('user.update'),

        // 删除用户
        Route::delete('/api/v1/users/{id:\d+}')
            ->middleware(AuthMiddleware::class)
            ->action([UserController::class, 'delete'])
            ->name('user.delete'),
    );
};
