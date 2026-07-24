<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida a ligação da compilação associada a uma edição.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class AtualizarLigacaoCompilacaoEdicaoRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
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
     * Normaliza a ligação antes da validação.
     *
     * Uma string vazia é convertida para nulo, permitindo remover a ligação
     * atualmente associada à edição.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $ligacao = $this->input(
            'ligacao_compilacao',
        );

        if (! is_string($ligacao)) {
            return;
        }

        $ligacao = trim(
            $ligacao,
        );

        $this->merge([
            'ligacao_compilacao' => $ligacao !== ''
                ? $ligacao
                : null,
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
            'ligacao_compilacao' => [
                'present',
                'nullable',
                'string',
                'url:http,https',
                'max:2048',
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
            'ligacao_compilacao.present' => 'Não foi recebida a ligação da compilação.',

            'ligacao_compilacao.string' => 'A ligação da compilação não é válida.',

            'ligacao_compilacao.url' => 'A ligação da compilação deve ser um endereço HTTP ou HTTPS válido.',

            'ligacao_compilacao.max' => 'A ligação da compilação não pode ter mais de 2048 caracteres.',
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
            'ligacao_compilacao' => 'ligação da compilação',
        ];
    }

    /**
     * Obtém a ligação validada.
     *
     * @return string|null Ligação normalizada ou nulo.
     *
     * @throws LogicException Quando o valor validado não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterLigacaoCompilacao(): ?string
    {
        $ligacao = $this->validated(
            'ligacao_compilacao',
        );

        if ($ligacao === null) {
            return null;
        }

        if (! is_string($ligacao)) {
            throw new LogicException(
                'O pedido validado não contém uma ligação válida.',
            );
        }

        return $ligacao;
    }
}
