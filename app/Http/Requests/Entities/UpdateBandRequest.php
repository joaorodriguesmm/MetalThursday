<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', Rule::unique('bands')->ignore($this->band->id)],
            'country_id' => ['required', 'exists:countries,id'],
            'genres'     => ['required', 'array', 'min:1'],
            'genres.*'   => ['exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'Por favor, insere o nome da banda.',
            'name.string'         => 'O nome deve ser uma sequência de caracteres.',
            'name.max'            => 'O nome deve ter no máximo 255 caracteres.',
            'name.unique'         => 'Já existe uma banda com o nome inserido.',
            'country_id.required' => 'Por favor, seleciona o país.',
            'country_id.exists'   => 'O pais selecionado é inválido.',
            'genres.required'     => 'Por favor, seleciona pelo menos um género.',
            'genres.array'        => 'Os géneros devem ser uma seleção valida.',
            'genres.min'          => 'Por favor, seleciona pelo menos um género.',
            'genres.*.exists'     => 'O género selecionado é inválido.',
        ];
    }
}
