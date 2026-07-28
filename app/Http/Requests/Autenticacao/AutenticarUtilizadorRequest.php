<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Valida e processa um pedido de autenticação.
 *
 * A limitação de tentativas combina o endereço de e-mail normalizado com
 * o endereço IP do pedido, sem armazenar diretamente esses dados na chave
 * da cache.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class AutenticarUtilizadorRequest extends FormRequest
{
    /**
     * Número máximo de tentativas antes do bloqueio temporário.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MAXIMO_TENTATIVAS = 5;

    /**
     * Duração do bloqueio temporário, em segundos.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DURACAO_BLOQUEIO_SEGUNDOS = 60;

    /**
     * Determina se o pedido pode ser processado.
     *
     * @return bool Verdadeiro para permitir a validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza o endereço de e-mail antes da validação.
     *
     * A palavra-passe não é alterada, porque os espaços podem fazer parte
     * do respetivo valor.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $email =
            $this->input(
                'email',
            );

        if (! is_string($email)) {
            return;
        }

        $this->merge([
            'email' => mb_strtolower(
                trim(
                    $email,
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Não são aplicados os requisitos mínimos atuais de complexidade, porque
     * uma conta existente pode ainda utilizar uma palavra-passe criada sob
     * regras anteriores.
     *
     * @return array<string, array<int, string>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],

            'palavra_passe' => [
                'bail',
                'required',
                'string',
                'max:'.RequisitosPalavraPasse::comprimentoMaximo(),
            ],

            'manter_sessao_iniciada' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',

            'email.email' => 'Por favor, insere um endereço de e-mail válido.',

            'email.max' => 'O endereço de e-mail não pode ter mais de 255 caracteres.',

            'palavra_passe.required' => 'Por favor, insere a palavra-passe.',

            'palavra_passe.string' => 'A palavra-passe deve ser uma sequência de caracteres.',

            'palavra_passe.max' => 'A palavra-passe recebida é demasiado longa.',

            'manter_sessao_iniciada.boolean' => 'A opção de manter a sessão iniciada não é válida.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos validados.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function attributes(): array
    {
        return [
            'email' => 'endereço de e-mail',

            'palavra_passe' => 'palavra-passe',

            'manter_sessao_iniciada' => 'manter a sessão iniciada',
        ];
    }

    /**
     * Autentica o utilizador através do guard web.
     *
     * @throws ValidationException Quando as credenciais são inválidas ou o
     *                             limite de tentativas foi excedido.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function autenticar(): void
    {
        $this->garantirAusenciaDeLimitacao();

        $autenticado =
            Auth::guard(
                'web',
            )->attempt(
                [
                    'email' => $this->email(),

                    /*
                     * `password` é uma chave interna obrigatória do contrato
                     * de autenticação do Laravel.
                     */
                    'password' => $this->palavraPasse(),
                ],
                $this->manterSessaoIniciada(),
            );

        if (! $autenticado) {
            RateLimiter::hit(
                $this->obterChaveLimitacao(),
                self::DURACAO_BLOQUEIO_SEGUNDOS,
            );

            throw ValidationException::withMessages([
                'email' => trans(
                    'auth.failed',
                ),
            ]);
        }

        RateLimiter::clear(
            $this->obterChaveLimitacao(),
        );
    }

    /**
     * Obtém o endereço de e-mail validado.
     *
     * @return string Endereço de e-mail.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function email(): string
    {
        return $this->obterTextoValidado(
            'email',
        );
    }

    /**
     * Obtém a palavra-passe validada.
     *
     * @return string Palavra-passe em texto simples.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function palavraPasse(): string
    {
        return $this->obterTextoValidado(
            'palavra_passe',
        );
    }

    /**
     * Determina se a sessão deve permanecer iniciada.
     *
     * @return bool Verdadeiro quando a opção foi selecionada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function manterSessaoIniciada(): bool
    {
        return $this->boolean(
            'manter_sessao_iniciada',
        );
    }

    /**
     * Impede uma nova tentativa quando o limite foi excedido.
     *
     * @throws ValidationException Quando o pedido está temporariamente
     *                             bloqueado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function garantirAusenciaDeLimitacao(): void
    {
        $chave =
            $this->obterChaveLimitacao();

        if (
            ! RateLimiter::tooManyAttempts(
                $chave,
                self::MAXIMO_TENTATIVAS,
            )
        ) {
            return;
        }

        event(
            new Lockout(
                $this,
            ),
        );

        $segundos =
            RateLimiter::availableIn(
                $chave,
            );

        throw ValidationException::withMessages([
            'email' => trans(
                'auth.throttle',
                [
                    'seconds' => $segundos,

                    'minutes' => (int) ceil(
                        $segundos / 60,
                    ),
                ],
            ),
        ]);
    }

    /**
     * Obtém a chave utilizada na limitação de tentativas.
     *
     * O endereço de e-mail e o IP são transformados num hash para não serem
     * armazenados diretamente como identificador da cache.
     *
     * @return string Chave da limitação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function obterChaveLimitacao(): string
    {
        $enderecoIp =
            $this->ip()
            ?? 'desconhecido';

        return 'autenticacao:'.hash(
            'sha256',
            $this->emailParaLimitacao()
                .'|'
                .$enderecoIp,
        );
    }

    /**
     * Obtém o endereço de e-mail utilizado na chave de limitação.
     *
     * Este método pode ser executado antes da conclusão da validação, pelo
     * que trabalha diretamente com a entrada normalizada do pedido.
     *
     * @return string Endereço normalizado ou texto vazio.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function emailParaLimitacao(): string
    {
        $email =
            $this->input(
                'email',
                '',
            );

        return is_string($email)
            ? mb_strtolower(
                trim(
                    $email,
                ),
            )
            : '';
    }

    /**
     * Obtém um texto validado.
     *
     * @param  string  $campo  Nome do campo.
     * @return string Valor validado.
     *
     * @throws LogicException Quando o valor possui um tipo inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterTextoValidado(
        string $campo,
    ): string {
        $valor =
            $this->validated(
                $campo,
            );

        if (! is_string($valor)) {
            throw new LogicException(
                sprintf(
                    'O campo validado "%s" possui um tipo inesperado.',
                    $campo,
                ),
            );
        }

        return $valor;
    }
}
