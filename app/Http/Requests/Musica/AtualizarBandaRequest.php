<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Musica\Banda;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use LogicException;

/**
 * Valida os dados necessários para atualizar uma banda.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class AtualizarBandaRequest extends PedidoBandaRequest
{
    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * A banda atual é ignorada na verificação. As bandas eliminadas
     * logicamente não impedem a reutilização do respetivo nome.
     *
     * @return Unique Regra de unicidade.
     *
     * @throws LogicException Quando a rota não contém uma banda válida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function obterRegraUnicidadeNome(): Unique
    {
        return Rule::unique(
            Banda::class,
            'nome',
        )
            ->ignore(
                $this->obterBandaDaRota(),
            )
            ->whereNull(
                'deleted_at',
            );
    }

    /**
     * Obtém a banda associada ao parâmetro da rota.
     *
     * @return Banda Banda que será atualizada.
     *
     * @throws LogicException Quando a rota não contém uma banda válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterBandaDaRota(): Banda
    {
        $banda = $this->route(
            'banda',
        );

        if (! $banda instanceof Banda) {
            throw new LogicException(
                'A rota não contém uma banda válida.',
            );
        }

        return $banda;
    }
}
