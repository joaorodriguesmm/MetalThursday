<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ReservasPendentesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function utilizador_comum_ve_apenas_as_suas_reservas(): void
    {
        $responsavel = Utilizador::factory()->create();
        $outro = Utilizador::factory()->create();
        $semReserva = Utilizador::factory()->create();

        $this->criarReserva($responsavel, '2027-01-07');
        $this->criarReserva($outro, '2027-01-14');

        $this->actingAs($responsavel, 'sessao');

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('07/01/2027')
            ->assertDontSee('14/01/2027');

        $this->actingAs($semReserva, 'sessao');

        $this->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('07/01/2027')
            ->assertDontSee('14/01/2027');
    }

    #[Test]
    public function apenas_superadministrador_ve_todas_as_reservas(): void
    {
        $responsavel = Utilizador::factory()->create();
        $this->criarReserva($responsavel, '2027-01-07');

        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create();

        $this->actingAs($administrador, 'sessao');

        $this->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('07/01/2027');

        $superAdministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $this->actingAs($superAdministrador, 'sessao');

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('07/01/2027');
    }

    private function criarReserva(Utilizador $responsavel, string $data): void
    {
        ReservaMetalThursday::factory()
            ->comResponsavel($responsavel)
            ->comData(CarbonImmutable::parse($data))
            ->create();
    }
}
