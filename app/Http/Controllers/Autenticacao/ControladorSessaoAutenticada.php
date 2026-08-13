<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\AutenticarUtilizadorRequest;
use App\Models\Autenticacao\Utilizador;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Gere a criação e o encerramento da sessão autenticada.
 *
 * A autenticação e o encerramento utilizam exclusivamente o guard `sessao`,
 * definido como guard principal da aplicação.
 *
 * @since 1.0.0
 */
final class ControladorSessaoAutenticada extends Controller
{
    /**
     * Apresenta o formulário de autenticação.
     *
     * @return View Formulário de autenticação.
     *
     * @since 1.0.0
     */
    public function apresentar(): View
    {
        return view(
            'autenticacao.iniciar-sessao',
            [
                'comprimentoMaximoPalavraPasse' => RequisitosPalavraPasse::comprimentoMaximo(),
            ],
        );
    }

    /**
     * Autentica o utilizador e regenera a sessão.
     *
     * Utilizadores com o endereço de e-mail por verificar não podem manter
     * uma sessão autenticada.
     *
     * @param  AutenticarUtilizadorRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a autenticação.
     *
     * @throws AuthenticationException Quando a autenticação não produz um
     *                                 utilizador válido.
     *
     * @since 1.0.0
     */
    public function autenticar(
        AutenticarUtilizadorRequest $pedido,
    ): RedirectResponse {
        $pedido->autenticar();

        $pedido
            ->session()
            ->regenerate();

        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (! $utilizador instanceof Utilizador) {
            $this->terminarSessao(
                $pedido,
            );

            throw new AuthenticationException(
                'Não foi possível concluir a autenticação.',
            );
        }

        if (
            $utilizador instanceof MustVerifyEmail
            && ! $utilizador->hasVerifiedEmail()
        ) {
            $email =
                $utilizador->email;

            $this->terminarSessao(
                $pedido,
            );

            return back()
                ->withInput([
                    'email' => $email,
                ])
                ->withErrors([
                    'email' => 'Verifica o teu endereço de e-mail antes de iniciares sessão.',
                ]);
        }

        return redirect()->intended(
            route(
                'inicio',
            ),
        );
    }

    /**
     * Encerra a sessão do utilizador autenticado.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return RedirectResponse Redirecionamento para o início de sessão.
     *
     * @since 1.0.0
     */
    public function terminar(
        Request $pedido,
    ): RedirectResponse {
        $this->terminarSessao(
            $pedido,
        );

        return to_route(
            'login',
        );
    }

    /**
     * Termina a sessão autenticada, invalida a sessão atual e regenera o
     * token CSRF.
     *
     * A invalidação elimina todos os dados da sessão anterior e gera um novo
     * identificador, impedindo a reutilização da sessão encerrada.
     *
     * @param  Request  $pedido  Pedido HTTP.
     *
     * @since 2.0.0
     */
    private function terminarSessao(
        Request $pedido,
    ): void {
        Auth::guard(
            'sessao',
        )->logout();

        $pedido
            ->session()
            ->invalidate();

        $pedido
            ->session()
            ->regenerateToken();
    }
}
