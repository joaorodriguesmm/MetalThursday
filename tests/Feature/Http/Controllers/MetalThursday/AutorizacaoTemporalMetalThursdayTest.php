<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AutorizacaoTemporalMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-11-12 09:00:00'));
    }

    #[Test]
    public function administrador_nao_acede_a_preparada_mesmo_sendo_autor_e_criador(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create();

        $metalThursday = $this->criarMetalThursday(
            '2026-11-19',
            $administrador
        );

        DB::table('metal_thursdays')
            ->where('id', $metalThursday->getKey())
            ->update(['criado_por_id' => $administrador->getKey()]);

        $this->actingAs($administrador, 'sessao');

        $this->get(route('metal-thursday.detalhes', $metalThursday))
            ->assertNotFound();

        $this->get(route('metal-thursday.editar', $metalThursday))
            ->assertForbidden();

        $this->patchJson(
            route('metal-thursday.atualizar', $metalThursday),
            []
        )->assertForbidden();

        $this->deleteJson(
            route('metal-thursday.eliminar', $metalThursday)
        )->assertForbidden();

        $this->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('19/11/2026');

        $this->assertNotSoftDeleted('metal_thursdays', [
            'id' => $metalThursday->getKey(),
        ]);
    }

    #[Test]
    public function administrador_pode_consultar_e_editar_depois_da_publicacao(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create();

        $autor = Utilizador::factory()->create();

        $metalThursday = $this->criarMetalThursday(
            '2026-11-05',
            $autor
        );

        $this->actingAs($administrador, 'sessao');

        $this->get(route('metal-thursday.detalhes', $metalThursday))
            ->assertOk();

        $this->get(route('metal-thursday.editar', $metalThursday))
            ->assertOk();
    }

    #[Test]
    public function autor_e_superadministrador_accedem_a_preparada(): void
    {
        $autor = Utilizador::factory()->create();

        $superAdministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create();

        $metalThursday = $this->criarMetalThursday(
            '2026-11-19',
            $autor
        );

        foreach ([$autor, $superAdministrador] as $utilizador) {
            $this->actingAs($utilizador, 'sessao');

            $this->get(route('metal-thursday.detalhes', $metalThursday))
                ->assertOk();

            $this->get(route('metal-thursday.editar', $metalThursday))
                ->assertOk();
        }
    }

    #[Test]
    public function utilizador_alheio_nao_acede_a_preparada(): void
    {
        $autor = Utilizador::factory()->create();
        $outro = Utilizador::factory()->create();

        $metalThursday = $this->criarMetalThursday(
            '2026-11-19',
            $autor
        );

        $this->actingAs($outro, 'sessao');

        $this->get(route('metal-thursday.detalhes', $metalThursday))
            ->assertNotFound();

        $this->get(route('metal-thursday.editar', $metalThursday))
            ->assertForbidden();
    }

    #[Test]
    public function administrador_nao_prepara_nem_guarda_reserva_propria(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(CarbonImmutable::parse('2026-11-26'))
            ->comResponsavel($administrador)
            ->create();

        $this->actingAs($administrador, 'sessao');

        $this->get(
            route('metal-thursday.reservas.preparar', $reserva)
        )->assertForbidden();

        $this->postJson(
            route('metal-thursday.reservas.rascunho.guardar', $reserva),
            []
        )->assertForbidden();

        $this->postJson(
            route('metal-thursday.reservas.guardar', $reserva),
            []
        )->assertForbidden();
    }

    private function criarMetalThursday(
        string $data,
        Utilizador $autor
    ): MetalThursday {
        $instante = CarbonImmutable::parse($data);

        $edicao = Edicao::factory()
            ->comPeriodo(
                $instante->startOfMonth(),
                $instante->endOfMonth()
            )
            ->create();

        return MetalThursday::factory()
            ->comData($instante)
            ->comEdicao($edicao)
            ->comAutor($autor)
            ->comProximoNomeado(Utilizador::factory()->create())
            ->create();
    }
}
