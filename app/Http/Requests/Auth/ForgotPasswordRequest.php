<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gere os pedidos de recuperação de password.
 *
 * @since 1.0
 * @version 1.0
 */
class ForgotPasswordRequest extends FormRequest
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
     * Obtém as regras de validação do pedido.
     *
     * @return array - Regras de validação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
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
            'email.required' => 'Por favor, insere o teu e-mail.',
            'email.string'   => 'O e-mail deve ser uma sequência de caracteres.',
            'email.email'    => 'Por favor, insere um e-mail válido.',
            'email.exists'   => 'Não existe nenhum utilizador com o e-mail inserido.',
        ];
    }
}
