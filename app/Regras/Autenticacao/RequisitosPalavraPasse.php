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
 * redefinição da palavra-passe, bem como em comandos ou serviços internos.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class RequisitosPalavraPasse
{
    /**
     * Comprimento mínimo exigido para uma palavra-passe.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MINIMO = 12;

    /**
     * Impede a instanciação desta classe utilitária.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function __construct() {}

    /**
     * Cria a regra de validação da palavra-passe.
     *
     * A palavra-passe deve possuir pelo menos doze caracteres, incluindo
     * letras maiúsculas e minúsculas, números e símbolos.
     *
     * @return Password Regra configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     * Valida diretamente uma palavra-passe em texto simples.
     *
     * Este método permite aplicar os mesmos requisitos fora dos pedidos HTTP,
     * nomeadamente em comandos, importações ou serviços internos.
     *
     * @param  string  $palavraPasse  Palavra-passe em texto simples.
     *
     * @throws InvalidArgumentException Quando a palavra-passe não cumpre os
     *                                  requisitos de segurança.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function validar(
        #[SensitiveParameter]
        string $palavraPasse,
    ): void {
        $validador =
            Validator::make(
                [
                    'palavra_passe' => $palavraPasse,
                ],
                [
                    'palavra_passe' => [
                        'required',
                        'string',
                        self::regra(),
                    ],
                ],
                [
                    'palavra_passe.required' => 'A palavra-passe é obrigatória.',

                    'palavra_passe.string' => 'A palavra-passe não é válida.',
                ],
            );

        if ($validador->passes()) {
            return;
        }

        $mensagem =
            $validador
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
