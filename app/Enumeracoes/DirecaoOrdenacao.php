<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa uma direção de ordenação disponibilizada pela aplicação.
 *
 * Os valores persistidos em parâmetros públicos utilizam português. A
 * conversão para os valores técnicos reconhecidos pelo construtor de
 * consultas é efetuada explicitamente através de {@see paraSql()}.
 *
 * @since 2.0.0
 *
 * @version 2.0.0
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
     * Tenta criar uma direção a partir de um valor textual.
     *
     * Apenas os valores públicos definidos pela própria enumeração são
     * aceites. A normalização limita-se à remoção de espaços exteriores e à
     * conversão para minúsculas.
     *
     * Valores técnicos como `asc` e `desc` não são parâmetros públicos
     * válidos e, consequentemente, não são aceites.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return self|null Direção correspondente ou nula.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public static function tentarCriar(
        mixed $valor,
    ): ?self {
        if (! is_string($valor)) {
            return null;
        }

        return self::tryFrom(
            mb_strtolower(
                trim(
                    $valor,
                ),
            ),
        );
    }

    /**
     * Obtém a etiqueta apresentada ao utilizador.
     *
     * @return string Etiqueta da direção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Ascendente => 'Ascendente',
            self::Descendente => 'Descendente',
        };
    }

    /**
     * Obtém a direção reconhecida pelo construtor de consultas.
     *
     * @return 'asc'|'desc' Direção técnica de ordenação.
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
