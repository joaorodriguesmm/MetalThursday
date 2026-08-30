<?php

declare(strict_types=1);

namespace App\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Notifications\NotificacaoMetalThursdayCriada;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use LogicException;

/**
 * Processa a notificação de publicação de uma MetalThursday.
 *
 * A decisão é novamente validada dentro de uma transação com bloqueio
 * pessimista, garantindo que execuções concorrentes não despacham a mesma
 * publicação mais do que uma vez.
 *
 * @since 2.0.0
 */
final class ServicoNotificacaoPublicacaoMetalThursday
{
    /**
     * Número de utilizadores processados por bloco.
     *
     * @since 2.0.0
     */
    private const UTILIZADORES_POR_BLOCO = 100;

    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @since 2.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Processa uma MetalThursday caso já esteja publicada e ainda não tenha
     * a respetiva notificação marcada como processada.
     *
     * A notificação utiliza `afterCommit`, pelo que os trabalhos destinados à
     * fila apenas são despachados depois do commit da transação. Se a operação
     * falhar antes desse momento, o marcador permanece nulo e uma execução
     * posterior poderá tentar novamente.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday a processar.
     * @return bool Verdadeiro quando a publicação foi processada nesta chamada.
     *
     * @throws InvalidArgumentException Quando o modelo não possui um
     *                                  identificador persistido válido.
     * @throws LogicException Quando não é possível persistir o marcador.
     *
     * @since 2.0.0
     */
    public function processar(
        MetalThursday $metalThursday,
    ): bool {
        $identificador =
            $this->obterIdentificador(
                $metalThursday,
            );

        return DB::transaction(
            function () use (
                $identificador,
            ): bool {
                $publicacao =
                    MetalThursday::query()
                        ->publicadasPorNotificar()
                        ->whereKey(
                            $identificador,
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $publicacao instanceof MetalThursday) {
                    return false;
                }

                $publicacao->loadMissing([
                    'edicao:id,nome',
                    'autor:id,nome',
                    'criadoPor:id,nome',
                ]);

                $notificacao =
                    new NotificacaoMetalThursdayCriada(
                        $publicacao,
                    );

                $this->notificarDestinatarios(
                    $publicacao,
                    $notificacao,
                );

                $registosAtualizados =
                    DB::table(
                        $publicacao->getTable(),
                    )
                        ->where(
                            'id',
                            $identificador,
                        )
                        ->whereNull(
                            MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM,
                        )
                        ->update([
                            MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM => now(),
                        ]);

                if ($registosAtualizados !== 1) {
                    throw new LogicException(
                        'Não foi possível marcar a publicação como notificada.',
                    );
                }

                return true;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Despacha a notificação geral para os utilizadores elegíveis.
     *
     * O criador da MetalThursday é excluído porque já conhece a publicação. O
     * próximo nomeado também é excluído porque recebe a notificação específica
     * de nomeação através do fluxo operacional correspondente.
     *
     * @param  MetalThursday  $metalThursday  Publicação processada.
     * @param  NotificacaoMetalThursdayCriada  $notificacao  Notificação.
     *
     * @since 2.0.0
     */
    private function notificarDestinatarios(
        MetalThursday $metalThursday,
        NotificacaoMetalThursdayCriada $notificacao,
    ): void {
        $construtor =
            Utilizador::query()
                ->with([
                    'permissoesEmail',
                ])
                ->selecionaveis();

        $identificadorCriador =
            $metalThursday->criado_por_id;

        if (
            is_numeric(
                $identificadorCriador,
            )
            && (int) $identificadorCriador > 0
        ) {
            $construtor->where(
                'utilizadores.id',
                '!=',
                (int) $identificadorCriador,
            );
        }

        $identificadorNomeado =
            $metalThursday->proximo_nomeado_id;

        if (
            is_numeric(
                $identificadorNomeado,
            )
            && (int) $identificadorNomeado > 0
        ) {
            $construtor->where(
                'utilizadores.id',
                '!=',
                (int) $identificadorNomeado,
            );
        }

        $construtor
            ->reorder(
                'utilizadores.id',
            )
            ->chunkById(
                self::UTILIZADORES_POR_BLOCO,
                static function (
                    Collection $destinatarios,
                ) use (
                    $notificacao,
                ): void {
                    Notification::send(
                        $destinatarios,
                        $notificacao,
                    );
                },
                'utilizadores.id',
                'id',
            );
    }

    /**
     * Obtém o identificador persistido da MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  Modelo recebido.
     * @return int Identificador validado.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido ou
     *                                  não possui um identificador válido.
     *
     * @since 2.0.0
     */
    private function obterIdentificador(
        MetalThursday $metalThursday,
    ): int {
        if (! $metalThursday->exists) {
            throw new InvalidArgumentException(
                'A MetalThursday a processar deve estar persistida.',
            );
        }

        $identificador =
            $metalThursday->getKey();

        if (
            is_int(
                $identificador,
            )
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (
            is_string(
                $identificador,
            )
            && ctype_digit(
                $identificador,
            )
            && (int) $identificador > 0
        ) {
            return (int) $identificador;
        }

        throw new InvalidArgumentException(
            'A MetalThursday a processar deve possuir um identificador válido.',
        );
    }
}
