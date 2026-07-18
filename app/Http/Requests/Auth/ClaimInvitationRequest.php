<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

/**
 * Gere os pedidos de registo por convite.
 *
 * @since 1.0
 * @version 1.0
 */
class ClaimInvitationRequest extends FormRequest
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
            'invite_code'         => ['required', 'string', 'exists:users,invite_code'],
            'photo'               => ['nullable', 'image', 'max:10240'],
            'name'                => ['required', 'string', 'min:3', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password'            => ['required', 'min:8', 'confirmed', Rules\Password::defaults()],
            'email_permissions'   => ['nullable', 'array'],
            'email_permissions.*' => ['exists:email_permissions,id'],
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
            'invite_code.*'       => 'Ocorreu um erro ao validar a integridade do convite. Recarrega a página e tenta novamente.',
            'photo.image'         => 'A fotografia deve ser uma imagem válida.',
            'photo.max'           => 'A fotografia não pode ter mais de 10MB.',
            'name.required'       => 'Por favor, insere o teu nome.',
            'name.string'         => 'O nome deve ser uma sequência de caracteres.',
            'name.min'            => 'O nome deve ter no mínimo 3 caracteres.',
            'name.max'            => 'O nome deve ter no máximo 255 caracteres.',
            'email.required'      => 'Por favor, insere o teu e-mail.',
            'name.string'         => 'O e-mail deve ser uma sequência de caracteres.',
            'email.email'         => 'Por favor, insere um e-mail válido.',
            'email.max'           => 'O e-mail deve ter no máximo 255 caracteres.',
            'email.unique'        => 'O e-mail inserido já foi utilizado por outro utilizador.',
            'password.required'   => 'Por favor, insere a palavra-passe.',
            'password.min'        => 'A palavra-passe deve ter no mínimo 8 caracteres.',
            'password.confirmed'  => 'As palavras-passe não coincidem.',
            'email_permissions.*' => 'Houve um erro ao validar as permissões de e-mail. Por favor, tenta novamente.',
        ];
    }
}
