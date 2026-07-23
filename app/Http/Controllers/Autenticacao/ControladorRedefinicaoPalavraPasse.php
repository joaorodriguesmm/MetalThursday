<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SensitiveParameter;

/**
 * Gere a redefinição da palavra-passe de um utilizador.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorRedefinicaoPalavraPasse extends Controller
{
    /**
     * Apresenta o formulário de redefinição da palavra-passe.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  string  $token  Token recebido na ligação de redefinição.
     * @return View Formulário de redefinição.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function apresentar(
        Request $pedido,
        #[SensitiveParameter]
        string $token,
    ): View {
        return view(
            'auth.reset-password',
            [
                'token' => $token,
                'email' => (string) $pedido->query(
                    'email',
                    '',
                ),
            ],
        );
    }

    /**
     * Redefine a palavra-passe através do gestor de palavras-passe.
     *
     * @param  ResetPasswordRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a operação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function redefinir(
        ResetPasswordRequest $pedido,
    ): RedirectResponse {
        $dados = $pedido->validated();

        $estado = Password::reset(
            [
                'email' => $dados['email'],

                'password' => $dados['password'],

                'password_confirmation' => $dados['password_confirmation'],

                'token' => $dados['token'],
            ],
            static function (
                Utilizador $utilizador,
                #[SensitiveParameter]
                string $palavraPasse,
            ): void {
                $utilizador
                    ->forceFill([
                        'password' => Hash::make(
                            $palavraPasse,
                        ),

                        'remember_token' => Str::random(60),
                    ])
                    ->saveOrFail();

                event(
                    new PasswordReset(
                        $utilizador,
                    ),
                );
            },
        );

        if ($estado === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with(
                    'estado',
                    'A tua palavra-passe foi redefinida. Já podes iniciar sessão.',
                );
        }

        return back()
            ->withInput(
                $pedido->only('email'),
            )
            ->withErrors([
                'email' => $this->obterMensagemErro(
                    $estado,
                ),
            ]);
    }

    /**
     * Obtém a mensagem correspondente ao resultado da redefinição.
     *
     * @param  string  $estado  Estado devolvido pelo gestor.
     * @return string Mensagem apresentada ao utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterMensagemErro(
        string $estado,
    ): string {
        return match ($estado) {
            Password::INVALID_TOKEN => 'A ligação de redefinição é inválida ou expirou.',

            Password::INVALID_USER => 'Não foi possível validar o endereço de e-mail.',

            default => 'Não foi possível redefinir a palavra-passe. Tenta novamente.',
        };
    }
}
