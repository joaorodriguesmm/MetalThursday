<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gere os pedidos de atualização de permissões de e-mail.
 *
 * @since 1.0
 * @version 1.0
 */
class UpdateEmailPermissionsRequest extends FormRequest
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
            'email_permissions.array'    => 'O campo de permissões de e-mail deve ser um array.',
            'email_permissions.*.exists' => 'Uma ou mais permissões de e-mail selecionadas não são válidas.',
        ];
    }
}
