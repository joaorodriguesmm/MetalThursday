<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa uma direção de ordenação disponibilizada pela aplicação.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
enum DirecaoOrdenacao: string
{
    /**
     * Ordenação ascendente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Ascendente = 'ascendente';

    /**
     * Ordenação descendente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Descendente = 'descendente';

    /**
     * Tenta criar uma direção de ordenação a partir de um valor recebido.
     *
     * Os valores `asc` e `desc` são temporariamente aceites para garantir
     * compatibilidade com os parâmetros utilizados na versão 1.0.0.
     *
     * @param  mixed  $valor  - Valor a converter.
     * @return self|null - Direção correspondente ou null quando o valor não
     *                   é reconhecido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public static function tentarCriar(mixed $valor): ?self
    {
        if (! is_string($valor)) {
            return null;
        }

        return match ($valor) {
            self::Ascendente->value,
            'asc' => self::Ascendente,

            self::Descendente->value,
            'desc' => self::Descendente,

            default => null,
        };
    }

    /**
     * Obtém a direção reconhecida pelo construtor de consultas.
     *
     * @return string - Direção SQL equivalente.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function paraSql(): string
    {
        return match ($this) {
            self::Ascendente => 'asc',
            self::Descendente => 'desc',
        };
    }
}
