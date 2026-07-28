<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida o pedido de envio de uma ligação para redefinir a palavra-passe.
 *
 * O pedido valida apenas o formato do endereço. A existência da conta não é
 * exposta ao utilizador, impedindo a enumeração de endereços registados.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class SolicitarRedefinicaoPalavraPasseRequest extends FormRequest
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
     * Normaliza o endereço de e-mail antes da validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $email =
            $this->input(
                'email',
            );

        if (! is_string($email)) {
            return;
        }

        $this->merge([
            'email' => mb_strtolower(
                trim(
                    $email,
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Não é utilizada a regra `exists`, porque a resposta não deve revelar
     * se o endereço está ou não associado a uma conta.
     *
     * @return array<string, array<int, string>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:255',
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
            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',

            'email.email' => 'Por favor, insere um endereço de e-mail válido.',

            'email.max' => 'O endereço de e-mail não pode ter mais de 255 caracteres.',
        ];
    }

    /**
     * Obtém o nome apresentado para o atributo validado.
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
            'email' => 'endereço de e-mail',
        ];
    }

    /**
     * Obtém o endereço de e-mail validado.
     *
     * @return string Endereço de e-mail normalizado.
     *
     * @throws LogicException Quando o valor validado possui um tipo
     *                        inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function email(): string
    {
        $email =
            $this->validated(
                'email',
            );

        if (! is_string($email)) {
            throw new LogicException(
                'O endereço de e-mail validado possui um tipo inesperado.',
            );
        }

        return $email;
    }
}
