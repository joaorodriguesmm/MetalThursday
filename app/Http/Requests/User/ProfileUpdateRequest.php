<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gere pedidos de atualização de perfil.
 *
 * @since 1.0
 * @version 1.0
 */
class ProfileUpdateRequest extends FormRequest
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
            'photo' => ['nullable', 'image', 'max:10240'],
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
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
            'photo.image'    => 'A fotografia deve ser uma imagem válida.',
            'photo.max'      => 'A fotografia não pode ter mais de 10MB.',
            'name.required'  => 'Por favor, insere o teu nome.',
            'name.string'    => 'O nome deve ser uma sequência de caracteres.',
            'name.min'       => 'O nome deve ter no mínimo 3 caracteres.',
            'name.max'       => 'O nome deve ter no máximo 255 caracteres.',
            'email.required' => 'Por favor, insere o teu e-mail.',
            'name.string'    => 'O e-mail deve ser uma sequência de caracteres.',
            'email.email'    => 'Por favor, insere um e-mail válido.',
            'email.max'      => 'O e-mail deve ter no máximo 255 caracteres.',
            'email.unique'   => 'O e-mail inserido já foi utilizado por outro utilizador.',
        ];
    }
}
