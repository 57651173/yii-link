<?php

declare(strict_types=1);

use App\Controller\V1\AuthController;
use App\Controller\V1\UserController;
use App\Middleware\AuthMiddleware;
use Psr\Container\ContainerInterface;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollector;

/**
 * API 路由定义
 *
 * 所有路由以 /api/v1 为前缀。
 * 需要认证的路由会附加 AuthMiddleware 中间件类名。
 * 中间件通过字符串类名引用，由中间件调度器从容器中实例化。
 */
return static function (ContainerInterface $container): RouteCollectionInterface {
    $collector = new RouteCollector();

    // ==================== 公开接口（无需认证）====================

    $collector->addRoute(
        Route::post('/api/v1/login')
            ->action([AuthController::class, 'login'])
            ->name('auth.login'),
    );

    // ==================== 需要认证的接口（附加 AuthMiddleware）====================

    $collector->addRoute(
        Route::get('/api/v1/me')
            ->action([AuthController::class, 'me'])
            ->middleware(AuthMiddleware::class)
            ->name('auth.me'),

        Route::get('/api/v1/users')
            ->action([UserController::class, 'index'])
            ->middleware(AuthMiddleware::class)
            ->name('user.index'),

        Route::get('/api/v1/users/{id:\d+}')
            ->action([UserController::class, 'show'])
            ->middleware(AuthMiddleware::class)
            ->name('user.show'),

        Route::post('/api/v1/users')
            ->action([UserController::class, 'create'])
            ->middleware(AuthMiddleware::class)
            ->name('user.create'),

        Route::put('/api/v1/users/{id:\d+}')
            ->action([UserController::class, 'update'])
            ->middleware(AuthMiddleware::class)
            ->name('user.update'),

        Route::delete('/api/v1/users/{id:\d+}')
            ->action([UserController::class, 'delete'])
            ->middleware(AuthMiddleware::class)
            ->name('user.delete'),
    );

    return new RouteCollection($collector);
};
