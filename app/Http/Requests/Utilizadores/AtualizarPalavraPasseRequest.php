<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use LogicException;

/**
 * Valida os dados necessários para alterar a palavra-passe.
 *
 * As palavras-passe não são normalizadas, porque espaços e outros caracteres
 * podem fazer parte do segredo definido pelo utilizador.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class AtualizarPalavraPasseRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da alteração da palavra-passe.
     *
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    protected string $errorBag = 'palavraPasse';

    /**
     * Determina se o pedido pode ser processado.
     *
     * @return bool Verdadeiro quando existe um utilizador autenticado.
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
     * Obtém as regras de validação.
     *
     * A palavra-passe atual é confirmada através do guard `web`. A nova
     * palavra-passe utiliza a política global da aplicação, deve ser diferente
     * da atual e coincidir com o campo de confirmação.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
     * @version 2.1.0
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

            'nova_palavra_passe.confirmed' => 'A nova palavra-passe e a confirmação não coincidem.',

            'confirmacao_nova_palavra_passe.required' => 'Por favor, confirma a nova palavra-passe.',

            'confirmacao_nova_palavra_passe.string' => 'A confirmação da nova palavra-passe não é válida.',
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
            'palavra_passe_atual' => 'palavra-passe atual',

            'nova_palavra_passe' => 'nova palavra-passe',

            'confirmacao_nova_palavra_passe' => 'confirmação da nova palavra-passe',
        ];
    }

    /**
     * Obtém a nova palavra-passe validada.
     *
     * @return string Nova palavra-passe.
     *
     * @throws LogicException Quando o pedido validado não contém a nova
     *                        palavra-passe.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    public function obterNovaPalavraPasse(): string
    {
        $palavraPasse = $this->validated(
            'nova_palavra_passe',
        );

        if (! is_string($palavraPasse)) {
            throw new LogicException(
                'O pedido validado não contém a nova palavra-passe.',
            );
        }

        return $palavraPasse;
    }
}
