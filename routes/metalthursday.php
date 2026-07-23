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
 * Rotas principais da aplicação MetalThursday.
 *
 * Todas as rotas deste ficheiro exigem autenticação e um endereço de e-mail
 * verificado.
 *
 * Os caminhos e nomes de rota que ainda estão em inglês serão traduzidos
 * durante a revisão conjunta das rotas, vistas e ficheiros JavaScript.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
Route::middleware([
    'auth',
    'verified',
])->group(
    static function (): void {
        /*
         * MetalThursday.
         */
        Route::get(
            '/',
            [
                ControladorMetalThursday::class,
                'index',
            ],
        )->name('home');

        Route::get(
            'metalthursday/criar',
            [
                ControladorMetalThursday::class,
                'create',
            ],
        )->name('metalthursday.create');

        Route::post(
            'metalthursday',
            [
                ControladorMetalThursday::class,
                'store',
            ],
        )->name('metalthursday.store');

        Route::get(
            'metalthursday/{metalThursday}',
            [
                ControladorMetalThursday::class,
                'show',
            ],
        )
            ->whereNumber('metalThursday')
            ->name('metalthursday.show');

        Route::get(
            'metalthursday/{metalThursday}/editar',
            [
                ControladorMetalThursday::class,
                'edit',
            ],
        )
            ->whereNumber('metalThursday')
            ->name('metalthursday.edit');

        Route::patch(
            'metalthursday/{metalThursday}',
            [
                ControladorMetalThursday::class,
                'update',
            ],
        )
            ->whereNumber('metalThursday')
            ->name('metalthursday.update');

        Route::delete(
            'metalthursday/{metalThursday}',
            [
                ControladorMetalThursday::class,
                'destroy',
            ],
        )
            ->whereNumber('metalThursday')
            ->name('metalthursday.destroy');

        Route::get(
            'users/longest-not-nominated',
            [
                ControladorMetalThursday::class,
                'obterUtilizadorHaMaisTempoSemNomeacao',
            ],
        )->name('users.longest-not-nominated');

        /*
         * Bandas.
         */
        Route::resource(
            'bands',
            ControladorBanda::class,
        )
            ->parameters([
                'bands' => 'banda',
            ])
            ->where([
                'banda' => '[0-9]+',
            ]);

        /*
         * Edições.
         *
         * As rotas adicionais devem ser declaradas antes do recurso para
         * evitar que os segmentos estáticos sejam interpretados como IDs.
         */
        Route::post(
            'editions/{edicao}/rankings',
            [
                ControladorEdicao::class,
                'guardarMusicasFavoritas',
            ],
        )
            ->whereNumber('edicao')
            ->name('editions.rankings.store');

        Route::patch(
            'editions/{edicao}/compilation-link',
            [
                ControladorEdicao::class,
                'atualizarLigacaoCompilacao',
            ],
        )
            ->whereNumber('edicao')
            ->name('editions.compilation-link.update');

        Route::resource(
            'editions',
            ControladorEdicao::class,
        )
            ->parameters([
                'editions' => 'edicao',
            ])
            ->where([
                'edicao' => '[0-9]+',
            ]);

        /*
         * Géneros musicais.
         */
        Route::resource(
            'genres',
            ControladorGenero::class,
        )
            ->parameters([
                'genres' => 'genero',
            ])
            ->where([
                'genero' => '[0-9]+',
            ]);

        /*
         * Comentários.
         */
        Route::post(
            '{tipoComentavel}/{identificadorComentavel}/comments',
            [
                ControladorComentario::class,
                'guardar',
            ],
        )
            ->where(
                'tipoComentavel',
                'metal_thursday|metal-thursday|metalthursday|section|seccao|seccao_metal_thursday',
            )
            ->whereNumber('identificadorComentavel')
            ->name('comments.store');

        Route::post(
            'comments/{comentario}/replies',
            [
                ControladorComentario::class,
                'responder',
            ],
        )
            ->whereNumber('comentario')
            ->name('comments.reply.store');

        Route::patch(
            'comments/{comentario}',
            [
                ControladorComentario::class,
                'atualizar',
            ],
        )
            ->whereNumber('comentario')
            ->name('comments.update');

        Route::delete(
            'comments/{comentario}',
            [
                ControladorComentario::class,
                'eliminar',
            ],
        )
            ->whereNumber('comentario')
            ->name('comments.destroy');

        /*
         * Gostos.
         */
        Route::post(
            'comments/{comentario}/like',
            [
                ControladorGosto::class,
                'alternar',
            ],
        )
            ->whereNumber('comentario')
            ->name('likes.toggle');

        Route::get(
            'comments/{comentario}/likers',
            [
                ControladorGosto::class,
                'listarUtilizadores',
            ],
        )
            ->whereNumber('comentario')
            ->name('comments.likers');

        /*
         * Avaliações.
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
                'metal_thursday|metal-thursday|metalthursday|section|seccao|seccao_metal_thursday',
            )
            ->whereNumber('identificadorAvaliavel')
            ->name('avaliacoes.guardar');

        /*
         * Audições.
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
                'metal_thursday|metal-thursday|metalthursday|section|seccao|seccao_metal_thursday',
            )
            ->whereNumber('identificadorAudivel')
            ->name('audicoes.alternar');

        /*
         * Notificações.
         *
         * A rota estática deve aparecer antes da rota com o identificador.
         */
        Route::get(
            'notifications',
            [
                ControladorNotificacao::class,
                'index',
            ],
        )->name('notifications.index');

        Route::patch(
            'notifications/read-all',
            [
                ControladorNotificacao::class,
                'marcarTodasComoLidas',
            ],
        )->name('notifications.read-all');

        Route::patch(
            'notifications/{identificadorNotificacao}/read',
            [
                ControladorNotificacao::class,
                'marcarComoLida',
            ],
        )
            ->whereUuid('identificadorNotificacao')
            ->name('notifications.read');
    },
);
