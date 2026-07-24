<?php

declare(strict_types=1);

namespace App\Http\Requests\Interacoes;

use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Define a validação comum dos pedidos de comentários.
 *
 * É utilizado pelos pedidos de criação, resposta e atualização de
 * comentários.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
abstract class PedidoComentarioRequest extends FormRequest
{
    /**
     * Número máximo de caracteres permitido num comentário.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const LIMITE_CARACTERES = 2000;

    /**
     * Determina se o pedido pode ser processado.
     *
     * A autorização da operação é realizada pelo controlador através da
     * política dos comentários.
     *
     * @return bool Verdadeiro para permitir a validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza o conteúdo antes da validação.
     *
     * Os finais de linha são convertidos para o formato Unix e os espaços
     * exteriores são removidos. Os espaços internos são preservados.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     * @return array<string, array<int, string>> Regras de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function rules(): array
    {
        return [
            'conteudo' => [
                'bail',
                'required',
                'string',
                'max:'.self::LIMITE_CARACTERES,
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function messages(): array
    {
        return [
            'conteudo.required' => 'Por favor, insere o texto do comentário.',

            'conteudo.string' => 'O comentário deve ser uma sequência de caracteres.',

            'conteudo.max' => 'O comentário não pode ter mais de 2000 caracteres.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     * @throws LogicException Quando o conteúdo validado não é uma string.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    final public function obterConteudo(): string
    {
        $conteudo = $this->validated(
            'conteudo',
        );

        if (! is_string($conteudo)) {
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
     *
     * @version 1.0.0
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
        );
    }
}
