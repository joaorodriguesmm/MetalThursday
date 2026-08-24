<?php

declare(strict_types=1);

namespace App\View\Components\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use LogicException;

/**
 * Prepara as reservas pendentes apresentadas na listagem de MetalThursdays.
 *
 * As reservas são apresentadas fora da paginação das MetalThursdays já
 * publicadas e ordenadas cronologicamente. Apenas o responsável da reserva
 * recebe a ação para preparar a respetiva MetalThursday.
 *
 * @since 2.0.0
 */
final class ReservasPendentes extends Component
{
    /**
     * Reservas preparadas para apresentação.
     *
     * @var array<int, array{
     *     identificador: int,
     *     data: string,
     *     nomeResponsavel: string,
     *     emAtraso: bool,
     *     podePreparar: bool
     * }>
     *
     * @since 2.0.0
     */
    public readonly array $reservasPreparadas;

    /**
     * Cria uma nova instância do componente.
     *
     * @throws AuthenticationException Quando não existe um utilizador
     *                                 autenticado válido.
     * @throws LogicException Quando uma reserva possui dados persistidos
     *                        inválidos.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        $identificadorUtilizador =
            Auth::guard(
                'sessao',
            )->id();

        if (
            ! is_numeric(
                $identificadorUtilizador,
            )
            || (int) $identificadorUtilizador < 1
        ) {
            throw new AuthenticationException(
                'Não foi possível identificar o utilizador autenticado.',
            );
        }

        $hoje = CarbonImmutable::today();

        $reservas = ReservaMetalThursday::query()
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
            ])
            ->orderBy(
                'data',
            )
            ->orderBy(
                'id',
            )
            ->get();

        $reservasPreparadas = [];

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

            $reservasPreparadas[] = [
                'identificador' => (int) $identificadorReserva,

                'data' => $data->format(
                    'd/m/Y',
                ),

                'nomeResponsavel' => $this->obterNomeResponsavel(
                    $reserva,
                ),

                'emAtraso' => $data->isBefore(
                    $hoje,
                ),

                'podePreparar' => is_numeric(
                    $reserva->responsavel_id,
                )
                    && (int) $reserva->responsavel_id
                    === (int) $identificadorUtilizador,
            ];
        }

        $this->reservasPreparadas =
            $reservasPreparadas;
    }

    /**
     * Obtém o nome apresentado para o responsável da reserva.
     *
     * @param  ReservaMetalThursday  $reserva  Reserva apresentada.
     * @return string Nome do responsável ou indicação de ausência.
     *
     * @since 2.0.0
     */
    private function obterNomeResponsavel(
        ReservaMetalThursday $reserva,
    ): string {
        $responsavel =
            $reserva->responsavel;

        if (! $responsavel instanceof Utilizador) {
            return 'Por atribuir';
        }

        $nome = trim(
            $responsavel->nome,
        );

        return $nome !== ''
            ? $nome
            : 'Por atribuir';
    }

    /**
     * Obtém a vista do componente.
     *
     * @return View Vista das reservas pendentes.
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
