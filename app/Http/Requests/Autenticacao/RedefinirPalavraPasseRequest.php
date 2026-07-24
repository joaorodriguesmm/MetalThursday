<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Valida um pedido de redefinição da palavra-passe.
 *
 * A validade do token, a correspondência com o endereço de e-mail e a
 * existência do utilizador são verificadas pelo gestor de palavras-passe.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class RedefinirPalavraPasseRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * @return bool Verdadeiro para permitir a validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza o token e o endereço de e-mail.
     *
     * A palavra-passe não é alterada, porque os espaços podem fazer parte do
     * seu valor.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $token = $this->input('token');
        $email = $this->input('email');

        $this->merge([
            'token' => is_string($token)
                ? trim($token)
                : $token,

            'email' => is_string($email)
                ? mb_strtolower(trim($email))
                : $email,
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Os nomes dos campos permanecem em inglês por fazerem parte do contrato
     * utilizado pelo gestor de redefinição de palavras-passe do Laravel.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        return [
            'token' => [
                'bail',
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],

            'password' => [
                'bail',
                'required',
                'string',
                'confirmed',
                Password::defaults(),
            ],

            'password_confirmation' => [
                'bail',
                'required',
                'string',
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function messages(): array
    {
        return [
            'token.required' => 'A ligação de redefinição não contém um token válido.',

            'token.string' => 'A ligação de redefinição não contém um token válido.',

            'token.max' => 'A ligação de redefinição não contém um token válido.',

            'email.required' => 'A ligação de redefinição não contém um endereço de e-mail.',

            'email.string' => 'A ligação de redefinição não contém um endereço de e-mail válido.',

            'email.email' => 'A ligação de redefinição não contém um endereço de e-mail válido.',

            'email.max' => 'A ligação de redefinição não contém um endereço de e-mail válido.',

            'password.required' => 'Por favor, insere a nova palavra-passe.',

            'password.string' => 'A nova palavra-passe deve ser uma sequência de caracteres.',

            'password.confirmed' => 'A nova palavra-passe e a confirmação não coincidem.',

            'password_confirmation.required' => 'Por favor, confirma a nova palavra-passe.',

            'password_confirmation.string' => 'A confirmação da nova palavra-passe não é válida.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function attributes(): array
    {
        return [
            'token' => 'token de redefinição',

            'email' => 'endereço de e-mail',

            'password' => 'nova palavra-passe',

            'password_confirmation' => 'confirmação da nova palavra-passe',
        ];
    }
}
