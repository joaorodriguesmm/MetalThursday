<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\RedefinirPalavraPasseRequest;
use App\Models\Autenticacao\Utilizador;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use SensitiveParameter;

/**
 * Gere a apresentação e o processamento da redefinição da palavra-passe.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class ControladorRedefinicaoPalavraPasse extends Controller
{
    /**
     * Mensagem apresentada quando a ligação não pode ser utilizada.
     *
     * A mesma mensagem é usada para códigos inválidos, expirados, utilizados,
     * endereços inexistentes e limitações temporárias.
     *
     * @var string
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private const MENSAGEM_LIGACAO_INVALIDA =
        'A ligação de redefinição é inválida ou já não está disponível. Solicita uma nova ligação.';

    /**
     * Apresenta o formulário de redefinição da palavra-passe.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  string  $token  Código recebido na ligação.
     * @return View Formulário de redefinição.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function apresentar(
        Request $pedido,
        #[SensitiveParameter]
        string $token,
    ): View {
        $email =
            $pedido->query(
                'email',
            );

        return view(
            'autenticacao.redefinir-palavra-passe',
            [
                'codigoRedefinicao' => $token,

                'email' => is_string($email)
                    ? mb_strtolower(
                        trim(
                            $email,
                        ),
                    )
                    : '',

                'comprimentoMinimoPalavraPasse' => RequisitosPalavraPasse::comprimentoMinimo(),

                'comprimentoMaximoPalavraPasse' => RequisitosPalavraPasse::comprimentoMaximo(),
            ],
        );
    }

    /**
     * Redefine a palavra-passe do utilizador.
     *
     * As designações internas `password`, `password_confirmation` e `token`
     * são utilizadas apenas no limite de integração com o gestor de
     * palavras-passe do Laravel.
     *
     * @param  RedefinirPalavraPasseRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a operação.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function redefinir(
        RedefinirPalavraPasseRequest $pedido,
    ): RedirectResponse {
        $resultado =
            Password::reset(
                [
                    'email' => $pedido->email(),

                    'password' => $pedido->palavraPasse(),

                    'password_confirmation' => $pedido->confirmacaoPalavraPasse(),

                    'token' => $pedido->codigoRedefinicao(),
                ],
                static function (
                    Utilizador $utilizador,
                    #[SensitiveParameter]
                    string $novaPalavraPasse,
                ): void {
                    $utilizador->forceFill([
                        /*
                         * `password` e `remember_token` pertencem ao contrato
                         * interno de autenticação do Laravel.
                         */
                        'password' => Hash::make(
                            $novaPalavraPasse,
                        ),

                        'remember_token' => Str::random(
                            60,
                        ),
                    ]);

                    $utilizador->saveOrFail();

                    event(
                        new PasswordReset(
                            $utilizador,
                        ),
                    );
                },
            );

        if ($resultado === Password::PASSWORD_RESET) {
            return to_route(
                'login',
            )->with(
                'sucesso',
                'A palavra-passe foi redefinida com sucesso.',
            );
        }

        return back()
            ->withInput([
                'email' => $pedido->email(),
            ])
            ->withErrors([
                'ligacao_redefinicao' => self::MENSAGEM_LIGACAO_INVALIDA,
            ]);
    }
}
