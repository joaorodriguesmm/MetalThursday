<?php

declare(strict_types=1);

namespace App\Regras\Autenticacao;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Centraliza os requisitos de segurança das palavras-passe.
 *
 * Os mesmos requisitos são utilizados no registo, na alteração e na
 * redefinição da palavra-passe, bem como em comandos e serviços internos.
 *
 * @since 2.0.0
 */
final class RequisitosPalavraPasse
{
    /**
     * Comprimento mínimo exigido.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MINIMO = 12;

    /**
     * Comprimento máximo aceite.
     *
     * Este limite evita o processamento de entradas excessivamente grandes
     * pelos algoritmos de derivação de palavra-passe.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const COMPRIMENTO_MAXIMO = 4096;

    /**
     * Impede a instanciação desta classe utilitária.
     *
     * @since 2.0.0
     */
    private function __construct() {}

    /**
     * Obtém o comprimento mínimo exigido.
     *
     * @return int Comprimento mínimo.
     *
     * @since 2.0.0
     */
    public static function comprimentoMinimo(): int
    {
        return self::COMPRIMENTO_MINIMO;
    }

    /**
     * Obtém o comprimento máximo aceite.
     *
     * @return int Comprimento máximo.
     *
     * @since 2.0.0
     */
    public static function comprimentoMaximo(): int
    {
        return self::COMPRIMENTO_MAXIMO;
    }

    /**
     * Cria a regra de complexidade da palavra-passe.
     *
     * A palavra-passe deve possuir letras maiúsculas e minúsculas, números
     * e símbolos.
     *
     * @return Password Regra configurada.
     *
     * @since 2.0.0
     */
    public static function regra(): Password
    {
        return Password::min(
            self::COMPRIMENTO_MINIMO,
        )
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    /**
     * Obtém as regras aplicáveis a uma palavra-passe obrigatória.
     *
     * @return array<int, string|Password> Regras de validação.
     *
     * @since 2.0.0
     */
    public static function regrasObrigatorias(): array
    {
        return [
            'bail',
            'required',
            'string',
            'max:'.self::COMPRIMENTO_MAXIMO,
            self::regra(),
        ];
    }

    /**
     * Valida diretamente uma palavra-passe em texto simples.
     *
     * Permite aplicar os mesmos requisitos fora dos pedidos HTTP,
     * nomeadamente em comandos, importações e serviços internos.
     *
     * @param  string  $palavraPasse  Palavra-passe em texto simples.
     *
     * @throws InvalidArgumentException Quando a palavra-passe não cumpre os
     *                                  requisitos de segurança.
     *
     * @since 2.0.0
     */
    public static function validar(
        #[SensitiveParameter]
        string $palavraPasse,
    ): void {
        $validador = Validator::make(
            [
                'palavra_passe' => $palavraPasse,
            ],
            [
                'palavra_passe' => self::regrasObrigatorias(),
            ],
            [
                'palavra_passe.required' => 'A palavra-passe é obrigatória.',

                'palavra_passe.string' => 'A palavra-passe não é válida.',

                'palavra_passe.max' => 'A palavra-passe é demasiado longa.',
            ],
            [
                'palavra_passe' => 'palavra-passe',
            ],
        );

        if ($validador->passes()) {
            return;
        }

        $mensagem = $validador
            ->errors()
            ->first(
                'palavra_passe',
            );

        throw new InvalidArgumentException(
            $mensagem !== ''
                ? $mensagem
                : 'A palavra-passe não cumpre os requisitos de segurança.',
        );
    }
}
