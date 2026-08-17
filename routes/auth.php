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
use App\Models\Autenticacao\Convite;
use Illuminate\Support\Facades\Route;

/**
 * Define as rotas de autenticação e de gestão do perfil.
 *
 * Os nomes técnicos exigidos por contratos do Laravel, como `login`, `logout`
 * e `verification.verify`, mantêm a nomenclatura convencional. As rotas
 * próprias da aplicação utilizam nomes em português.
 *
 * Todos os middlewares de autenticação indicam explicitamente o guard
 * `sessao`.
 *
 * @since 1.0.0
 */

/*
|--------------------------------------------------------------------------
| Rotas exclusivas para visitantes
|--------------------------------------------------------------------------
*/

Route::middleware(
    'guest:sessao',
)->group(
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
                Convite::PADRAO_CODIGO,
            )
            ->name(
                'convites.aceitar',
            );

        Route::post(
            'convites/aceitar',
            [
                ControladorRegistoConvite::class,
                'registar',
            ],
        )
            ->middleware(
                'throttle:6,1',
            )
            ->name(
                'convites.registar',
            );

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
        )->name(
            'autenticacao.recuperar-palavra-passe',
        );

        Route::post(
            'palavra-passe/esquecida',
            [
                ControladorLigacaoRedefinicaoPalavraPasse::class,
                'enviar',
            ],
        )
            ->middleware(
                'throttle:6,1',
            )
            ->name(
                'autenticacao.enviar-ligacao-redefinicao',
            );

        Route::get(
            'palavra-passe/redefinir/{token}',
            [
                ControladorRedefinicaoPalavraPasse::class,
                'apresentar',
            ],
        )->name(
            'autenticacao.redefinir-palavra-passe',
        );

        Route::post(
            'palavra-passe/redefinir',
            [
                ControladorRedefinicaoPalavraPasse::class,
                'redefinir',
            ],
        )
            ->middleware(
                'throttle:6,1',
            )
            ->name(
                'autenticacao.atualizar-palavra-passe',
            );

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
        )->name(
            'login',
        );

        Route::post(
            'entrar',
            [
                ControladorSessaoAutenticada::class,
                'autenticar',
            ],
        )
            ->middleware(
                'throttle:6,1',
            )
            ->name(
                'autenticacao.iniciar',
            );
    },
);

/*
|--------------------------------------------------------------------------
| Verificação do endereço de e-mail
|--------------------------------------------------------------------------
|
| A verificação pode ocorrer antes de o utilizador iniciar sessão, mas exige
| uma ligação assinada e válida.
|
*/

Route::get(
    'verificar-email/{id}/{hash}',
    ControladorVerificacaoEmail::class,
)
    ->whereNumber(
        'id',
    )
    ->where(
        'hash',
        '[a-fA-F0-9]{40}',
    )
    ->middleware([
        'signed',
        'throttle:6,1',
    ])
    ->name(
        'verification.verify',
    );

/*
|--------------------------------------------------------------------------
| Rotas exclusivas para utilizadores autenticados
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sessao',
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
        )->name(
            'logout',
        );

        /*
        |--------------------------------------------------------------------------
        | Gestão do perfil
        |--------------------------------------------------------------------------
        */

        Route::prefix(
            'perfil',
        )
            ->name(
                'perfil.',
            )
            ->group(
                static function (): void {
                    Route::get(
                        '/',
                        [
                            ControladorPerfil::class,
                            'editar',
                        ],
                    )->name(
                        'editar',
                    );

                    Route::patch(
                        '/',
                        [
                            ControladorPerfil::class,
                            'atualizar',
                        ],
                    )->name(
                        'atualizar',
                    );

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
