<?php

declare(strict_types=1);

namespace App\Servicos\Notificacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Notifications\NotificacaoLembreteAtrasoMetalThursday;
use App\Notifications\NotificacaoLembreteTarefaMetalThursday;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Notifications\Dispatcher as DespachanteNotificacoes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as ColecaoEloquent;
use Illuminate\Database\Query\JoinClause;

/**
 * Envia os lembretes associados às tarefas pendentes de MetalThursday.
 *
 * O serviço seleciona exclusivamente reservas ainda não cumpridas e
 * destinatários com acesso ativo. As permissões de e-mail são carregadas antes
 * do envio para que a determinação dos canais não provoque consultas
 * adicionais.
 *
 * @since 2.0.0
 */
final class NotificadorLembretesMetalThursday
{
    /**
     * Quantidade máxima de reservas processadas em cada lote.
     *
     * @since 2.0.0
     */
    private const TAMANHO_LOTE =
        200;

    /**
     * Cria o serviço de lembretes.
     *
     * @param  DespachanteNotificacoes  $notificacoes  Serviço responsável
     *                                                 pelo envio das
     *                                                 notificações.
     *
     * @since 2.0.0
     */
    public function __construct(
        private readonly DespachanteNotificacoes $notificacoes,
    ) {}

    /**
     * Notifica o responsável quando existe uma MetalThursday pendente cuja
     * data corresponde ao dia de referência.
     *
     * Reservas já cumpridas, reservas sem responsável e responsáveis com o
     * acesso suspenso são ignorados.
     *
     * @param  CarbonInterface|null  $referencia  Momento utilizado para
     *                                            determinar o dia atual.
     *
     * @since 2.0.0
     */
    public function notificarTarefasDoDia(
        ?CarbonInterface $referencia = null,
    ): void {
        $data =
            $this->obterDataReferencia(
                $referencia,
            );

        $reserva = ReservaMetalThursday::query()
            ->with([
                'responsavel.permissoesEmail',
            ])
            ->where(
                'data',
                $data->toDateString(),
            )
            ->whereNull(
                'metal_thursday_id',
            )
            ->whereHas(
                'responsavel',
                static fn (
                    Builder $construtor,
                ): Builder => $construtor->comAcessoAtivo(),
            )
            ->first();

        if (! $reserva instanceof ReservaMetalThursday) {
            return;
        }

        $responsavel =
            $reserva->responsavel;

        if (! $responsavel instanceof Utilizador) {
            return;
        }

        $this
            ->notificacoes
            ->send(
                $responsavel,
                new NotificacaoLembreteTarefaMetalThursday(
                    $reserva,
                ),
            );
    }

    /**
     * Notifica diariamente os responsáveis com reservas pendentes anteriores ao
     * dia de referência.
     *
     * É enviada no máximo uma notificação por responsável em cada execução,
     * correspondente à respetiva reserva pendente em atraso mais antiga.
     *
     * Reservas cumpridas, reservas sem responsável e responsáveis sem acesso
     * ativo são excluídos.
     *
     * @param  CarbonInterface|null  $referencia  Momento utilizado para
     *                                            determinar o dia atual.
     *
     * @since 2.0.0
     */
    public function notificarAtrasos(
        ?CarbonInterface $referencia = null,
    ): void {
        $data =
            $this->obterDataReferencia(
                $referencia,
            );

        $reservasMaisAntigas = ReservaMetalThursday::query()
            ->selectRaw(
                'responsavel_id, MIN(data) AS data_mais_antiga',
            )
            ->whereNotNull(
                'responsavel_id',
            )
            ->whereNull(
                'metal_thursday_id',
            )
            ->where(
                'data',
                '<',
                $data->toDateString(),
            )
            ->groupBy(
                'responsavel_id',
            );

        ReservaMetalThursday::query()
            ->joinSub(
                $reservasMaisAntigas,
                'reservas_atrasadas_mais_antigas',
                static function (
                    JoinClause $juncao,
                ): void {
                    $juncao
                        ->on(
                            'reservas_metal_thursday.responsavel_id',
                            '=',
                            'reservas_atrasadas_mais_antigas.responsavel_id',
                        )
                        ->on(
                            'reservas_metal_thursday.data',
                            '=',
                            'reservas_atrasadas_mais_antigas.data_mais_antiga',
                        );
                },
            )
            ->with([
                'responsavel.permissoesEmail',
            ])
            ->whereHas(
                'responsavel',
                static fn (
                    Builder $construtor,
                ): Builder => $construtor->comAcessoAtivo(),
            )
            ->select([
                'reservas_metal_thursday.*',
            ])
            ->reorder(
                'reservas_metal_thursday.id',
            )
            ->chunkById(
                self::TAMANHO_LOTE,
                function (
                    ColecaoEloquent $reservas,
                ) use (
                    $data,
                ): void {
                    foreach ($reservas as $reserva) {
                        if (! $reserva instanceof ReservaMetalThursday) {
                            continue;
                        }

                        $responsavel =
                            $reserva->responsavel;

                        if (! $responsavel instanceof Utilizador) {
                            continue;
                        }

                        $this
                            ->notificacoes
                            ->send(
                                $responsavel,
                                new NotificacaoLembreteAtrasoMetalThursday(
                                    $reserva,
                                    $data,
                                ),
                            );
                    }
                },
                'reservas_metal_thursday.id',
                'id',
            );
    }

    /**
     * Obtém a data civil da aplicação correspondente ao momento de referência.
     *
     * A conversão explícita para o fuso horário configurado evita que uma
     * referência recebida noutro fuso determine o dia incorreto junto à
     * mudança de data.
     *
     * @param  CarbonInterface|null  $referencia  Momento recebido.
     * @return CarbonImmutable Data normalizada.
     *
     * @since 2.0.0
     */
    private function obterDataReferencia(
        ?CarbonInterface $referencia,
    ): CarbonImmutable {
        return CarbonImmutable::instance(
            $referencia
                ?? now(),
        )
            ->setTimezone(
                (string) config(
                    'app.timezone',
                ),
            )
            ->startOfDay();
    }
}
