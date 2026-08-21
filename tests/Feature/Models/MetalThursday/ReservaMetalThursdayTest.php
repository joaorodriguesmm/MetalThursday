<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os contratos do modelo das reservas de MetalThursday.
 *
 * @since 2.0.0
 */
final class ReservaMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os estados pendente e cumprido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function distingue_reserva_pendente_e_cumprida(): void
    {
        $reserva = ReservaMetalThursday::factory()
            ->create();

        self::assertTrue(
            $reserva->estaPendente(),
        );

        self::assertFalse(
            $reserva->estaCumprida(),
        );

        $data = CarbonImmutable::parse(
            '2026-08-27',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                $data,
            )
            ->comEdicao(
                $edicao,
            )
            ->create();

        $reserva->data =
            $data;

        $reserva
            ->metalThursday()
            ->associate(
                $metalThursday,
            );

        $reserva->saveOrFail();

        self::assertFalse(
            $reserva->estaPendente(),
        );

        self::assertTrue(
            $reserva->estaCumprida(),
        );
    }

    /**
     * Confirma que uma reserva só pode representar uma quinta-feira.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_data_que_nao_e_quinta_feira(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        ReservaMetalThursday::factory()
            ->make([
                'data' => '2026-08-28',
            ])
            ->saveOrFail();
    }

    /**
     * Confirma que uma MetalThursday eliminada logicamente continua acessível
     * através do histórico da reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preserva_relacao_com_metal_thursday_eliminada_logicamente(): void
    {
        $data = CarbonImmutable::parse(
            '2026-08-27',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                $data,
            )
            ->comEdicao(
                $edicao,
            )
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comMetalThursday(
                $metalThursday,
            )
            ->create();

        $metalThursday->deleteOrFail();

        $reserva->unsetRelation(
            'metalThursday',
        );

        self::assertSame(
            $metalThursday->getKey(),
            $reserva->metalThursday?->getKey(),
        );

        self::assertTrue(
            $reserva->metalThursday?->trashed()
                ?? false,
        );
    }
}
