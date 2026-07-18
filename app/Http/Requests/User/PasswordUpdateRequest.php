<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Gere pedidos de atualização de password.
 *
 * @since 1.0
 * @version 1.0
 */
class PasswordUpdateRequest extends FormRequest
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
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => ['required', 'min:8', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Obtém as mensagens de erro do pedido.
     *
     * @return array - Mensagens de erro.
     *
     * @since 1.0
     * @version 1.0
     */
    public function messages(): array
    {
        return [
            'current_password.required'         => 'Por favor, insere a tua palavra-passe atual.',
            'current_password.string'           => 'A palavra-passe atual deve ser uma sequência de caracteres.',
            'current_password.current_password' => 'A palavra-passe atual inserida não corresponde à tua palavra-passe atual.',
            'password.required'                 => 'Por favor, insere a nova palavra-passe.',
            'password.min'                      => 'A nova palavra-passe deve ter pelo menos 8 caracteres.',
            'password.confirmed'                => 'As palavras-passe não coincidem.',
        ];
    }
}
