<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use LogicException;

/**
 * Pagina as consultas da listagem sem repetir a contagem em páginas válidas.
 *
 * @since 2.0.0
 */
final class ServicoPaginacaoListagemMetalThursday
{
    /** Atributo técnico usado para transportar o total na consulta paginada. */
    private const ALIAS_TOTAL = 'total_resultados_paginacao';

    /**
     * Pagina uma consulta através de uma função de janela.
     *
     * Quando a página pedida não contém registos, executa a contagem
     * convencional como fallback para preservar o total exato.
     *
     * @template TModel of Model
     *
     * @param  Builder<covariant TModel>  $consulta  Consulta a paginar.
     * @param  int  $porPagina  Número de registos por página.
     * @param  Request  $pedido  Pedido HTTP atual.
     * @param  string  $nomePagina  Nome do parâmetro da página.
     * @return LengthAwarePaginator<int, TModel> Paginador preparado.
     *
     * @since 2.0.0
     */
    public function paginar(
        Builder $consulta,
        int $porPagina,
        Request $pedido,
        string $nomePagina = 'page',
    ): LengthAwarePaginator {
        $pagina = Paginator::resolveCurrentPage(
            $nomePagina,
        );

        $consultaContagem = clone $consulta;

        if ($consulta->getQuery()->columns === null) {
            $consulta->select(
                $consulta
                    ->getModel()
                    ->qualifyColumn('*'),
            );
        }

        $consulta->selectRaw(
            'COUNT(*) OVER() AS '.self::ALIAS_TOTAL,
        );

        $registos =
            $consulta
                ->forPage(
                    $pagina,
                    $porPagina,
                )
                ->get();

        $primeiroRegisto =
            $registos->first();

        $total = $primeiroRegisto === null
            ? $consultaContagem
                ->toBase()
                ->getCountForPagination()
            : $this->obterTotal(
                $primeiroRegisto,
            );

        foreach ($registos as $registo) {
            $registo->offsetUnset(
                self::ALIAS_TOTAL,
            );
        }

        $paginador =
            new LengthAwarePaginator(
                $registos,
                $total,
                $porPagina,
                $pagina,
                [
                    'path' => $pedido->url(),
                    'pageName' => $nomePagina,
                ],
            );

        return $paginador->appends(
            $pedido->query(),
        );
    }

    /**
     * Obtém o total transportado pela função de janela.
     *
     * @param  Model  $modelo  Modelo da primeira linha da página.
     * @return int Total de resultados.
     *
     * @since 2.0.0
     */
    private function obterTotal(
        Model $modelo,
    ): int {
        $valor =
            $modelo->getAttributes()[
                self::ALIAS_TOTAL
            ] ?? null;

        if (! is_numeric($valor)) {
            throw new LogicException(
                'A consulta paginada não devolveu um total válido.',
            );
        }

        return (int) $valor;
    }
}
