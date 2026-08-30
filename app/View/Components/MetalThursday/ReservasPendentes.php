<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\RascunhoMetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara as MetalThursdays que ainda exigem acompanhamento operacional.
 *
 * São agregadas as reservas ainda por preparar ou em rascunho e as
 * MetalThursdays já finalizadas cuja data de publicação ainda não chegou.
 * Conteúdo de uma MetalThursday preparada não é exposto pelo componente.
 *
 * As reservas pendentes permanecem visíveis como informação operacional. Uma
 * MetalThursday preparada apenas é incluída quando o utilizador autenticado
 * possui permissão para a alterar.
 *
 * @since 2.0.0
 */
final class ReservasPendentes extends Component
{
    /**
     * Itens preparados para apresentação.
     *
     * @var array<int, array{
     *     idElemento: string,
     *     dataOrdenacao: string,
     *     data: string,
     *     nomeResponsavel: string,
     *     estado: string,
     *     emAtraso: bool,
     *     rotaAcao: string|null,
     *     textoAcao: string|null
     * }>
     *
     * @since 2.0.0
     */
    public readonly array $itensPorPublicar;

    /**
     * Cria uma nova instância do componente.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado válido.
     * @throws LogicException Quando um registo possui dados persistidos
     *                        inválidos.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $utilizador =
            Auth::guard(
                'sessao',
            )->user();

        if (! $utilizador instanceof Utilizador) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
            );
        }

        $hoje =
            CarbonImmutable::today(
                config(
                    'app.timezone',
                ),
            );

        $itensPorPublicar =
            $this->obterReservasPendentes(
                $utilizador,
                $hoje,
            );

        array_push(
            $itensPorPublicar,
            ...$this->obterMetalThursdaysPreparadas(
                $utilizador,
            ),
        );

        usort(
            $itensPorPublicar,
            static function (
                array $primeiro,
                array $segundo,
            ): int {
                $comparacaoData =
                    $primeiro['dataOrdenacao']
                    <=> $segundo['dataOrdenacao'];

                if ($comparacaoData !== 0) {
                    return $comparacaoData;
                }

                return $primeiro['idElemento']
                    <=> $segundo['idElemento'];
            },
        );

        $this->itensPorPublicar =
            $itensPorPublicar;
    }

    /**
     * Obtém as reservas que ainda não possuem uma MetalThursday definitiva.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  CarbonInterface  $hoje  Data local atual.
     * @return array<int, array{
     *     idElemento: string,
     *     dataOrdenacao: string,
     *     data: string,
     *     nomeResponsavel: string,
     *     estado: string,
     *     emAtraso: bool,
     *     rotaAcao: string|null,
     *     textoAcao: string|null
     * }> Reservas preparadas para apresentação.
     *
     * @throws LogicException Quando uma reserva possui dados inválidos.
     *
     * @since 2.0.0
     */
    private function obterReservasPendentes(
        Utilizador $utilizador,
        CarbonInterface $hoje,
    ): array {
        $reservas =
            ReservaMetalThursday::query()
                ->select([
                    'id',
                    'data',
                    'responsavel_id',
                ])
                ->whereNull(
                    'metal_thursday_id',
                )
                ->with([
                    'responsavel:id,nome',

                    'rascunho:id,reserva_metal_thursday_id',
                ])
                ->orderBy(
                    'data',
                )
                ->orderBy(
                    'id',
                )
                ->get();

        $itens =
            [];

        foreach ($reservas as $reserva) {
            $identificadorReserva =
                $reserva->getKey();

            $data =
                $reserva->data;

            if (
                ! is_numeric(
                    $identificadorReserva,
                )
                || (int) $identificadorReserva < 1
                || ! $data instanceof CarbonInterface
            ) {
                throw new LogicException(
                    'A reserva de MetalThursday possui dados persistidos inválidos.',
                );
            }

            $temRascunho =
                $reserva->rascunho
                instanceof RascunhoMetalThursday;

            $podePreparar =
                is_numeric(
                    $reserva->responsavel_id,
                )
                && (int) $reserva->responsavel_id
                === (int) $utilizador->getKey();

            $itens[] = [
                'idElemento' => 'reserva-metal-thursday-'.
                    (int) $identificadorReserva,

                'dataOrdenacao' => $data->format(
                    'Y-m-d',
                ),

                'data' => $data->format(
                    'd/m/Y',
                ),

                'nomeResponsavel' => $this->obterNomeUtilizador(
                    $reserva->responsavel,
                ),

                'estado' => $temRascunho
                    ? 'Rascunho'
                    : 'Por preparar',

                'emAtraso' => $data->isBefore(
                    $hoje,
                ),

                'rotaAcao' => $podePreparar
                    ? route(
                        'metal-thursday.reservas.preparar',
                        $reserva,
                    )
                    : null,

                'textoAcao' => $podePreparar
                    ? (
                        $temRascunho
                        ? 'Continuar preparação'
                        : 'Preparar MetalThursday'
                    )
                    : null,
            ];
        }

        return $itens;
    }

    /**
     * Obtém as MetalThursdays finalizadas cuja publicação ainda não ocorreu.
     *
     * Apenas são devolvidos registos que o utilizador autenticado pode alterar
     * segundo a política da MetalThursday. O componente não expõe nome,
     * secções ou qualquer outro conteúdo preparado.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @return array<int, array{
     *     idElemento: string,
     *     dataOrdenacao: string,
     *     data: string,
     *     nomeResponsavel: string,
     *     estado: string,
     *     emAtraso: bool,
     *     rotaAcao: string|null,
     *     textoAcao: string|null
     * }> MetalThursdays preparadas para apresentação.
     *
     * @throws LogicException Quando uma MetalThursday possui dados inválidos.
     *
     * @since 2.0.0
     */
    private function obterMetalThursdaysPreparadas(
        Utilizador $utilizador,
    ): array {
        $metalThursdays =
            MetalThursday::query()
                ->select([
                    'id',
                    'data',
                    'autor_id',
                    'criado_por_id',
                ])
                ->preparadasPorPublicar()
                ->with([
                    'autor:id,nome',
                ])
                ->orderBy(
                    'data',
                )
                ->orderBy(
                    'id',
                )
                ->get();

        $itens =
            [];

        foreach ($metalThursdays as $metalThursday) {
            if (
                ! Gate::forUser(
                    $utilizador,
                )->allows(
                    'update',
                    $metalThursday,
                )
            ) {
                continue;
            }

            $identificadorMetalThursday =
                $metalThursday->getKey();

            $data =
                $metalThursday->data;

            if (
                ! is_numeric(
                    $identificadorMetalThursday,
                )
                || (int) $identificadorMetalThursday < 1
                || ! $data instanceof CarbonInterface
            ) {
                throw new LogicException(
                    'A MetalThursday preparada possui dados persistidos inválidos.',
                );
            }

            $itens[] = [
                'idElemento' => 'metal-thursday-preparada-'.
                    (int) $identificadorMetalThursday,

                'dataOrdenacao' => $data->format(
                    'Y-m-d',
                ),

                'data' => $data->format(
                    'd/m/Y',
                ),

                'nomeResponsavel' => $this->obterNomeUtilizador(
                    $metalThursday->autor,
                ),

                'estado' => 'Preparada',

                'emAtraso' => false,

                'rotaAcao' => route(
                    'metal-thursday.editar',
                    $metalThursday,
                ),

                'textoAcao' => 'Editar preparação',
            ];
        }

        return $itens;
    }

    /**
     * Obtém o nome apresentado para um utilizador relacionado.
     *
     * @param  Utilizador|null  $utilizador  Utilizador relacionado.
     * @return string Nome do utilizador ou indicação de ausência.
     *
     * @since 2.0.0
     */
    private function obterNomeUtilizador(
        ?Utilizador $utilizador,
    ): string {
        if (! $utilizador instanceof Utilizador) {
            return 'Por atribuir';
        }

        $nome =
            trim(
                $utilizador->nome,
            );

        return $nome !== ''
            ? $nome
            : 'Por atribuir';
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista das MetalThursdays por publicar.
     *
     * @since 2.0.0
     */
    public function render(): View
    {
        return view(
            'components.metal-thursday.reservas-pendentes',
        );
    }
}
