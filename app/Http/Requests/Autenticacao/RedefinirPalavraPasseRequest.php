<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use LogicException;

/**
 * Valida um pedido de redefinição da palavra-passe.
 *
 * A validade do código, a correspondência com o endereço de e-mail e a
 * existência do utilizador são verificadas pelo gestor de palavras-passe do
 * Laravel.
 *
 * Os campos recebidos pelo formulário mantêm nomes portugueses. As chaves
 * técnicas `password`, `password_confirmation` e `token` apenas são
 * construídas pelo controlador ao invocar o gestor de palavras-passe.
 *
 * @since 1.0.0
 */
final class RedefinirPalavraPasseRequest extends FormRequest
{
    /**
     * Comprimento máximo aceite para o código de redefinição.
     *
     * Este é um limite técnico do pedido e protege o processamento de valores
     * anormalmente extensos.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO_CODIGO_REDEFINICAO = 255;

    /**
     * Mensagem apresentada quando os dados da ligação não são válidos.
     *
     * A mesma mensagem é utilizada para o código e para o endereço de e-mail,
     * evitando expor qual dos valores falhou ou se existe uma conta associada.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_LIGACAO_INVALIDA =
        'A ligação de redefinição é inválida ou já não está disponível. Solicita uma nova ligação.';

    /**
     * Determina se o pedido pode ser processado.
     *
     * @return bool Verdadeiro para permitir a validação.
     *
     * @since 1.0.0
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
     * fonte central. A sintaxe, o comprimento e a normalização definitiva do
     * endereço de e-mail pertencem ao objeto de valor
     * {@see EnderecoEmail}.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 1.0.0
     */
    public function rules(): array
    {
        return [
            'codigo_redefinicao' => [
                'bail',
                'required',
                'string',
                'max:'.self::COMPRIMENTO_MAXIMO_CODIGO_REDEFINICAO,
                $this->criarRegraCodigoRedefinicao(),
            ],

            'email' => [
                'bail',
                'required',
                'string',
                $this->criarRegraEnderecoEmail(),
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
     * Obtém as validações executadas depois das regras principais.
     *
     * Os erros associados ao código e ao endereço de e-mail pertencem a
     * campos ocultos. A chave `ligacao_redefinicao` permite apresentar uma
     * única mensagem segura e visível na vista.
     *
     * @return list<callable(Validator): void> Validações adicionais.
     *
     * @since 2.0.0
     */
    public function after(): array
    {
        return [
            static function (
                Validator $validador,
            ): void {
                if (
                    ! $validador
                        ->errors()
                        ->hasAny([
                            'codigo_redefinicao',
                            'email',
                        ])
                ) {
                    return;
                }

                $validador
                    ->errors()
                    ->add(
                        'ligacao_redefinicao',
                        self::MENSAGEM_LIGACAO_INVALIDA,
                    );
            },
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     */
    public function messages(): array
    {
        return [
            'codigo_redefinicao.required' => self::MENSAGEM_LIGACAO_INVALIDA,

            'codigo_redefinicao.string' => self::MENSAGEM_LIGACAO_INVALIDA,

            'codigo_redefinicao.max' => self::MENSAGEM_LIGACAO_INVALIDA,

            'email.required' => self::MENSAGEM_LIGACAO_INVALIDA,

            'email.string' => self::MENSAGEM_LIGACAO_INVALIDA,

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
     * @since 2.0.0
     */
    public function codigoRedefinicao(): string
    {
        return $this->obterTextoValidado(
            'codigo_redefinicao',
        );
    }

    /**
     * Obtém o endereço de e-mail validado e normalizado.
     *
     * @return string Endereço de e-mail normalizado.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato do objeto de valor.
     *
     * @since 2.0.0
     */
    public function email(): string
    {
        $email =
            $this->obterTextoValidado(
                'email',
            );

        try {
            return EnderecoEmail::deTexto(
                $email,
            )->valor();
        } catch (InvalidArgumentException $excecao) {
            throw new LogicException(
                'O pedido validado não contém um endereço de e-mail válido.',
                previous: $excecao,
            );
        }
    }

    /**
     * Obtém a nova palavra-passe validada.
     *
     * @return string Nova palavra-passe em texto simples.
     *
     * @since 2.0.0
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
     * @since 2.0.0
     */
    public function confirmacaoPalavraPasse(): string
    {
        return $this->obterTextoValidado(
            'confirmacao_palavra_passe',
        );
    }

    /**
     * Cria a regra de validação do código de redefinição.
     *
     * O código não pode conter texto UTF-8 inválido, espaços ou caracteres de
     * controlo.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraCodigoRedefinicao(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (! is_string($valor)) {
                return;
            }

            if (
                preg_match(
                    '//u',
                    $valor,
                ) !== 1
                || preg_match(
                    '/[\x00-\x20\x7F]/',
                    $valor,
                ) === 1
            ) {
                $falhar(
                    self::MENSAGEM_LIGACAO_INVALIDA,
                );
            }
        };
    }

    /**
     * Cria a regra de validação do endereço de e-mail.
     *
     * A sintaxe, o comprimento e a normalização definitiva pertencem ao
     * objeto de valor {@see EnderecoEmail}.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraEnderecoEmail(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (! is_string($valor)) {
                return;
            }

            try {
                EnderecoEmail::deTexto(
                    $valor,
                );
            } catch (InvalidArgumentException) {
                $falhar(
                    self::MENSAGEM_LIGACAO_INVALIDA,
                );
            }
        };
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
     * @since 2.0.0
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
