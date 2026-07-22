<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gere os pedidos de criação/edição de banda.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class StoreBandRequest extends FormRequest
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
                Rule::unique('bands')->whereNull('deleted_at'),
            ],
            'country_id' => ['required', 'exists:countries,id'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['exists:genres,id'],
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
            'name.required' => 'Por favor, insere o nome da banda.',
            'name.string' => 'O nome deve ser uma sequência de caracteres.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'name.unique' => 'Já existe uma banda com o nome inserido.',
            'country_id.required' => 'Por favor, seleciona o país.',
            'country_id.exists' => 'O pais selecionado é inválido.',
            'genres.required' => 'Por favor, seleciona pelo menos um género.',
            'genres.array' => 'Os géneros devem ser uma seleção valida.',
            'genres.min' => 'Por favor, seleciona pelo menos um género.',
            'genres.*.exists' => 'O género selecionado é inválido.',
        ];
    }
}
