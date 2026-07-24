<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Models\MetalThursday\Edicao;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Valida os dados necessários para criar uma edição.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class CriarEdicaoRequest extends PedidoEdicaoRequest
{
    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * Edições eliminadas logicamente não impedem a reutilização do respetivo
     * nome.
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
            Edicao::class,
            'nome',
        )->whereNull(
            'deleted_at',
        );
    }
}
