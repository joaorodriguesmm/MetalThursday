<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Servicos\MetalThursday\ServicoReservasMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Impede reservas novas para datas já ocupadas por MetalThursdays.
 */
final class ReservasDatasOcupadasTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function nomeacao_explicita_nao_cria_reserva_para_data_ja_publicada(): void
    {
        $data = CarbonImmutable::parse('2026-08-27');
        $nomeado = Utilizador::factory()->create();
        $this->criarMetalThursday($data);

        $resultado = app(ServicoReservasMetalThursday::class)
            ->criarReservaParaNomeado($data, (int) $nomeado->getKey());

        self::assertNull($resultado);
        $this->assertDatabaseMissing('reservas_metal_thursday', [
            'data' => '2026-08-27',
        ]);
    }

    #[Test]
    public function reserva_automatica_nao_cria_slot_para_data_ja_publicada(): void
    {
        $data = CarbonImmutable::parse('2026-08-27');
        Utilizador::factory()->create();
        $this->criarMetalThursday($data);

        $resultado = app(ServicoReservasMetalThursday::class)
            ->criarReservaAutomatica($data);

        self::assertNull($resultado);
        $this->assertDatabaseMissing('reservas_metal_thursday', [
            'data' => '2026-08-27',
        ]);
    }

    #[Test]
    public function fallback_nao_cria_slot_se_data_seguinte_ja_estiver_ocupada(): void
    {
        $data = CarbonImmutable::parse('2026-08-27');
        $responsavel = Utilizador::factory()->create();
        ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-08-20'))
            ->comResponsavel($responsavel)
            ->create();
        $this->criarMetalThursday($data);

        $resultado = app(ServicoReservasMetalThursday::class)
            ->criarReservaSemanal(CarbonImmutable::parse('2026-08-21'));

        self::assertNull($resultado);
        $this->assertDatabaseCount('reservas_metal_thursday', 1);
        $this->assertDatabaseMissing('reservas_metal_thursday', [
            'data' => '2026-08-27',
        ]);
    }

    #[Test]
    public function nao_cria_reserva_para_data_de_registo_eliminado_logicamente(): void
    {
        $data = CarbonImmutable::parse('2026-08-27');
        $nomeado = Utilizador::factory()->create();
        $metalThursday = $this->criarMetalThursday($data);
        $metalThursday->delete();

        $resultado = app(ServicoReservasMetalThursday::class)
            ->criarReservaParaNomeado($data, (int) $nomeado->getKey());

        self::assertNull($resultado);
        $this->assertDatabaseMissing('reservas_metal_thursday', [
            'data' => '2026-08-27',
        ]);
    }

    private function criarMetalThursday(CarbonImmutable $data): MetalThursday
    {
        $edicao = Edicao::factory()
            ->comPeriodo($data->startOfMonth(), $data->endOfMonth())
            ->create();

        return MetalThursday::factory()
            ->comData($data)
            ->comEdicao($edicao)
            ->create();
    }
}
