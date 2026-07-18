<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Gere os pedidos de redefinição de password.
 *
 * @since 1.0
 * @version 1.0
 */
class ResetPasswordRequest extends FormRequest
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
            'token'    => ['required'],
            'email'    => ['required', 'string', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
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
            'token.required'     => 'Ocorreu um erro ao validar a integridade do link. Recarrega a página e tenta novamente.',
            'email.*'            => 'Ocorreu um erro ao validar a integridade do link. Recarrega a página e tenta novamente.',
            'password.required'  => 'Por favor, insere a palavra-passe.',
            'password.min'       => 'A palavra-passe deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As palavras-passe não coincidem.',
        ];
    }
}
