<?php

use App\Http\Middleware\TranslateUrlParameters;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Retorna a Aplicação.
 *
 * @since 1.0
 * @version 1.0
 */
return Application::configure(basePath: dirname(__DIR__))
       /**
        * Rotas da aplicação.
        *
        * @since 1.0
        * @version 1.0
        */
       ->withRouting(
           web: __DIR__ . '/../routes/web.php',
           commands: __DIR__ . '/../routes/console.php',
           health: '/up',
       )
       /**
        * Middlewares da aplicação.
        *
        * @since 1.0
        * @version 1.0
        */
       ->withMiddleware(function (Middleware $middleware): void {
           $middleware->web(append: [
               TranslateUrlParameters::class,
           ]);
       })
       /**
        * Exceções da aplicação.
        *
        * @since 1.0
        * @version 1.0
        */
       ->withExceptions(function (Exceptions $exceptions): void {
           //
       })->create();
