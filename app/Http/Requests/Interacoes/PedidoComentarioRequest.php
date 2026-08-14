<?php

declare(strict_types=1);

namespace App\Http\Requests\Interacoes;

use App\Models\Interacoes\Comentario;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Define a validação comum dos pedidos de comentários.
 *
 * É utilizado pelos pedidos de criação, resposta e atualização de
 * comentários. Cada pedido concreto aplica a respetiva política antes da
 * normalização e da validação dos dados.
 *
 * A normalização efetuada nesta camada melhora a resposta de validação
 * apresentada ao cliente. O modelo {@see Comentario} volta a validar o
 * conteúdo antes da persistência, protegendo outros pontos de entrada.
 *
 * @since 2.0.0
 */
abstract class PedidoComentarioRequest extends FormRequest
{
    /**
     * Normaliza o conteúdo antes da validação.
     *
     * Os finais de linha são convertidos para o formato Unix. Os espaços,
     * tabulações e quebras de linha exteriores são removidos, preservando os
     * restantes caracteres para que a validação possa rejeitá-los quando
     * necessário.
     *
     * Os espaços e as quebras de linha interiores são preservados.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $conteudo = $this->input(
            'conteudo',
        );

        if (! is_string($conteudo)) {
            return;
        }

        $this->merge([
            'conteudo' => $this->normalizarConteudo(
                $conteudo,
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, list<string|Closure>> Regras de validação.
     *
     * @since 2.0.0
     */
    public function rules(): array
    {
        return [
            'conteudo' => [
                'bail',
                'required',
                'string',
                'max:'.Comentario::COMPRIMENTO_MAXIMO_CONTEUDO,

                /**
                 * Confirma que o conteúdo é texto UTF-8 válido e não contém
                 * caracteres de controlo incompatíveis com um comentário.
                 *
                 * @param  string  $atributo  Nome do atributo.
                 * @param  mixed  $valor  Valor recebido.
                 * @param  Closure(string): void  $falhar  Função de erro.
                 *
                 * @since 2.0.0
                 */
                static function (
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
                    ) {
                        $falhar(
                            'O comentário contém texto inválido.',
                        );

                        return;
                    }

                    if (
                        preg_match(
                            '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                            $valor,
                        ) === 1
                    ) {
                        $falhar(
                            'O comentário contém caracteres inválidos.',
                        );
                    }
                },
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     */
    public function messages(): array
    {
        return [
            'conteudo.required' => 'Por favor, insere o texto do comentário.',

            'conteudo.string' => 'O comentário deve ser uma sequência de caracteres.',

            'conteudo.max' => sprintf(
                'O comentário não pode ter mais de %d caracteres.',
                Comentario::COMPRIMENTO_MAXIMO_CONTEUDO,
            ),
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
            'conteudo' => 'comentário',
        ];
    }

    /**
     * Obtém o conteúdo validado do comentário.
     *
     * @return string Conteúdo normalizado.
     *
     * @throws LogicException Quando o resultado validado não contém uma
     *                        string não vazia.
     *
     * @since 2.0.0
     */
    final public function obterConteudo(): string
    {
        $conteudo = $this->validated(
            'conteudo',
        );

        if (
            ! is_string($conteudo)
            || $conteudo === ''
        ) {
            throw new LogicException(
                'O pedido validado não contém o texto do comentário.',
            );
        }

        return $conteudo;
    }

    /**
     * Normaliza o conteúdo de um comentário.
     *
     * @param  string  $conteudo  Conteúdo recebido.
     * @return string Conteúdo normalizado.
     *
     * @since 2.0.0
     */
    private function normalizarConteudo(
        string $conteudo,
    ): string {
        return trim(
            str_replace(
                [
                    "\r\n",
                    "\r",
                ],
                "\n",
                $conteudo,
            ),
            " \t\n",
        );
    }
}
