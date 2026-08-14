<?php

declare(strict_types=1);

use App\Http\Middleware\GarantirAcessoAtivo;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Configura e cria a aplicação Laravel.
 *
 * @since 1.0.0
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
             * Restringe os pedidos aos hosts associados ao endereço
             * configurado da aplicação, protegendo também a geração de
             * endereços absolutos a partir de pedidos HTTP.
             */
            $middleware->trustHosts();

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
    ->withExceptions()
    ->create();
