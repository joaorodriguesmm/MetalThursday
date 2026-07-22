<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gere os pedidos de criação/edição de género.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class StoreGenreRequest extends FormRequest
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
     * Obtém as regras de validação para o pedido.
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
                Rule::unique('genres')->whereNull('deleted_at'),
            ],
            'parent_genres' => ['nullable', 'array'],
            'parent_genres.*' => ['exists:genres,id'],
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
            'name.required' => 'Por favor, insere o nome do género.',
            'name.string' => 'O nome deve ser uma sequência de caracteres.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'name.unique' => 'Já existe um género com o nome inserido.',
            'parent_genres.array' => 'Os géneros pai devem ser uma seleção válida.',
            'parent_genres.*.exists' => 'Um dos géneros pai selecionados é inválido.',
        ];
    }
}
