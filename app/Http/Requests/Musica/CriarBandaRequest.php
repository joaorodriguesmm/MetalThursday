<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Musica\Banda;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Valida os dados necessários para criar uma banda.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class CriarBandaRequest extends PedidoBandaRequest
{
    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * As bandas eliminadas logicamente não impedem a reutilização do
     * respetivo nome.
     *
     * @return Unique Regra de unicidade.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function obterRegraUnicidadeNome(): Unique
    {
        return Rule::unique(
            Banda::class,
            'nome',
        )->whereNull(
            'deleted_at',
        );
    }
}
