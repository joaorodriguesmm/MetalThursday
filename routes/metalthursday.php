<?php

declare(strict_types=1);

use App\Http\Controllers\Interacoes\ControladorAudicao;
use App\Http\Controllers\Interacoes\ControladorAvaliacao;
use App\Http\Controllers\Interacoes\ControladorComentario;
use App\Http\Controllers\Interacoes\ControladorGosto;
use App\Http\Controllers\MetalThursday\ControladorEdicao;
use App\Http\Controllers\MetalThursday\ControladorMetalThursday;
use App\Http\Controllers\Musica\ControladorBanda;
use App\Http\Controllers\Musica\ControladorGenero;
use App\Http\Controllers\Utilizadores\ControladorNotificacao;
use Illuminate\Support\Facades\Route;

/**
 * Define as rotas principais da aplicação MetalThursday.
 *
 * Todas as rotas deste ficheiro exigem autenticação, uma sessão autenticada
 * válida e um endereço de e-mail verificado.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
Route::middleware([
    'auth',
    'auth.session',
    'verified',
])->group(
    static function (): void {
        /*
        |--------------------------------------------------------------------------
        | Página inicial
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/',
            [
                ControladorMetalThursday::class,
                'index',
            ],
        )->name(
            'inicio',
        );

        /*
        |--------------------------------------------------------------------------
        | MetalThursday
        |--------------------------------------------------------------------------
        */

        Route::controller(
            ControladorMetalThursday::class,
        )
            ->prefix(
                'metal-thursday',
            )
            ->name(
                'metal-thursday.',
            )
            ->group(
                static function (): void {
                    Route::get(
                        'criar',
                        'create',
                    )->name(
                        'criar',
                    );

                    Route::post(
                        '/',
                        'store',
                    )->name(
                        'guardar',
                    );

                    Route::get(
                        '{metalThursday}',
                        'show',
                    )
                        ->whereNumber(
                            'metalThursday',
                        )
                        ->name(
                            'detalhes',
                        );

                    Route::get(
                        '{metalThursday}/editar',
                        'edit',
                    )
                        ->whereNumber(
                            'metalThursday',
                        )
                        ->name(
                            'editar',
                        );

                    Route::patch(
                        '{metalThursday}',
                        'update',
                    )
                        ->whereNumber(
                            'metalThursday',
                        )
                        ->name(
                            'atualizar',
                        );

                    Route::delete(
                        '{metalThursday}',
                        'destroy',
                    )
                        ->whereNumber(
                            'metalThursday',
                        )
                        ->name(
                            'eliminar',
                        );
                },
            );

        Route::get(
            'utilizadores/ha-mais-tempo-sem-nomeacao',
            [
                ControladorMetalThursday::class,
                'obterUtilizadorHaMaisTempoSemNomeacao',
            ],
        )->name(
            'utilizadores.ha-mais-tempo-sem-nomeacao',
        );

        /*
        |--------------------------------------------------------------------------
        | Bandas
        |--------------------------------------------------------------------------
        */

        Route::controller(
            ControladorBanda::class,
        )
            ->prefix(
                'bandas',
            )
            ->name(
                'bandas.',
            )
            ->group(
                static function (): void {
                    Route::get(
                        '/',
                        'index',
                    )->name(
                        'indice',
                    );

                    Route::get(
                        'criar',
                        'create',
                    )->name(
                        'criar',
                    );

                    Route::post(
                        '/',
                        'store',
                    )->name(
                        'guardar',
                    );

                    Route::get(
                        '{banda}',
                        'show',
                    )
                        ->whereNumber(
                            'banda',
                        )
                        ->name(
                            'detalhes',
                        );

                    Route::get(
                        '{banda}/editar',
                        'edit',
                    )
                        ->whereNumber(
                            'banda',
                        )
                        ->name(
                            'editar',
                        );

                    Route::patch(
                        '{banda}',
                        'update',
                    )
                        ->whereNumber(
                            'banda',
                        )
                        ->name(
                            'atualizar',
                        );

                    Route::delete(
                        '{banda}',
                        'destroy',
                    )
                        ->whereNumber(
                            'banda',
                        )
                        ->name(
                            'eliminar',
                        );
                },
            );

        /*
        |--------------------------------------------------------------------------
        | Edições
        |--------------------------------------------------------------------------
        */

        Route::controller(
            ControladorEdicao::class,
        )
            ->prefix(
                'edicoes',
            )
            ->name(
                'edicoes.',
            )
            ->group(
                static function (): void {
                    Route::get(
                        '/',
                        'index',
                    )->name(
                        'indice',
                    );

                    Route::get(
                        'criar',
                        'create',
                    )->name(
                        'criar',
                    );

                    Route::post(
                        '/',
                        'store',
                    )->name(
                        'guardar',
                    );

                    Route::post(
                        '{edicao}/musicas-favoritas',
                        'guardarMusicasFavoritas',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'musicas-favoritas.guardar',
                        );

                    Route::patch(
                        '{edicao}/ligacao-compilacao',
                        'atualizarLigacaoCompilacao',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'ligacao-compilacao.atualizar',
                        );

                    Route::get(
                        '{edicao}',
                        'show',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'detalhes',
                        );

                    Route::get(
                        '{edicao}/editar',
                        'edit',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'editar',
                        );

                    Route::patch(
                        '{edicao}',
                        'update',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'atualizar',
                        );

                    Route::delete(
                        '{edicao}',
                        'destroy',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'eliminar',
                        );
                },
            );

        /*
        |--------------------------------------------------------------------------
        | Géneros musicais
        |--------------------------------------------------------------------------
        */

        Route::controller(
            ControladorGenero::class,
        )
            ->prefix(
                'generos',
            )
            ->name(
                'generos.',
            )
            ->group(
                static function (): void {
                    Route::get(
                        '/',
                        'index',
                    )->name(
                        'indice',
                    );

                    Route::get(
                        'criar',
                        'create',
                    )->name(
                        'criar',
                    );

                    Route::post(
                        '/',
                        'store',
                    )->name(
                        'guardar',
                    );

                    Route::get(
                        '{genero}',
                        'show',
                    )
                        ->whereNumber(
                            'genero',
                        )
                        ->name(
                            'detalhes',
                        );

                    Route::get(
                        '{genero}/editar',
                        'edit',
                    )
                        ->whereNumber(
                            'genero',
                        )
                        ->name(
                            'editar',
                        );

                    Route::patch(
                        '{genero}',
                        'update',
                    )
                        ->whereNumber(
                            'genero',
                        )
                        ->name(
                            'atualizar',
                        );

                    Route::delete(
                        '{genero}',
                        'destroy',
                    )
                        ->whereNumber(
                            'genero',
                        )
                        ->name(
                            'eliminar',
                        );
                },
            );

        /*
        |--------------------------------------------------------------------------
        | Comentários
        |--------------------------------------------------------------------------
        */

        Route::post(
            '{tipoComentavel}/{identificadorComentavel}/comentarios',
            [
                ControladorComentario::class,
                'guardar',
            ],
        )
            ->where(
                'tipoComentavel',
                'metal_thursday|seccao_metal_thursday',
            )
            ->whereNumber(
                'identificadorComentavel',
            )
            ->name(
                'comentarios.guardar',
            );

        Route::post(
            'comentarios/{comentario}/respostas',
            [
                ControladorComentario::class,
                'responder',
            ],
        )
            ->whereNumber(
                'comentario',
            )
            ->name(
                'comentarios.respostas.guardar',
            );

        Route::patch(
            'comentarios/{comentario}',
            [
                ControladorComentario::class,
                'atualizar',
            ],
        )
            ->whereNumber(
                'comentario',
            )
            ->name(
                'comentarios.atualizar',
            );

        Route::delete(
            'comentarios/{comentario}',
            [
                ControladorComentario::class,
                'eliminar',
            ],
        )
            ->whereNumber(
                'comentario',
            )
            ->name(
                'comentarios.eliminar',
            );

        /*
        |--------------------------------------------------------------------------
        | Gostos dos comentários
        |--------------------------------------------------------------------------
        */

        Route::post(
            'comentarios/{comentario}/gosto',
            [
                ControladorGosto::class,
                'alternar',
            ],
        )
            ->whereNumber(
                'comentario',
            )
            ->name(
                'gostos.alternar',
            );

        Route::get(
            'comentarios/{comentario}/utilizadores-gosto',
            [
                ControladorGosto::class,
                'listarUtilizadores',
            ],
        )
            ->whereNumber(
                'comentario',
            )
            ->name(
                'comentarios.utilizadores-gosto',
            );

        /*
        |--------------------------------------------------------------------------
        | Avaliações
        |--------------------------------------------------------------------------
        */

        Route::post(
            'avaliacoes/{tipoAvaliavel}/{identificadorAvaliavel}',
            [
                ControladorAvaliacao::class,
                'guardar',
            ],
        )
            ->where(
                'tipoAvaliavel',
                'metal_thursday|seccao_metal_thursday',
            )
            ->whereNumber(
                'identificadorAvaliavel',
            )
            ->name(
                'avaliacoes.guardar',
            );

        /*
        |--------------------------------------------------------------------------
        | Audições
        |--------------------------------------------------------------------------
        */

        Route::post(
            'audicoes/{tipoAudivel}/{identificadorAudivel}',
            [
                ControladorAudicao::class,
                'alternar',
            ],
        )
            ->where(
                'tipoAudivel',
                'metal_thursday|seccao_metal_thursday',
            )
            ->whereNumber(
                'identificadorAudivel',
            )
            ->name(
                'audicoes.alternar',
            );

        /*
        |--------------------------------------------------------------------------
        | Notificações
        |--------------------------------------------------------------------------
        |
        | A ação estática deve ser declarada antes da rota que recebe o UUID.
        |
        */

        Route::controller(
            ControladorNotificacao::class,
        )
            ->prefix(
                'notificacoes',
            )
            ->name(
                'notificacoes.',
            )
            ->group(
                static function (): void {
                    Route::get(
                        '/',
                        'index',
                    )->name(
                        'indice',
                    );

                    Route::post(
                        'marcar-todas-como-lidas',
                        'marcarTodasComoLidas',
                    )->name(
                        'marcar-todas-como-lidas',
                    );

                    Route::post(
                        '{identificadorNotificacao}/marcar-como-lida',
                        'marcarComoLida',
                    )
                        ->whereUuid(
                            'identificadorNotificacao',
                        )
                        ->name(
                            'marcar-como-lida',
                        );
                },
            );
    },
);
