<?php

declare(strict_types=1);

namespace App\Regras\Autenticacao;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Centraliza a política de segurança das palavras-passe.
 *
 * A mesma regra pode ser utilizada no registo, alteração de palavra-passe,
 * reposição de palavra-passe e criação administrativa de utilizadores.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PoliticaPalavraPasse
{
    /**
     * Comprimento mínimo da palavra-passe.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MINIMO = 12;

    /**
     * Cria a regra de validação da palavra-passe.
     *
     * A palavra-passe deve conter letras maiúsculas e minúsculas, números e
     * símbolos.
     *
     * @return Password - Regra configurada.
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
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    /**
     * Valida diretamente uma palavra-passe.
     *
     * Este método protege utilizações fora de pedidos HTTP, como comandos,
     * importações ou serviços internos.
     *
     * @param  string  $palavraPasse  - Palavra-passe em texto simples.
     *
     * @throws InvalidArgumentException Quando não cumpre a política.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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

        if (! $validador->fails()) {
            return;
        }

        throw new InvalidArgumentException(
            $validador
                ->errors()
                ->first('palavra_passe'),
        );
    }
}
