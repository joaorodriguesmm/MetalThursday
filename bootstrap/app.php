<?php

declare(strict_types=1);

use App\Http\Middleware\GarantirAcessoAtivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Configura e cria a aplicação Laravel.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
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
             * O middleware é acrescentado ao grupo web para que uma conta
             * suspensa seja rejeitada mesmo quando a autenticação resulta de
             * uma sessão antiga ou de um cookie persistente.
             *
             * A posição no grupo garante que a sessão já foi inicializada.
             */
            $middleware->web(
                append: [
                    GarantirAcessoAtivo::class,
                ],
            );
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
