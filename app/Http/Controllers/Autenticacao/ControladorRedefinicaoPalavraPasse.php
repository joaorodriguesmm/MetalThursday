<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\RedefinirPalavraPasseRequest;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SensitiveParameter;

/**
 * Gere a apresentação e o processamento da redefinição da palavra-passe.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class ControladorRedefinicaoPalavraPasse extends Controller
{
    /**
     * Apresenta o formulário de redefinição da palavra-passe.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  string  $token  Token recebido na ligação.
     * @return View Formulário de redefinição.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function apresentar(
        Request $pedido,
        #[SensitiveParameter]
        string $token,
    ): View {
        $email = $pedido->query('email');

        return view(
            'auth.reset-password',
            [
                'token' => $token,

                'email' => is_string($email)
                    ? mb_strtolower(trim($email))
                    : '',
            ],
        );
    }

    /**
     * Redefine a palavra-passe.
     *
     * @param  RedefinirPalavraPasseRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a operação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function redefinir(
        RedefinirPalavraPasseRequest $pedido,
    ): RedirectResponse {
        $dados = $pedido->validated();

        /** @var string $email */
        $email = $dados['email'];

        /** @var string $palavraPasse */
        $palavraPasse = $dados['password'];

        /** @var string $confirmacaoPalavraPasse */
        $confirmacaoPalavraPasse =
            $dados['password_confirmation'];

        /** @var string $token */
        $token = $dados['token'];

        $estado = Password::reset(
            [
                'email' => $email,

                'password' => $palavraPasse,

                'password_confirmation' => $confirmacaoPalavraPasse,

                'token' => $token,
            ],
            static function (
                Utilizador $utilizador,
                #[SensitiveParameter]
                string $novaPalavraPasse,
            ): void {
                $utilizador->forceFill([
                    'password' => $novaPalavraPasse,

                    'remember_token' => Str::random(60),
                ]);

                $utilizador->saveOrFail();

                event(
                    new PasswordReset(
                        $utilizador,
                    ),
                );
            },
        );

        if ($estado === Password::PASSWORD_RESET) {
            return to_route(
                'login',
            )->with(
                'estado',
                'A palavra-passe foi redefinida com sucesso.',
            );
        }

        return back()
            ->withInput([
                'email' => $email,
            ])
            ->withErrors([
                'email' => $this->obterMensagemErro(
                    $estado,
                ),
            ]);
    }

    /**
     * Obtém uma mensagem segura para o estado devolvido pelo gestor.
     *
     * Os estados de utilizador inexistente e token inválido recebem a mesma
     * mensagem, evitando expor informação sobre contas registadas.
     *
     * @param  string  $estado  Estado devolvido pelo gestor.
     * @return string Mensagem apresentada.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function obterMensagemErro(
        string $estado,
    ): string {
        if ($estado === Password::RESET_THROTTLED) {
            return __($estado);
        }

        return 'A ligação de redefinição é inválida ou expirou. Solicita uma nova ligação.';
    }
}
