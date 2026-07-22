<?php

namespace App\Http\Requests\MetalThursday;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gere os pedidos de criação/edição de edição.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class StoreEditionRequest extends FormRequest
{
    /**
     * Obtém se o utilizador é autorizado a executar o pedido.
     *
     * @return bool - Verdadeiro se o utilizador é autorizado.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação dos dados do pedido.
     *
     * @return array - Regras de validação.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mt_editions')->whereNull('deleted_at'),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Obtém as mensagens de erro para o pedido.
     *
     * @return array - Mensagens de erro.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Por favor, insere o nome da edição.',
            'name.string' => 'O nome da edição deve ser uma sequência de caracteres.',
            'name.max' => 'O nome da edição deve ter no máximo 255 caracteres.',
            'name.unique' => 'Já existe uma edição com esse nome.',
            'start_date.required' => 'Por favor, insere a data de início da edição.',
            'start_date.date' => 'A data de início da edição deve ser uma data válida.',
            'end_date.date' => 'A data de fim da edição deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de fim não deve ser inferior à data de início.',
        ];
    }
}
