<?php

declare(strict_types=1);

use App\Http\Controllers\MetalThursday\ControladorMetalThursday;
use App\Http\Middleware\MetalThursday\ContextualizarReservaMetalThursday;
use Illuminate\Support\Facades\Route;

/**
 * Define as rotas explícitas de preparação de reservas de MetalThursday.
 *
 * A mesma reserva identifica o formulário e a respetiva submissão. O
 * middleware confirma o responsável e torna a data e o autor autoritativos
 * antes da validação.
 *
 * @since 2.0.0
 */
Route::middleware([
    'auth:sessao',
    'auth.session',
    'verified',
])
    ->controller(
        ControladorMetalThursday::class,
    )
    ->prefix(
        'metal-thursday/reservas',
    )
    ->name(
        'metal-thursday.reservas.',
    )
    ->group(
        static function (): void {
            Route::middleware(
                ContextualizarReservaMetalThursday::class,
            )
                ->group(
                    static function (): void {
                        Route::get(
                            '{reservaMetalThursday}/preparar',
                            'prepararReserva',
                        )
                            ->whereNumber(
                                'reservaMetalThursday',
                            )
                            ->name(
                                'preparar',
                            );

                        Route::post(
                            '{reservaMetalThursday}',
                            'guardarReserva',
                        )
                            ->whereNumber(
                                'reservaMetalThursday',
                            )
                            ->name(
                                'guardar',
                            );
                    },
                );
        },
    );
