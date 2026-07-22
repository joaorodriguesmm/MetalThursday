<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Valida os pedidos de alteração da palavra-passe.
 *
 * As palavras-passe não são normalizadas, uma vez que espaços e restantes
 * caracteres podem fazer parte do segredo definido pelo utilizador.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class AtualizarPalavraPasseRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da alteração da palavra-passe.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $errorBag = 'palavraPasse';

    /**
     * Determina se o pedido pode ser executado.
     *
     * @return bool - Verdadeiro quando existe um utilizador autenticado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function authorize(): bool
    {
        return $this->user() instanceof Utilizador;
    }

    /**
     * Obtém as regras de validação aplicáveis ao pedido.
     *
     * A palavra-passe atual é confirmada através do guard de sessão `web`.
     * A nova palavra-passe utiliza a política global da aplicação e não pode
     * coincidir com o valor introduzido como palavra-passe atual.
     *
     * @return array<string, array<int, mixed>> - Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        return [
            'palavra_passe_atual' => [
                'bail',
                'required',
                'string',
                'current_password:web',
            ],

            'nova_palavra_passe' => [
                'bail',
                'required',
                'string',
                'different:palavra_passe_atual',
                'confirmed:confirmacao_nova_palavra_passe',
                Password::defaults(),
            ],

            'confirmacao_nova_palavra_passe' => [
                'required',
                'string',
                'same:nova_palavra_passe',
            ],
        ];
    }

    /**
     * Obtém as mensagens de erro específicas do pedido.
     *
     * @return array<string, string> - Mensagens de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function messages(): array
    {
        return [
            'palavra_passe_atual.required' => 'Por favor, insere a tua palavra-passe atual.',

            'palavra_passe_atual.string' => 'A palavra-passe atual não é válida.',

            'palavra_passe_atual.current_password' => 'A palavra-passe atual introduzida não está correta.',

            'nova_palavra_passe.required' => 'Por favor, insere a nova palavra-passe.',

            'nova_palavra_passe.string' => 'A nova palavra-passe não é válida.',

            'nova_palavra_passe.different' => 'A nova palavra-passe deve ser diferente da palavra-passe atual.',

            'nova_palavra_passe.confirmed' => 'A confirmação da nova palavra-passe não coincide.',

            'confirmacao_nova_palavra_passe.required' => 'Por favor, confirma a nova palavra-passe.',

            'confirmacao_nova_palavra_passe.string' => 'A confirmação da nova palavra-passe não é válida.',

            'confirmacao_nova_palavra_passe.same' => 'A confirmação da nova palavra-passe não corresponde à nova palavra-passe.',
        ];
    }

    /**
     * Obtém os nomes legíveis dos atributos validados.
     *
     * @return array<string, string> - Nomes dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function attributes(): array
    {
        return [
            'palavra_passe_atual' => 'palavra-passe atual',

            'nova_palavra_passe' => 'nova palavra-passe',

            'confirmacao_nova_palavra_passe' => 'confirmação da nova palavra-passe',
        ];
    }
}
