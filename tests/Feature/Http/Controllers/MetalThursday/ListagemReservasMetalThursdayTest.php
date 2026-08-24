<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a apresentação das reservas pendentes na listagem principal.
 *
 * @since 2.0.0
 */
final class ListagemReservasMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste sem depender dos ficheiros produzidos pelo Vite.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Confirma que as reservas pendentes são apresentadas cronologicamente e
     * com o estado correspondente à data atual.
     *
     * @since 2.0.0
     */
    #[Test]
    public function listagem_apresenta_reservas_pendentes_por_ordem_cronologica(): void
    {
        $responsavelPrimeira = Utilizador::factory()
            ->create([
                'nome' => 'Responsável Primeiro',
            ]);

        $responsavelSegunda = Utilizador::factory()
            ->create([
                'nome' => 'Responsável Segundo',
            ]);

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $responsavelSegunda,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                ),
            )
            ->comResponsavel(
                $responsavelPrimeira,
            )
            ->create();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-21 12:00:00',
                'Europe/Lisbon',
            ),
        );

        $resposta = $this
            ->actingAs(
                $responsavelPrimeira,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            );

        $resposta
            ->assertOk()
            ->assertSee(
                'MetalThursdays por publicar',
            )
            ->assertSeeInOrder([
                'MetalThursday de',
                '20/08/2026',
                'Em atraso',
                'Responsável Primeiro',
                'MetalThursday de',
                '27/08/2026',
                'Por publicar',
                'Responsável Segundo',
            ]);

        self::assertSame(
            1,
            substr_count(
                $resposta->getContent(),
                'Preparar MetalThursday',
            ),
        );
    }

    /**
     * Confirma que um utilizador diferente do responsável não recebe a ação
     * de preparação da reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_diferente_do_responsavel_nao_ve_acao_preparar(): void
    {
        $utilizadorAutenticado = Utilizador::factory()
            ->create();

        $responsavel = Utilizador::factory()
            ->create([
                'nome' => 'Responsável da Reserva',
            ]);

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $this
            ->actingAs(
                $utilizadorAutenticado,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Responsável da Reserva',
            )
            ->assertDontSee(
                'Preparar MetalThursday',
            );
    }

    /**
     * Confirma que uma reserva sem responsável continua visível para que o
     * estado de atribuição não fique oculto.
     *
     * @since 2.0.0
     */
    #[Test]
    public function listagem_apresenta_reserva_sem_responsavel(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-27',
                ),
            )
            ->semResponsavel()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertSee(
                '27/08/2026',
            )
            ->assertSee(
                'Por atribuir',
            )
            ->assertDontSee(
                'Preparar MetalThursday',
            );
    }

    /**
     * Confirma que reservas já cumpridas não regressam à área das
     * MetalThursdays por publicar.
     *
     * @since 2.0.0
     */
    #[Test]
    public function listagem_nao_apresenta_reserva_ja_cumprida(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $dataCumprida = CarbonImmutable::parse(
            '2026-08-13',
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
                $utilizador,
            )
            ->create();

        $reservaCumprida = ReservaMetalThursday::factory()
            ->comMetalThursday(
                $metalThursday,
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        $reservaPendente = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-08-20',
                ),
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertDontSeeHtml(
                'id="reserva-metal-thursday-'.$reservaCumprida->getKey().'"',
            )
            ->assertSeeHtml(
                'id="reserva-metal-thursday-'.$reservaPendente->getKey().'"',
            );
    }
}
