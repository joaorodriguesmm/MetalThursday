<?php

declare(strict_types=1);

use App\Http\Controllers\Utilizadores\ControladorUtilizador;
use Illuminate\Support\Facades\Route;

/**
 * Define as rotas da área administrativa dos utilizadores.
 *
 * Todas as rotas exigem autenticação, uma sessão válida e um endereço de
 * e-mail verificado. A autorização específica é aplicada através da política
 * dos utilizadores.
 *
 * @since 2.0.0
 *
 * @version 5.0.0
 */
Route::middleware([
    'auth:sessao',
    'auth.session',
    'verified',
])
    ->prefix(
        'utilizadores',
    )
    ->name(
        'utilizadores.',
    )
    ->group(
        static function (): void {
            Route::get(
                '/',
                [
                    ControladorUtilizador::class,
                    'indice',
                ],
            )->name(
                'indice',
            );

            Route::get(
                '/{utilizador}',
                [
                    ControladorUtilizador::class,
                    'detalhes',
                ],
            )
                ->whereNumber(
                    'utilizador',
                )
                ->name(
                    'detalhes',
                );

            Route::patch(
                '/{utilizador}/papel',
                [
                    ControladorUtilizador::class,
                    'alterarPapel',
                ],
            )
                ->whereNumber(
                    'utilizador',
                )
                ->name(
                    'alterar-papel',
                );

            Route::patch(
                '/{utilizador}/suspender',
                [
                    ControladorUtilizador::class,
                    'suspender',
                ],
            )
                ->whereNumber(
                    'utilizador',
                )
                ->name(
                    'suspender',
                );

            Route::patch(
                '/{utilizador}/reativar',
                [
                    ControladorUtilizador::class,
                    'reativar',
                ],
            )
                ->whereNumber(
                    'utilizador',
                )
                ->name(
                    'reativar',
                );

            Route::delete(
                '/{utilizador}/sessoes',
                [
                    ControladorUtilizador::class,
                    'encerrarSessoes',
                ],
            )
                ->whereNumber(
                    'utilizador',
                )
                ->name(
                    'encerrar-sessoes',
                );
        },
    );
