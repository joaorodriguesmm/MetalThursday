<?php

namespace App\Http\Requests\Interactions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gere os pedidos de atualização de comentários.
 *
 * @since 1.0
 * @version 1.0
 */
class UpdateCommentRequest extends FormRequest
{
    /**
     * Determina se o utilizador está autorizado a fazer o pedido.
     *
     * @return bool - Verdadeiro se o utilizador é autorizado.
     *
     * @since 1.0
     * @version 1.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam ao pedido.
     *
     * @return array - Regras de validação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Obtém as mensagens de erro para o pedido.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Por favor, insere o texto do comentário.',
            'content.string'   => 'O comentário deve ser uma sequência de caracteres.',
            'content.max'      => 'O comentário deve ter no máximo 2000 caracteres.',
        ];
    }
}
