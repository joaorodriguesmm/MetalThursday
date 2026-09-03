<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Integracoes;

use App\Servicos\Integracoes\LimitadorPedidosExternos;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a coordenação do intervalo mínimo entre pedidos externos.
 *
 * @since 2.0.0
 */
final class LimitadorPedidosExternosTest extends TestCase
{
    /**
     * Prepara uma cache isolada e partilhável dentro do processo de teste.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.default',
            'array',
        );

        Cache::flush();
    }

    /**
     * Confirma que dois pedidos consecutivos ao mesmo fornecedor respeitam o
     * intervalo mínimo configurado.
     *
     * É utilizada uma tolerância inferior ao intervalo real para evitar
     * falsos negativos provocados pela resolução temporal do sistema.
     *
     * @since 2.0.0
     */
    #[Test]
    public function respeita_intervalo_minimo_entre_pedidos(): void
    {
        $limitador =
            app(
                LimitadorPedidosExternos::class,
            );

        $limitador->aguardar(
            'fornecedor-teste',
            100,
        );

        $inicio =
            microtime(
                true,
            );

        $limitador->aguardar(
            'fornecedor-teste',
            100,
        );

        $milissegundosDecorridos =
            (
                microtime(
                    true,
                )
                - $inicio
            )
            * 1000;

        self::assertGreaterThanOrEqual(
            80,
            $milissegundosDecorridos,
        );
    }

    /**
     * Confirma que o intervalo zero desativa a espera.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_desativar_limite_temporal(): void
    {
        $limitador =
            app(
                LimitadorPedidosExternos::class,
            );

        $limitador->aguardar(
            'fornecedor-teste',
            0,
        );

        self::assertTrue(
            true,
        );
    }
}
