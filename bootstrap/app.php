<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Configura e cria a aplicação Laravel.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
return Application::configure(
    basePath: dirname(__DIR__),
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(
        static function (
            Middleware $middleware,
        ): void {
            /*
             * Os middlewares personalizados serão registados aqui quando
             * existirem.
             */
        },
    )
    ->withExceptions(
        static function (
            Exceptions $exceptions,
        ): void {
            /*
             * A configuração personalizada das exceções será registada aqui
             * quando for necessária.
             *
             * Este bloco não deve ser removido porque também regista o
             * ExceptionHandler predefinido do Laravel no contentor.
             */
        },
    )
    ->create();
