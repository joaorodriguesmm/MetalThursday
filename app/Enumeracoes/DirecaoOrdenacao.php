<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa uma direção de ordenação disponibilizada pela aplicação.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
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
     * Os valores técnicos `asc` e `desc` são também aceites para manter
     * compatibilidade com parâmetros já utilizados pela aplicação.
     *
     * A comparação ignora espaços adicionais e diferenças entre letras
     * maiúsculas e minúsculas.
     *
     * @param  mixed  $valor  Valor a converter.
     * @return self|null Direção correspondente ou nula quando o valor não é
     *                   reconhecido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public static function tentarCriar(
        mixed $valor,
    ): ?self {
        if (! is_string($valor)) {
            return null;
        }

        $valorNormalizado = mb_strtolower(
            trim($valor),
        );

        return match ($valorNormalizado) {
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
     * @return string Direção de ordenação equivalente.
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
