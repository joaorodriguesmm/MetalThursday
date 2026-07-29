<?php

declare(strict_types=1);

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
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
 * Todas as rotas deste ficheiro exigem autenticação através do guard
 * `sessao`, uma sessão autenticada válida e um endereço de e-mail verificado.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
 */
Route::middleware([
    'auth:sessao',
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
                'indice',
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
                        'criar',
                    )->name(
                        'criar',
                    );

                    Route::post(
                        '/',
                        'guardar',
                    )->name(
                        'guardar',
                    );

                    Route::get(
                        '{metalThursday}',
                        'detalhes',
                    )
                        ->whereNumber(
                            'metalThursday',
                        )
                        ->name(
                            'detalhes',
                        );

                    Route::get(
                        '{metalThursday}/editar',
                        'editar',
                    )
                        ->whereNumber(
                            'metalThursday',
                        )
                        ->name(
                            'editar',
                        );

                    Route::patch(
                        '{metalThursday}',
                        'atualizar',
                    )
                        ->whereNumber(
                            'metalThursday',
                        )
                        ->name(
                            'atualizar',
                        );

                    Route::delete(
                        '{metalThursday}',
                        'eliminar',
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
                        'indice',
                    )->name(
                        'indice',
                    );

                    Route::get(
                        'criar',
                        'criar',
                    )->name(
                        'criar',
                    );

                    Route::post(
                        '/',
                        'guardar',
                    )->name(
                        'guardar',
                    );

                    Route::get(
                        '{banda}',
                        'detalhes',
                    )
                        ->whereNumber(
                            'banda',
                        )
                        ->name(
                            'detalhes',
                        );

                    Route::get(
                        '{banda}/editar',
                        'editar',
                    )
                        ->whereNumber(
                            'banda',
                        )
                        ->name(
                            'editar',
                        );

                    Route::patch(
                        '{banda}',
                        'atualizar',
                    )
                        ->whereNumber(
                            'banda',
                        )
                        ->name(
                            'atualizar',
                        );

                    Route::delete(
                        '{banda}',
                        'eliminar',
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
                        'indice',
                    )->name(
                        'indice',
                    );

                    Route::get(
                        'criar',
                        'criar',
                    )->name(
                        'criar',
                    );

                    Route::post(
                        '/',
                        'guardar',
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
                        'detalhes',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'detalhes',
                        );

                    Route::get(
                        '{edicao}/editar',
                        'editar',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'editar',
                        );

                    Route::patch(
                        '{edicao}',
                        'atualizar',
                    )
                        ->whereNumber(
                            'edicao',
                        )
                        ->name(
                            'atualizar',
                        );

                    Route::delete(
                        '{edicao}',
                        'eliminar',
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
                        'indice',
                    )->name(
                        'indice',
                    );

                    Route::get(
                        'criar',
                        'criar',
                    )->name(
                        'criar',
                    );

                    Route::post(
                        '/',
                        'guardar',
                    )->name(
                        'guardar',
                    );

                    Route::get(
                        '{genero}',
                        'detalhes',
                    )
                        ->whereNumber(
                            'genero',
                        )
                        ->name(
                            'detalhes',
                        );

                    Route::get(
                        '{genero}/editar',
                        'editar',
                    )
                        ->whereNumber(
                            'genero',
                        )
                        ->name(
                            'editar',
                        );

                    Route::patch(
                        '{genero}',
                        'atualizar',
                    )
                        ->whereNumber(
                            'genero',
                        )
                        ->name(
                            'atualizar',
                        );

                    Route::delete(
                        '{genero}',
                        'eliminar',
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
            ->whereIn(
                'tipoComentavel',
                TipoEntidadeInteracao::obterSlugs(),
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
            ->whereIn(
                'tipoAvaliavel',
                TipoEntidadeInteracao::obterSlugs(),
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
            ->whereIn(
                'tipoAudivel',
                TipoEntidadeInteracao::obterSlugs(),
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
                        'indice',
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
