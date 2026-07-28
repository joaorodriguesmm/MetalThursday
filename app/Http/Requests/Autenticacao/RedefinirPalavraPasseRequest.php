<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use LogicException;

/**
 * Valida um pedido de redefinição da palavra-passe.
 *
 * A validade do código, a correspondência com o endereço de e-mail e a
 * existência do utilizador são verificadas pelo gestor de palavras-passe.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class RedefinirPalavraPasseRequest extends FormRequest
{
    /**
     * Mensagem apresentada quando os dados da ligação não são válidos.
     *
     * A mesma mensagem é utilizada para o código e para o endereço de e-mail,
     * evitando expor detalhes internos do processo de redefinição.
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
     * Normaliza o código de redefinição e o endereço de e-mail.
     *
     * A palavra-passe e a respetiva confirmação não são alteradas, porque os
     * espaços podem fazer parte dos seus valores.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $codigoRedefinicao =
            $this->input(
                'codigo_redefinicao',
            );

        $email =
            $this->input(
                'email',
            );

        $this->merge([
            'codigo_redefinicao' => is_string($codigoRedefinicao)
                ? trim(
                    $codigoRedefinicao,
                )
                : $codigoRedefinicao,

            'email' => is_string($email)
                ? mb_strtolower(
                    trim(
                        $email,
                    ),
                )
                : $email,
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Os requisitos da nova palavra-passe são obtidos através da respetiva
     * fonte central, garantindo consistência com os restantes fluxos.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function rules(): array
    {
        return [
            'codigo_redefinicao' => [
                'bail',
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],

            'palavra_passe' => RequisitosPalavraPasse::regrasObrigatorias(),

            'confirmacao_palavra_passe' => [
                'bail',
                'required',
                'string',
                'max:'.RequisitosPalavraPasse::comprimentoMaximo(),
                'same:palavra_passe',
            ],
        ];
    }

    /**
     * Adiciona um erro único para dados inválidos da ligação.
     *
     * Os erros associados ao código ou ao endereço de e-mail pertencem a
     * campos ocultos. A chave `ligacao_redefinicao` permite apresentar uma
     * única mensagem segura e visível na view.
     *
     * @param  Validator  $validador  Validador do pedido.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    protected function withValidator(
        Validator $validador,
    ): void {
        $validador->after(
            static function (
                Validator $validador,
            ): void {
                $temErroNaLigacao =
                    $validador
                        ->errors()
                        ->has(
                            'codigo_redefinicao',
                        )
                    || $validador
                        ->errors()
                        ->has(
                            'email',
                        );

                if (! $temErroNaLigacao) {
                    return;
                }

                $validador
                    ->errors()
                    ->add(
                        'ligacao_redefinicao',
                        self::MENSAGEM_LIGACAO_INVALIDA,
                    );
            },
        );
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
            'codigo_redefinicao.required' => self::MENSAGEM_LIGACAO_INVALIDA,

            'codigo_redefinicao.string' => self::MENSAGEM_LIGACAO_INVALIDA,

            'codigo_redefinicao.max' => self::MENSAGEM_LIGACAO_INVALIDA,

            'email.required' => self::MENSAGEM_LIGACAO_INVALIDA,

            'email.string' => self::MENSAGEM_LIGACAO_INVALIDA,

            'email.email' => self::MENSAGEM_LIGACAO_INVALIDA,

            'email.max' => self::MENSAGEM_LIGACAO_INVALIDA,

            'palavra_passe.required' => 'Por favor, insere a nova palavra-passe.',

            'palavra_passe.string' => 'A nova palavra-passe deve ser uma sequência de caracteres.',

            'palavra_passe.max' => 'A nova palavra-passe é demasiado longa.',

            'confirmacao_palavra_passe.required' => 'Por favor, confirma a nova palavra-passe.',

            'confirmacao_palavra_passe.string' => 'A confirmação da nova palavra-passe não é válida.',

            'confirmacao_palavra_passe.max' => 'A confirmação da nova palavra-passe é demasiado longa.',

            'confirmacao_palavra_passe.same' => 'A nova palavra-passe e a confirmação não coincidem.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
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
            'codigo_redefinicao' => 'código de redefinição',

            'email' => 'endereço de e-mail',

            'palavra_passe' => 'nova palavra-passe',

            'confirmacao_palavra_passe' => 'confirmação da nova palavra-passe',
        ];
    }

    /**
     * Obtém o código de redefinição validado.
     *
     * @return string Código de redefinição.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function codigoRedefinicao(): string
    {
        return $this->obterTextoValidado(
            'codigo_redefinicao',
        );
    }

    /**
     * Obtém o endereço de e-mail validado.
     *
     * @return string Endereço de e-mail normalizado.
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
     * Obtém a nova palavra-passe validada.
     *
     * @return string Nova palavra-passe em texto simples.
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
     * Obtém a confirmação da nova palavra-passe.
     *
     * @return string Confirmação da nova palavra-passe.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function confirmacaoPalavraPasse(): string
    {
        return $this->obterTextoValidado(
            'confirmacao_palavra_passe',
        );
    }

    /**
     * Obtém um texto validado.
     *
     * @param  string  $campo  Nome do campo validado.
     * @return string Texto validado.
     *
     * @throws LogicException Quando o valor validado possui um tipo
     *                        inesperado.
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
