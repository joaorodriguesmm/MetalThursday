<?php

declare(strict_types=1);

use App\Http\Controllers\Autenticacao\ControladorLigacaoRedefinicaoPalavraPasse;
use App\Http\Controllers\Autenticacao\ControladorRedefinicaoPalavraPasse;
use App\Http\Controllers\Autenticacao\ControladorRegistoConvite;
use App\Http\Controllers\Autenticacao\ControladorSessaoAutenticada;
use App\Http\Controllers\Autenticacao\ControladorVerificacaoEmail;
use App\Http\Controllers\Utilizadores\ControladorPalavraPasse;
use App\Http\Controllers\Utilizadores\ControladorPerfil;
use App\Http\Controllers\Utilizadores\ControladorPermissoesEmail;
use Illuminate\Support\Facades\Route;

/**
 * Define as rotas de autenticação e de gestão do perfil.
 *
 * Os nomes de rotas exigidos pelos contratos técnicos do Laravel mantêm a
 * nomenclatura original.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */

/*
|--------------------------------------------------------------------------
| Rotas exclusivas para visitantes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(
    static function (): void {
        /*
        |--------------------------------------------------------------------------
        | Aceitação de convites
        |--------------------------------------------------------------------------
        */

        Route::get(
            'convites/{codigoConvite}',
            [
                ControladorRegistoConvite::class,
                'apresentar',
            ],
        )
            ->where(
                'codigoConvite',
                '[A-Za-z0-9_-]{10,128}',
            )
            ->name('convites.aceitar');

        Route::post(
            'convites/aceitar',
            [
                ControladorRegistoConvite::class,
                'registar',
            ],
        )
            ->middleware('throttle:6,1')
            ->name('convites.registar');

        /*
        |--------------------------------------------------------------------------
        | Recuperação da palavra-passe
        |--------------------------------------------------------------------------
        */

        Route::get(
            'palavra-passe/esquecida',
            [
                ControladorLigacaoRedefinicaoPalavraPasse::class,
                'apresentar',
            ],
        )->name('password.request');

        Route::post(
            'palavra-passe/esquecida',
            [
                ControladorLigacaoRedefinicaoPalavraPasse::class,
                'enviar',
            ],
        )
            ->middleware('throttle:6,1')
            ->name('password.email');

        Route::get(
            'palavra-passe/redefinir/{token}',
            [
                ControladorRedefinicaoPalavraPasse::class,
                'apresentar',
            ],
        )->name('password.reset');

        Route::post(
            'palavra-passe/redefinir',
            [
                ControladorRedefinicaoPalavraPasse::class,
                'redefinir',
            ],
        )
            ->middleware('throttle:6,1')
            ->name('password.store');

        /*
        |--------------------------------------------------------------------------
        | Início de sessão
        |--------------------------------------------------------------------------
        */

        Route::get(
            'entrar',
            [
                ControladorSessaoAutenticada::class,
                'apresentar',
            ],
        )->name('login');

        Route::post(
            'entrar',
            [
                ControladorSessaoAutenticada::class,
                'autenticar',
            ],
        )
            ->middleware('throttle:6,1')
            ->name('autenticacao.iniciar');
    },
);

/*
|--------------------------------------------------------------------------
| Verificação do endereço de e-mail
|--------------------------------------------------------------------------
|
| Esta rota permanece acessível sem autenticação porque o utilizador pode
| confirmar o endereço antes de iniciar sessão.
|
*/

Route::get(
    'verificar-email/{id}/{hash}',
    ControladorVerificacaoEmail::class,
)
    ->whereNumber('id')
    ->where(
        'hash',
        '[a-fA-F0-9]{40}',
    )
    ->middleware('throttle:6,1')
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Rotas exclusivas para utilizadores autenticados
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'auth.session',
])->group(
    static function (): void {
        /*
        |--------------------------------------------------------------------------
        | Encerramento da sessão
        |--------------------------------------------------------------------------
        */

        Route::post(
            'sair',
            [
                ControladorSessaoAutenticada::class,
                'terminar',
            ],
        )->name('logout');

        /*
        |--------------------------------------------------------------------------
        | Gestão do perfil
        |--------------------------------------------------------------------------
        */

        Route::prefix('perfil')
            ->name('perfil.')
            ->group(
                static function (): void {
                    Route::get(
                        '/',
                        [
                            ControladorPerfil::class,
                            'editar',
                        ],
                    )->name('editar');

                    Route::patch(
                        '/',
                        [
                            ControladorPerfil::class,
                            'atualizar',
                        ],
                    )->name('atualizar');

                    Route::patch(
                        'permissoes-email',
                        [
                            ControladorPermissoesEmail::class,
                            'atualizar',
                        ],
                    )->name(
                        'permissoes-email.atualizar',
                    );

                    Route::put(
                        'palavra-passe',
                        [
                            ControladorPalavraPasse::class,
                            'atualizar',
                        ],
                    )->name(
                        'palavra-passe.atualizar',
                    );
                },
            );
    },
);
