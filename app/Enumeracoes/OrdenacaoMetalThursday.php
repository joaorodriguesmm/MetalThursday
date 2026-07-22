<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa uma ordenação disponível na listagem de MetalThursdays.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
enum OrdenacaoMetalThursday: string
{
    /**
     * Ordenação pela data da MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Data = 'data';

    /**
     * Ordenação pela classificação média.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case Classificacao = 'classificacao';

    /**
     * Ordenação pela classificação atribuída pelo utilizador autenticado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    case MinhaClassificacao = 'minha_classificacao';

    /**
     * Tenta criar uma ordenação a partir de um valor recebido.
     *
     * Os valores ingleses e os valores portugueses anteriormente utilizados
     * são temporariamente aceites para garantir compatibilidade com a versão
     * 1.0.0.
     *
     * @param  mixed  $valor  - Valor a converter.
     * @return self|null - Ordenação correspondente ou null quando o valor não
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
            self::Data->value,
            'date' => self::Data,

            self::Classificacao->value,
            'avaliacao',
            'rating' => self::Classificacao,

            self::MinhaClassificacao->value,
            'minha_avaliacao',
            'my_rating' => self::MinhaClassificacao,

            default => null,
        };
    }
}
