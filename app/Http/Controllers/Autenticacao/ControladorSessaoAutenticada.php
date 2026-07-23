<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gere o início e o encerramento das sessões autenticadas.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorSessaoAutenticada extends Controller
{
    /**
     * Apresenta o formulário de autenticação.
     *
     * @return View Formulário de autenticação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function apresentar(): View
    {
        return view(
            'auth.login',
        );
    }

    /**
     * Autentica o utilizador e inicia uma nova sessão.
     *
     * A sessão é terminada imediatamente quando a conta necessita de
     * verificação de e-mail e ainda não foi confirmada.
     *
     * @param  LoginRequest  $pedido  Pedido de autenticação validado.
     * @return RedirectResponse Redirecionamento após a autenticação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function autenticar(
        LoginRequest $pedido,
    ): RedirectResponse {
        $pedido->authenticate();

        $utilizador = $pedido->user();

        if (
            $utilizador instanceof MustVerifyEmail
            && ! $utilizador->hasVerifiedEmail()
        ) {
            $this->terminarSessao(
                $pedido,
            );

            return back()
                ->withInput(
                    $pedido->only('email'),
                )
                ->withErrors([
                    'email' => 'A tua conta ainda não foi ativada. '
                        .'Confirma o endereço através da ligação enviada '
                        .'para o teu e-mail.',
                ]);
        }

        $pedido
            ->session()
            ->regenerate();

        return redirect()->intended(
            route(
                'home',
                [],
                false,
            ),
        );
    }

    /**
     * Termina a sessão do utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return RedirectResponse Redirecionamento para a autenticação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function terminar(
        Request $pedido,
    ): RedirectResponse {
        $this->terminarSessao(
            $pedido,
        );

        return redirect()->route(
            'login',
        );
    }

    /**
     * Encerra a autenticação e invalida os dados da sessão.
     *
     * @param  Request  $pedido  Pedido HTTP.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function terminarSessao(
        Request $pedido,
    ): void {
        Auth::guard('web')->logout();

        $pedido
            ->session()
            ->invalidate();

        $pedido
            ->session()
            ->regenerateToken();
    }
}
