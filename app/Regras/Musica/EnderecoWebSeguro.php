<?php

declare(strict_types=1);

namespace App\Regras\Musica;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida endereços HTTP ou HTTPS sem credenciais incorporadas.
 *
 * @since 2.0.0
 */
final class EnderecoWebSeguro implements ValidationRule
{
    /**
     * Valida o endereço recebido.
     *
     * @param  string  $attribute  Atributo validado.
     * @param  mixed  $value  Valor recebido.
     * @param  Closure(string): void  $fail  Função de falha.
     *
     * @since 2.0.0
     */
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if (! is_string($value)) {
            return;
        }

        $endereco = trim($value);

        if (
            $endereco === ''
            || filter_var(
                $endereco,
                FILTER_VALIDATE_URL,
            ) === false
        ) {
            $fail(
                'A ligação deve conter um endereço web válido.',
            );

            return;
        }

        $esquema = mb_strtolower(
            (string) parse_url(
                $endereco,
                PHP_URL_SCHEME,
            ),
        );

        if (! in_array($esquema, ['http', 'https'], true)) {
            $fail(
                'A ligação deve utilizar HTTP ou HTTPS.',
            );

            return;
        }

        if (
            parse_url(
                $endereco,
                PHP_URL_USER,
            ) !== null
            || parse_url(
                $endereco,
                PHP_URL_PASS,
            ) !== null
        ) {
            $fail(
                'A ligação não pode incluir credenciais.',
            );
        }
    }
}
