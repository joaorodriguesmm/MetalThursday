<?php

declare(strict_types=1);

namespace Tests\Feature\Factories\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os estados personalizados da factory das reservas de MetalThursday.
 *
 * @since 2.0.0
 */
final class ReservaMetalThursdayFactoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma os estados personalizados de uma reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_reserva_com_estados_personalizados(): void
    {
        $data = CarbonImmutable::parse(
            '2026-08-27',
        );

        $responsavel = Utilizador::factory()
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                $data,
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        self::assertSame(
            '2026-08-27',
            $reserva->data->toDateString(),
        );

        self::assertSame(
            $responsavel->getKey(),
            $reserva->responsavel_id,
        );

        self::assertNull(
            $reserva->metal_thursday_id,
        );
    }

    /**
     * Confirma que uma reserva pode ser criada sem responsável.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_reserva_sem_responsavel(): void
    {
        $reserva = ReservaMetalThursday::factory()
            ->semResponsavel()
            ->create();

        self::assertNull(
            $reserva->responsavel_id,
        );
    }

    /**
     * Confirma a associação de uma MetalThursday persistida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function associa_metal_thursday_persistida(): void
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

        self::assertSame(
            $metalThursday->getKey(),
            $reserva->metal_thursday_id,
        );

        self::assertSame(
            '2026-08-27',
            $reserva->data->toDateString(),
        );

        self::assertTrue(
            $reserva->estaCumprida(),
        );
    }

    /**
     * Confirma que a factory rejeita um responsável não persistido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_responsavel_nao_persistido(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        ReservaMetalThursday::factory()
            ->comResponsavel(
                new Utilizador,
            );
    }

    /**
     * Confirma que a factory rejeita uma MetalThursday não persistida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_metal_thursday_nao_persistida(): void
    {
        $this->expectException(
            InvalidArgumentException::class,
        );

        ReservaMetalThursday::factory()
            ->comMetalThursday(
                new MetalThursday,
            );
    }

    /**
     * Confirma que a factory rejeita uma data fora de quinta-feira.
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
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-28',
                ),
            );
    }
}
