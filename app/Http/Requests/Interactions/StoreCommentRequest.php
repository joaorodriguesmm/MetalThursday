<?php

namespace App\Http\Requests\Interactions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gere os pedidos de criação de comentários.
 *
 * @version 1.0
 * @since 1.0
 */
class StoreCommentRequest extends FormRequest
{
    /**
     * Obtém se o utilizador é autorizado a executar o pedido.
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
     * Obtém as regras de validação para o pedido.
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
     * @return array - Mensagens de erro.
     *
     * @since 1.0
     * @version 1.0
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
