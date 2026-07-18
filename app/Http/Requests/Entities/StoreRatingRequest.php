<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gere os pedidos de criação/edição de avaliação.
 *
 * @since 1.0
 * @version 1.0
 */
class StoreRatingRequest extends FormRequest
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
            'rating' => ['required', 'numeric', 'min:0.5', 'max:10', 'regex:/^\d+(\.5|\.0)?$/'],
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
            'rating.*' => 'Ocorreu um erro, por favor tenta novamente.',
        ];
    }
}
