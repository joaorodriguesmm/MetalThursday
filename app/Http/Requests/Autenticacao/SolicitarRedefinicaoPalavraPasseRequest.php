<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\ObjetosValor\Utilizadores\EnderecoEmail;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;
use LogicException;

/**
 * Valida o pedido de envio de uma ligação para redefinir a palavra-passe.
 *
 * O pedido valida apenas o formato do endereço. A existência de uma conta
 * associada não é verificada nesta camada nem exposta ao utilizador,
 * impedindo a enumeração de endereços registados.
 *
 * A normalização e a validação definitiva do endereço pertencem ao objeto de
 * valor {@see EnderecoEmail}.
 *
 * @since 1.0.0
 */
final class SolicitarRedefinicaoPalavraPasseRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * @return bool Verdadeiro para permitir a validação.
     *
     * @since 1.0.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza preliminarmente o endereço de e-mail.
     *
     * A normalização definitiva é novamente aplicada pelo objeto de valor
     * depois da validação estrutural do pedido.
     *
     * @since 2.0.0
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
     * Não é utilizada uma regra de existência, porque a resposta ao pedido
     * não deve revelar se o endereço está associado a uma conta.
     *
     * @return array<string, list<string|Closure>> Regras de validação.
     *
     * @since 1.0.0
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                $this->criarRegraEnderecoEmail(),
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',
        ];
    }

    /**
     * Obtém o nome apresentado para o atributo validado.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     */
    public function attributes(): array
    {
        return [
            'email' => 'endereço de e-mail',
        ];
    }

    /**
     * Obtém o endereço de e-mail validado e normalizado.
     *
     * @return string Endereço de e-mail normalizado.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato do objeto de valor.
     *
     * @since 2.0.0
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

        try {
            return EnderecoEmail::deTexto(
                $email,
            )->valor();
        } catch (InvalidArgumentException $excecao) {
            throw new LogicException(
                'O pedido validado não contém um endereço de e-mail válido.',
                previous: $excecao,
            );
        }
    }

    /**
     * Cria a regra de validação do endereço de e-mail.
     *
     * A sintaxe, o comprimento e a normalização definitiva pertencem ao
     * objeto de valor {@see EnderecoEmail}.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraEnderecoEmail(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (! is_string($valor)) {
                return;
            }

            try {
                EnderecoEmail::deTexto(
                    $valor,
                );
            } catch (InvalidArgumentException) {
                $falhar(
                    'Por favor, insere um endereço de e-mail válido.',
                );
            }
        };
    }
}
