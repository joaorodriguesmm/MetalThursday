<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa uma ordenação disponível na listagem de MetalThursdays.
 *
 * Os valores correspondem diretamente aos parâmetros públicos aceites pela
 * aplicação.
 *
 * @since 2.0.0
 */
enum OrdenacaoMetalThursday: string
{
    /**
     * Ordenação pela data da MetalThursday.
     *
     * @since 2.0.0
     */
    case Data = 'data';

    /**
     * Ordenação pela classificação média.
     *
     * @since 2.0.0
     */
    case Classificacao = 'classificacao';

    /**
     * Ordenação pela classificação atribuída pelo utilizador autenticado.
     *
     * @since 2.0.0
     */
    case MinhaClassificacao = 'minha_classificacao';

    /**
     * Tenta criar uma ordenação a partir de um valor textual.
     *
     * Apenas os valores públicos definidos pela própria enumeração são
     * aceites. A normalização limita-se à remoção de espaços exteriores e à
     * conversão para minúsculas.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return self|null Ordenação correspondente ou nula.
     *
     * @since 2.0.0
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
     * @return string Etiqueta da ordenação.
     *
     * @since 2.0.0
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Data => 'Data',
            self::Classificacao => 'Avaliação média',
            self::MinhaClassificacao => 'A minha avaliação',
        };
    }
}
