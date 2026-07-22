<?php

declare(strict_types=1);

use App\Http\Controllers\Autenticacao\ControladorRegistoConvite;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\HandleEmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Utilizadores\ControladorPalavraPasse;
use App\Http\Controllers\Utilizadores\ControladorPerfil;
use App\Http\Controllers\Utilizadores\ControladorPermissoesEmail;
use Illuminate\Support\Facades\Route;

/**
 * Define as rotas de autenticação e gestão do perfil.
 *
 * Os nomes exigidos pelos contratos técnicos do Laravel permanecem com a
 * nomenclatura original. As rotas específicas da aplicação utilizam
 * nomenclatura portuguesa.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */

/*
|--------------------------------------------------------------------------
| Rotas de aceitação de convites
|--------------------------------------------------------------------------
|
| Apenas visitantes sem uma sessão autenticada podem consultar ou aceitar
| convites.
|
*/

Route::middleware('guest')->group(
    static function (): void {
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
    },
);

/*
|--------------------------------------------------------------------------
| Rotas de recuperação da palavra-passe
|--------------------------------------------------------------------------
|
| Os nomes `password.*` são mantidos por constituírem contratos técnicos do
| sistema de reposição de palavras-passe do Laravel.
|
*/

Route::middleware('guest')->group(
    static function (): void {
        Route::get(
            'palavra-passe/esquecida',
            [
                PasswordResetLinkController::class,
                'create',
            ],
        )->name('password.request');

        Route::post(
            'palavra-passe/esquecida',
            [
                PasswordResetLinkController::class,
                'store',
            ],
        )
            ->middleware('throttle:6,1')
            ->name('password.email');

        Route::get(
            'palavra-passe/redefinir/{token}',
            [
                NewPasswordController::class,
                'create',
            ],
        )->name('password.reset');

        Route::post(
            'palavra-passe/redefinir',
            [
                NewPasswordController::class,
                'store',
            ],
        )
            ->middleware('throttle:6,1')
            ->name('password.store');
    },
);

/*
|--------------------------------------------------------------------------
| Rotas de início de sessão
|--------------------------------------------------------------------------
|
| O nome `login` é mantido por ser utilizado pelo middleware de autenticação
| do Laravel como destino dos visitantes não autenticados.
|
*/

Route::middleware('guest')->group(
    static function (): void {
        Route::get(
            'entrar',
            [
                AuthenticatedSessionController::class,
                'create',
            ],
        )->name('login');

        Route::post(
            'entrar',
            [
                AuthenticatedSessionController::class,
                'store',
            ],
        )->name('autenticacao.iniciar');
    },
);

/*
|--------------------------------------------------------------------------
| Rotas autenticadas
|--------------------------------------------------------------------------
|
| `auth.session` permite detetar alterações da hash da palavra-passe e
| terminar sessões invalidadas noutros dispositivos.
|
*/

Route::middleware([
    'auth',
    'auth.session',
])->group(
    static function (): void {
        Route::post(
            'sair',
            [
                AuthenticatedSessionController::class,
                'destroy',
            ],
        )->name('autenticacao.sair');

        /*
        |--------------------------------------------------------------------------
        | Perfil
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

        /*
        |--------------------------------------------------------------------------
        | Verificação do endereço de e-mail
        |--------------------------------------------------------------------------
        |
        | O nome `verification.verify` é exigido pelo sistema de verificação
        | do Laravel e, por isso, permanece inalterado.
        |
        */

        Route::get(
            'verificar-email/{id}/{hash}',
            HandleEmailVerificationController::class,
        )
            ->middleware([
                'signed',
                'throttle:6,1',
            ])
            ->whereNumber('id')
            ->name('verification.verify');
    },
);
