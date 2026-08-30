<?php

declare(strict_types=1);

namespace App\Servicos\Agendamentos;

use App\Models\MetalThursday\MetalThursday;
use App\Servicos\MetalThursday\ServicoNotificacaoPublicacaoMetalThursday;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

/**
 * Processa as notificações de MetalThursdays cuja data de publicação já
 * chegou.
 *
 * A classe limita-se a orquestrar a procura das publicações pendentes. A
 * decisão final e a proteção contra processamento duplicado permanecem no
 * serviço específico de notificação.
 *
 * @since 2.0.0
 */
final class ExecutarPublicacoesMetalThursday
{
    /**
     * Número de publicações obtidas por bloco.
     *
     * @since 2.0.0
     */
    private const PUBLICACOES_POR_BLOCO = 100;

    /**
     * Processa todas as publicações atualmente elegíveis.
     *
     * Cada MetalThursday é processada isoladamente. Uma falha permanece
     * reportada e deixa essa publicação pendente para uma execução posterior,
     * sem impedir o processamento das restantes.
     *
     * @param  ServicoNotificacaoPublicacaoMetalThursday  $servicoNotificacao
     *                                                                         Serviço
     *                                                                         responsável
     *                                                                         por cada
     *                                                                         publicação.
     *
     * @since 2.0.0
     */
    public function __invoke(
        ServicoNotificacaoPublicacaoMetalThursday $servicoNotificacao,
    ): void {
        MetalThursday::query()
            ->publicadasPorNotificar()
            ->select([
                'id',
            ])
            ->chunkById(
                self::PUBLICACOES_POR_BLOCO,
                static function (
                    Collection $publicacoes,
                ) use (
                    $servicoNotificacao,
                ): void {
                    foreach ($publicacoes as $publicacao) {
                        if (! $publicacao instanceof MetalThursday) {
                            continue;
                        }

                        try {
                            $servicoNotificacao->processar(
                                $publicacao,
                            );
                        } catch (Throwable $excecao) {
                            report(
                                $excecao,
                            );
                        }
                    }
                },
                'metal_thursdays.id',
                'id',
            );
    }
}
