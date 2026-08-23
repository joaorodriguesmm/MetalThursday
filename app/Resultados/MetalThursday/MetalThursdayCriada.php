<?php

declare(strict_types=1);

namespace App\Resultados\MetalThursday;

use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use InvalidArgumentException;

/**
 * Transporta o resultado da criação de uma MetalThursday.
 *
 * O resultado distingue a MetalThursday persistida da eventual reserva
 * seguinte criada na mesma transação. Quando o slot seguinte já existia, a
 * reserva devolvida é nula.
 *
 * @since 2.0.0
 */
final readonly class MetalThursdayCriada
{
    /**
     * Cria o resultado da operação.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday criada.
     * @param  ReservaMetalThursday|null  $reservaSeguinte  Reserva seguinte
     *                                                      criada nesta
     *                                                      operação.
     *
     * @throws InvalidArgumentException Quando algum modelo recebido não está
     *                                  persistido.
     *
     * @since 2.0.0
     */
    public function __construct(
        private MetalThursday $metalThursday,
        private ?ReservaMetalThursday $reservaSeguinte,
    ) {
        self::validarModeloPersistido(
            $metalThursday,
            'A MetalThursday deve estar persistida antes de criar o resultado.',
        );

        if ($reservaSeguinte instanceof ReservaMetalThursday) {
            self::validarModeloPersistido(
                $reservaSeguinte,
                'A reserva seguinte deve estar persistida antes de criar o resultado.',
            );
        }
    }

    /**
     * Obtém a MetalThursday criada.
     *
     * @return MetalThursday MetalThursday persistida.
     *
     * @since 2.0.0
     */
    public function obterMetalThursday(): MetalThursday
    {
        return $this->metalThursday;
    }

    /**
     * Obtém a reserva seguinte criada nesta operação.
     *
     * Um valor nulo significa que nenhum novo slot foi criado, nomeadamente
     * porque já existia uma reserva para essa data.
     *
     * @return ReservaMetalThursday|null Reserva criada ou nulo.
     *
     * @since 2.0.0
     */
    public function obterReservaSeguinte(): ?ReservaMetalThursday
    {
        return $this->reservaSeguinte;
    }

    /**
     * Confirma que um modelo recebido já se encontra persistido.
     *
     * @param  MetalThursday|ReservaMetalThursday  $modelo  Modelo recebido.
     * @param  string  $mensagem  Mensagem utilizada em caso de erro.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido.
     *
     * @since 2.0.0
     */
    private static function validarModeloPersistido(
        MetalThursday|ReservaMetalThursday $modelo,
        string $mensagem,
    ): void {
        if (
            $modelo->exists
            && $modelo->getKey() !== null
        ) {
            return;
        }

        throw new InvalidArgumentException(
            $mensagem,
        );
    }
}
