<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\ObjetosValor\Utilizadores\EnderecoEmail;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;
use LogicException;

/**
 * Valida apenas o formato do endereço, sem revelar a existência de contas.
 *
 * @since 2.0.0
 */
final class ReenviarVerificacaoEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => mb_strtolower(trim($email))]);
        }
    }

    /**
     * @return array<string, list<string|Closure>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                static function (string $atributo, mixed $valor, Closure $falhar): void {
                    if (! is_string($valor)) {
                        return;
                    }

                    try {
                        EnderecoEmail::deTexto($valor);
                    } catch (InvalidArgumentException) {
                        $falhar('Por favor, insere um endereço de e-mail válido.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Por favor, insere o teu endereço de e-mail.',
            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',
        ];
    }

    public function email(): string
    {
        $email = $this->validated('email');

        if (! is_string($email)) {
            throw new LogicException('O pedido validado não contém um endereço de e-mail válido.');
        }

        return EnderecoEmail::deTexto($email)->valor();
    }
}
