<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Musica\Genero;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Valida os dados necessários para criar um género musical.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class CriarGeneroRequest extends PedidoGeneroRequest
{
    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * Os géneros eliminados logicamente não impedem a reutilização do
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
            Genero::class,
            'nome',
        )->whereNull(
            'deleted_at',
        );
    }
}
