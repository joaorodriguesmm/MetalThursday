<?php

namespace App\Http\Requests\MetalThursday;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255', Rule::unique('mt_editions')->ignore($this->edition->id)],
            'start_date' => ['required', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Por favor, insere o nome da edição.',
            'name.string'             => 'O nome da edição deve ser uma sequência de caracteres.',
            'name.max'                => 'O nome da edição deve ter no máximo 255 caracteres.',
            'name.unique'             => 'Já existe uma edição com esse nome.',
            'start_date.required'     => 'Por favor, insere a data de início da edição.',
            'start_date.date'         => 'A data de início da edição deve ser uma data válida.',
            'end_date.date'           => 'A data de fim da edição deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de fim não deve ser inferior à data de início.',
        ];
    }
}
