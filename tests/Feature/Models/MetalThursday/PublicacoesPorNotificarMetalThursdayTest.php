<?php

declare(strict_types=1);

namespace Tests\Feature\Models\MetalThursday;

use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a seleção das MetalThursdays publicadas ainda por notificar.
 *
 * @since 2.0.0
 */
final class PublicacoesPorNotificarMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara uma referência temporal determinística.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que apenas publicações efetivas e ainda não notificadas são
     * selecionadas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function seleciona_apenas_publicadas_ainda_por_notificar(): void
    {
        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-09-30',
                    ),
                )
                ->create();

        $publicadaAnterior =
            $this->criarMetalThursday(
                $edicao,
                '2026-08-20',
            );

        $publicadaHoje =
            $this->criarMetalThursday(
                $edicao,
                '2026-08-27',
            );

        $preparada =
            $this->criarMetalThursday(
                $edicao,
                '2026-09-03',
            );

        $jaNotificada =
            $this->criarMetalThursday(
                $edicao,
                '2026-08-13',
            );

        $eliminada =
            $this->criarMetalThursday(
                $edicao,
                '2026-08-06',
            );

        DB::table(
            'metal_thursdays',
        )
            ->where(
                'id',
                $jaNotificada->getKey(),
            )
            ->update([
                MetalThursday::COLUNA_PUBLICACAO_NOTIFICADA_EM => CarbonImmutable::parse(
                    '2026-08-13 00:05:00',
                    'Europe/Lisbon',
                ),
            ]);

        $eliminada->deleteOrFail();

        $resultados =
            MetalThursday::query()
                ->publicadasPorNotificar()
                ->orderBy(
                    'data',
                )
                ->get();

        self::assertSame(
            [
                (int) $publicadaAnterior->getKey(),
                (int) $publicadaHoje->getKey(),
            ],
            $resultados->modelKeys(),
        );

        self::assertNotContains(
            (int) $preparada->getKey(),
            $resultados->modelKeys(),
        );

        self::assertNotContains(
            (int) $jaNotificada->getKey(),
            $resultados->modelKeys(),
        );

        self::assertNotContains(
            (int) $eliminada->getKey(),
            $resultados->modelKeys(),
        );
    }

    /**
     * Cria uma MetalThursday na edição e data indicadas.
     *
     * @param  Edicao  $edicao  Edição associada.
     * @param  string  $data  Data no formato AAAA-MM-DD.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
        string $data,
    ): MetalThursday {
        return MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    $data,
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->create();
    }
}
