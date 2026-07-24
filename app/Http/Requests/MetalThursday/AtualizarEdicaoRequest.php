<?php

declare(strict_types=1);

namespace App\Http\Requests\MetalThursday;

use App\Models\MetalThursday\Edicao;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use LogicException;

/**
 * Valida os dados necessários para atualizar uma edição.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class AtualizarEdicaoRequest extends PedidoEdicaoRequest
{
    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * A edição atual é ignorada na verificação. Edições eliminadas
     * logicamente não impedem a reutilização do respetivo nome.
     *
     * @return Unique Regra de unicidade.
     *
     * @throws LogicException Quando a rota não contém uma edição válida.
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
        )
            ->ignore(
                $this->obterEdicaoDaRota(),
            )
            ->whereNull(
                'deleted_at',
            );
    }

    /**
     * Obtém a edição associada ao parâmetro da rota.
     *
     * @return Edicao Edição que será atualizada.
     *
     * @throws LogicException Quando a rota não contém uma edição válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterEdicaoDaRota(): Edicao
    {
        $edicao = $this->route(
            'edicao',
        );

        if (! $edicao instanceof Edicao) {
            throw new LogicException(
                'A rota não contém uma edição válida.',
            );
        }

        return $edicao;
    }
}
