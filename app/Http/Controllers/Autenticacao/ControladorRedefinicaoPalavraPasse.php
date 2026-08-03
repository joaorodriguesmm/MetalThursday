<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacao\RedefinirPalavraPasseRequest;
use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Gere a apresentação e o processamento da redefinição da palavra-passe.
 *
 * A redefinição é executada através do broker configurado na aplicação. O
 * controlador converte os campos portugueses do formulário para as chaves
 * técnicas exigidas pelo contrato do Laravel.
 *
 * @since 1.0.0
 *
 * @version 4.0.0
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
     * Comprimento do novo token persistente de autenticação.
     *
     * @var int
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_TOKEN_PERSISTENTE = 60;

    /**
     * Apresenta o formulário de redefinição da palavra-passe.
     *
     * O endereço recebido na consulta é apenas utilizado para preencher o
     * formulário. A validação definitiva do endereço e do código pertence ao
     * pedido submetido e ao broker de palavras-passe.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  string  $token  Código recebido na ligação.
     * @return View Formulário de redefinição.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function apresentar(
        Request $pedido,
        #[SensitiveParameter]
        string $token,
    ): View {
        return view(
            'autenticacao.redefinir-palavra-passe',
            [
                'codigoRedefinicao' => $token,

                'email' => $this->obterEmailDaConsulta(
                    $pedido,
                ),

                'comprimentoMinimoPalavraPasse' => RequisitosPalavraPasse::comprimentoMinimo(),

                'comprimentoMaximoPalavraPasse' => RequisitosPalavraPasse::comprimentoMaximo(),
            ],
        );
    }

    /**
     * Redefine a palavra-passe do utilizador.
     *
     * As designações internas `password`, `password_confirmation` e `token`
     * são utilizadas apenas no limite de integração com o broker de
     * palavras-passe do Laravel.
     *
     * O atributo `password` recebe a palavra-passe em texto simples porque o
     * modelo {@see Utilizador} é responsável pela criação do hash.
     *
     * @param  RedefinirPalavraPasseRequest  $pedido  Pedido validado.
     * @return RedirectResponse Redirecionamento após a operação.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function redefinir(
        RedefinirPalavraPasseRequest $pedido,
    ): RedirectResponse {
        $email =
            $pedido->email();

        $resultado =
            Password::broker()
                ->reset(
                    [
                        'email' => $email,

                        'password' => $pedido->palavraPasse(),

                        'password_confirmation' => $pedido->confirmacaoPalavraPasse(),

                        'token' => $pedido->codigoRedefinicao(),
                    ],
                    static function (
                        Utilizador $utilizador,
                        #[SensitiveParameter]
                        string $novaPalavraPasse,
                    ): void {
                        /*
                         * `password` pertence ao contrato técnico de
                         * autenticação do Laravel. A criação do hash é
                         * realizada pelo cast definido no modelo.
                         */
                        $utilizador->forceFill([
                            'password' => $novaPalavraPasse,
                        ]);

                        /*
                         * A rotação do `remember_token` invalida os cookies
                         * persistentes emitidos antes da redefinição.
                         */
                        $utilizador->setRememberToken(
                            Str::random(
                                self::COMPRIMENTO_TOKEN_PERSISTENTE,
                            ),
                        );

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
                'email' => $email,
            ])
            ->withErrors([
                'ligacao_redefinicao' => self::MENSAGEM_LIGACAO_INVALIDA,
            ]);
    }

    /**
     * Obtém e normaliza o endereço de e-mail recebido na consulta.
     *
     * Um valor ausente ou inválido produz uma string vazia. A apresentação do
     * formulário não revela a existência de qualquer conta nem valida a
     * ligação de redefinição.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @return string Endereço normalizado ou string vazia.
     *
     * @since 4.0.0
     *
     * @version 1.0.0
     */
    private function obterEmailDaConsulta(
        Request $pedido,
    ): string {
        $email =
            $pedido->query(
                'email',
            );

        if (! is_string($email)) {
            return '';
        }

        try {
            return EnderecoEmail::deTexto(
                $email,
            )->valor();
        } catch (InvalidArgumentException) {
            return '';
        }
    }
}
