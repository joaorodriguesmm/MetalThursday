<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday\Suporte;

use Closure;

/**
 * Cria as regras textuais partilhadas pelos pedidos de MetalThursday.
 *
 * As regras distinguem texto UTF-8 inválido de caracteres de controlo não
 * permitidos e deixam as regras de tipo do Laravel tratar valores que não
 * sejam strings.
 *
 * @since 2.0.0
 */
final class RegrasTextoMetalThursday
{
    /**
     * Cria uma regra para texto de uma única linha.
     *
     * @param  string  $mensagemTextoInvalido  Mensagem para UTF-8 inválido.
     * @param  string  $mensagemCaracteresInvalidos  Mensagem para caracteres
     *                                               de controlo.
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    public static function criarRegraTextoLinha(
        string $mensagemTextoInvalido,
        string $mensagemCaracteresInvalidos,
    ): Closure {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ) use (
            $mensagemTextoInvalido,
            $mensagemCaracteresInvalidos,
        ): void {
            if (
                $valor === null
                || ! is_string($valor)
            ) {
                return;
            }

            if (
                preg_match(
                    '//u',
                    $valor,
                ) !== 1
            ) {
                $falhar(
                    $mensagemTextoInvalido,
                );

                return;
            }

            if (
                preg_match(
                    '/[\x00-\x1F\x7F]/',
                    $valor,
                ) === 1
            ) {
                $falhar(
                    $mensagemCaracteresInvalidos,
                );
            }
        };
    }

    /**
     * Cria uma regra para texto com várias linhas.
     *
     * São permitidas tabulações e quebras de linha. Os restantes caracteres
     * de controlo são rejeitados.
     *
     * @param  string  $mensagemTextoInvalido  Mensagem para UTF-8 inválido.
     * @param  string  $mensagemCaracteresInvalidos  Mensagem para caracteres
     *                                               de controlo.
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    public static function criarRegraTextoMultilinha(
        string $mensagemTextoInvalido,
        string $mensagemCaracteresInvalidos,
    ): Closure {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ) use (
            $mensagemTextoInvalido,
            $mensagemCaracteresInvalidos,
        ): void {
            if (
                $valor === null
                || ! is_string($valor)
            ) {
                return;
            }

            if (
                preg_match(
                    '//u',
                    $valor,
                ) !== 1
            ) {
                $falhar(
                    $mensagemTextoInvalido,
                );

                return;
            }

            if (
                preg_match(
                    '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                    $valor,
                ) === 1
            ) {
                $falhar(
                    $mensagemCaracteresInvalidos,
                );
            }
        };
    }
}
