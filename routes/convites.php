<?php

declare(strict_types=1);

use App\Http\Controllers\Autenticacao\ControladorConvite;
use Illuminate\Support\Facades\Route;

/**
 * Define as rotas da gestão administrativa dos convites.
 *
 * As rotas públicas de aceitação continuam definidas em `routes/auth.php`.
 * Estas operações exigem autenticação, sessão válida, endereço verificado e
 * autorização através da política dos convites.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
Route::middleware([
    'auth:sessao',
    'auth.session',
    'verified',
])
    ->prefix(
        'convites',
    )
    ->name(
        'convites.',
    )
    ->group(
        static function (): void {
            Route::get(
                '/',
                [
                    ControladorConvite::class,
                    'indice',
                ],
            )->name(
                'indice',
            );

            Route::get(
                '/criar',
                [
                    ControladorConvite::class,
                    'criar',
                ],
            )->name(
                'criar',
            );

            Route::post(
                '/',
                [
                    ControladorConvite::class,
                    'guardar',
                ],
            )->name(
                'guardar',
            );

            Route::patch(
                '/{convite}/revogar',
                [
                    ControladorConvite::class,
                    'revogar',
                ],
            )
                ->whereNumber(
                    'convite',
                )
                ->name(
                    'revogar',
                );
        },
    );
