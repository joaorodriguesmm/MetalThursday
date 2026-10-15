<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\RascunhoMetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Servicos\MetalThursday\ServicoPreparacaoMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma a revalidação transacional de reservas carregadas anteriormente.
 *
 * @since 2.0.0
 */
final class ServicoPreparacaoReservaDesatualizadaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uma alteração de responsável impede a gravação do rascunho,
     * mesmo quando o modelo recebido ainda contém o responsável anterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_rascunho_apos_alteracao_do_responsavel(): void
    {
        $responsavelAnterior = Utilizador::factory()->create();
        $responsavelAtual = Utilizador::factory()->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-09-10'))
            ->comResponsavel($responsavelAnterior)
            ->create();

        $reservaDesatualizada = ReservaMetalThursday::query()
            ->findOrFail($reserva->getKey());

        $reserva->responsavel_id = (int) $responsavelAtual->getKey();
        $reserva->saveOrFail();

        self::assertSame(
            (int) $responsavelAnterior->getKey(),
            $reservaDesatualizada->responsavel_id,
        );

        $resultado = app(ServicoPreparacaoMetalThursday::class)
            ->guardarRascunho(
                $reservaDesatualizada,
                $responsavelAnterior,
                ['nome' => 'Rascunho indevido'],
            );

        self::assertNull($resultado);
        self::assertSame(0, RascunhoMetalThursday::query()->count());
        $reserva->refresh();

        self::assertSame(
            (int) $responsavelAtual->getKey(),
            $reserva->responsavel_id,
        );
    }

    /**
     * Uma alteração de responsável impede a finalização de uma reserva
     * desatualizada e preserva o rascunho anteriormente guardado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_finalizacao_apos_alteracao_do_responsavel(): void
    {
        $responsavelAnterior = Utilizador::factory()->create();
        $responsavelAtual = Utilizador::factory()->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-09-10'))
            ->comResponsavel($responsavelAnterior)
            ->create();

        $rascunho = RascunhoMetalThursday::factory()
            ->comReserva($reserva)
            ->comDados([
                'nome' => 'Rascunho a preservar',
                'proximo_nomeado_id' => null,
                'seccoes' => [],
            ])
            ->create();

        $reservaDesatualizada = ReservaMetalThursday::query()
            ->findOrFail($reserva->getKey());

        $reserva->responsavel_id = (int) $responsavelAtual->getKey();
        $reserva->saveOrFail();

        $resultado = app(ServicoPreparacaoMetalThursday::class)
            ->finalizar(
                $reservaDesatualizada,
                $responsavelAnterior,
                [],
            );

        self::assertNull($resultado);
        self::assertSame(0, MetalThursday::query()->count());
        self::assertSame(1, RascunhoMetalThursday::query()->count());
        $rascunho->refresh();
        $reserva->refresh();

        self::assertSame(
            'Rascunho a preservar',
            $rascunho->dados['nome'],
        );
        self::assertTrue($reserva->estaPendente());
        $reserva->refresh();

        self::assertSame(
            (int) $responsavelAtual->getKey(),
            $reserva->responsavel_id,
        );
    }
}
