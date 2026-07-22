<?php

namespace App\Http\Requests\Entities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('genres')->ignore($this->genre->id)],
            'parent_genres' => ['nullable', 'array'],
            'parent_genres.*' => ['exists:genres,id'],
        ];
    }

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
