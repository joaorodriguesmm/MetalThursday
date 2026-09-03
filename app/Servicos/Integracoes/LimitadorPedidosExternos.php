<?php

declare(strict_types=1);

namespace App\Servicos\Integracoes;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;

/**
 * Coordena o intervalo mínimo entre pedidos a serviços externos.
 *
 * O instante do último pedido é partilhado através da cache da aplicação e o
 * acesso é protegido por um bloqueio distribuído. Desta forma, pedidos
 * executados por processos ou utilizadores diferentes respeitam o mesmo
 * intervalo mínimo configurado para cada fornecedor.
 *
 * @since 2.0.0
 */
final class LimitadorPedidosExternos
{
    /**
     * Duração máxima do bloqueio distribuído, em segundos.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const DURACAO_BLOQUEIO_SEGUNDOS =
        15;

    /**
     * Tempo máximo de espera pela aquisição do bloqueio, em segundos.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const ESPERA_BLOQUEIO_SEGUNDOS =
        15;

    /**
     * Tempo durante o qual é conservado o instante do último pedido.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const RETENCAO_ULTIMO_PEDIDO_SEGUNDOS =
        300;

    /**
     * Aguarda até ser permitido iniciar um novo pedido ao fornecedor indicado.
     *
     * Um intervalo igual a zero desativa o controlo, o que é útil em testes
     * automatizados que utilizam respostas HTTP simuladas.
     *
     * @param  string  $fornecedor  Identificador interno do fornecedor.
     * @param  int  $intervaloMilissegundos  Intervalo mínimo entre inícios de
     *                                       pedidos.
     *
     * @throws InvalidArgumentException Quando os parâmetros são inválidos.
     * @throws RuntimeException Quando não é possível adquirir o bloqueio
     *                          distribuído dentro do tempo permitido.
     *
     * @since 2.0.0
     */
    public function aguardar(
        string $fornecedor,
        int $intervaloMilissegundos,
    ): void {
        $fornecedor =
            trim(
                $fornecedor,
            );

        if ($fornecedor === '') {
            throw new InvalidArgumentException(
                'O fornecedor do limite de pedidos externos é obrigatório.',
            );
        }

        if ($intervaloMilissegundos < 0) {
            throw new InvalidArgumentException(
                'O intervalo mínimo entre pedidos externos não pode ser negativo.',
            );
        }

        if ($intervaloMilissegundos === 0) {
            return;
        }

        $chaveBase =
            'integracoes:pedidos:'
            .mb_strtolower(
                $fornecedor,
            );

        $chaveBloqueio =
            $chaveBase
            .':bloqueio';

        $chaveUltimoPedido =
            $chaveBase
            .':ultimo-inicio';

        try {
            Cache::lock(
                $chaveBloqueio,
                self::DURACAO_BLOQUEIO_SEGUNDOS,
            )->block(
                self::ESPERA_BLOQUEIO_SEGUNDOS,
                function () use (
                    $chaveUltimoPedido,
                    $intervaloMilissegundos,
                ): void {
                    $agora =
                        microtime(
                            true,
                        );

                    $ultimoPedido =
                        Cache::get(
                            $chaveUltimoPedido,
                        );

                    if (is_numeric($ultimoPedido)) {
                        $milissegundosDecorridos =
                            (
                                $agora
                                - (float) $ultimoPedido
                            )
                            * 1000;

                        $milissegundosRestantes =
                            $intervaloMilissegundos
                            - $milissegundosDecorridos;

                        if ($milissegundosRestantes > 0) {
                            usleep(
                                (int) ceil(
                                    $milissegundosRestantes
                                        * 1000,
                                ),
                            );
                        }
                    }

                    Cache::put(
                        $chaveUltimoPedido,
                        microtime(
                            true,
                        ),
                        self::RETENCAO_ULTIMO_PEDIDO_SEGUNDOS,
                    );
                },
            );
        } catch (LockTimeoutException $excecao) {
            throw new RuntimeException(
                'Não foi possível coordenar o limite de pedidos ao serviço externo.',
                previous: $excecao,
            );
        }
    }
}
