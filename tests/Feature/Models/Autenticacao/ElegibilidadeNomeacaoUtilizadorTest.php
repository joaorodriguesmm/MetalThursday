<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a definição comum da elegibilidade para nomeações.
 *
 * @since 2.0.0
 */
final class ElegibilidadeNomeacaoUtilizadorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma todos os critérios necessários para uma nova nomeação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function filtra_utilizadores_elegiveis_para_nomeacao(): void
    {
        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create([
                'nome' => 'Super Administrador',
            ]);

        $elegivel = Utilizador::factory()
            ->create([
                'nome' => 'Elegível',
            ]);

        $comHistoricoCumprido = Utilizador::factory()
            ->create([
                'nome' => 'Histórico Cumprido',
            ]);

        Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create([
                'nome' => 'Indisponível',
            ]);

        Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
            )
            ->create([
                'nome' => 'Suspenso',
            ]);

        $comReservaPendente = Utilizador::factory()
            ->create([
                'nome' => 'Reserva Pendente',
            ]);

        $dataCumprida = CarbonImmutable::parse(
            '2026-08-20',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                $dataCumprida->startOfMonth(),
                $dataCumprida->endOfMonth(),
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                $dataCumprida,
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $elegivel,
            )
            ->comProximoNomeado(
                $comHistoricoCumprido,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comResponsavel(
                $comHistoricoCumprido,
            )
            ->comMetalThursday(
                $metalThursday,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $comReservaPendente,
            )
            ->create();

        $identificadores = Utilizador::query()
            ->elegiveisParaNomeacao()
            ->pluck(
                'id',
            )
            ->map(
                static fn (
                    mixed $identificador,
                ): int => (int) $identificador,
            )
            ->sort()
            ->values()
            ->all();

        $esperados = [
            (int) $elegivel->getKey(),
            (int) $comHistoricoCumprido->getKey(),
        ];

        sort(
            $esperados,
            SORT_NUMERIC,
        );

        self::assertSame(
            $esperados,
            $identificadores,
        );
    }
}
