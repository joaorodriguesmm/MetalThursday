<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\HandleEmailVerificationController;
use Illuminate\Support\Facades\Route;

/**
 * Rotas de autenticação.
 *
 * @since 1.0
 * @version 1.0
 */

/**
 * Rotas de autenticação para utilizadores sem sessão iniciada.
 *
 * @since 1.0
 * @version 1.0
 */
Route::middleware('guest')->group(function () {
    /**
     * Rota /convite/{codigo_convite} (GET).
     * Apresenta o formulário de registo por convite.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::get('/convite/{codigo_convite}', [RegisteredUserController::class, 'create'])
         ->name('registo.convite');

    /**
     * Rota /convite (POST).
     * Processa o formulário de registo por convite.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::post('/convite', [RegisteredUserController::class, 'store'])
         ->name('registo.finalizar');

    /**
     * Rota /password-esquecida (GET).
     * Apresenta o formulário de recuperação de password.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::get('password-esquecida', [PasswordResetLinkController::class, 'create'])
         ->name('password.request');

    /**
     * Rota /forgot-password (POST).
     * Processa o formulário de recuperação de password.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
         ->name('password.email');

    /**
     * Rota /redefinir-password/{token} (GET).
     * Apresenta o formulário de redefinição de password.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::get('redefinir-password/{token}', [NewPasswordController::class, 'create'])
         ->name('password.reset');

    /**
     * Rota /reset-password (POST).
     * Processa o formulário de redefinição de password.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::post('reset-password', [NewPasswordController::class, 'store'])
         ->name('password.store');

    /**
     * Rota /login (GET).
     * Apresenta o formulário de login.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
         ->name('login');

    /**
     * Rota /login (POST).
     * Processa o formulário de login.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

/**
 * Rotas de autenticação para utilizadores com sessão iniciada.
 *
 * @since 1.0
 * @version 1.0
 */
Route::middleware('auth')->group(function () {
    /**
     * Rota /logout (POST).
     * Processa o logout.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
         ->name('logout');

    /**
     * Rota /profile (GET).
     * Apresenta o formulário de edição de perfil.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::get('/perfil', [ProfileController::class, 'edit'])
         ->name('profile.edit');

    /**
     * Rota /profile (PATCH).
     * Processa o formulário de edição de perfil.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::patch('/perfil', [ProfileController::class, 'update'])
         ->name('profile.update');

    /**
     * Rota /profile/email-permissions (PATCH).
     * Processa o formulário de edição de permissões de e-mail.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::patch('/perfil/email-permissions', [ProfileController::class, 'updateEmailPermissions'])
         ->name('profile.email_permissions.update');

    /**
     * Rota /profile/password (PUT).
     * Processa o formulário de edição de password.
     *
     * @since 1.0
     * @version 1.0
     */
    Route::put('/perfil/password', [ProfileController::class, 'updatePassword'])
         ->name('profile.password.update');
});

/**
 * Rota /verificar-email/{id}/{hash} (GET).
 * Processa a verificação de e-mail.
 *
 * @since 1.0
 * @version 1.0
 */
Route::get('/verificar-email/{id}/{hash}', HandleEmailVerificationController::class)
     ->middleware(['signed'])
     ->name('verification.verify');
