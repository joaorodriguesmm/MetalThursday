<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\RascunhoMetalThursday;
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
                'Por preparar',
                'Responsável Primeiro',
                'MetalThursday de',
                '27/08/2026',
                'Por preparar',
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

    /**
     * Confirma que uma reserva com rascunho apresenta o estado e a ação de
     * continuação da preparação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function listagem_identifica_reserva_como_rascunho(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create([
                    'nome' => 'Responsável do Rascunho',
                ]);

        $reserva =
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

        RascunhoMetalThursday::factory()
            ->comReserva(
                $reserva,
            )
            ->create();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-21 12:00:00',
                'Europe/Lisbon',
            ),
        );

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertSeeHtml(
                'id="reserva-metal-thursday-'.$reserva->getKey().'"',
            )
            ->assertSee(
                'Rascunho',
            )
            ->assertSee(
                'Continuar preparação',
            )
            ->assertSeeHtml(
                'href="'.
                    route(
                        'metal-thursday.reservas.preparar',
                        $reserva,
                    ).
                    '"',
            );
    }

    /**
     * Confirma que uma MetalThursday futura finalizada continua na área
     * operacional para o respetivo autor.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_ve_metal_thursday_preparada_com_acao_de_edicao(): void
    {
        $autor =
            Utilizador::factory()
                ->create([
                    'nome' => 'Autor da Preparada',
                ]);

        $data =
            CarbonImmutable::parse(
                '2026-08-27',
            );

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    $data->startOfMonth(),
                    $data->endOfMonth(),
                )
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->comData(
                    $data,
                )
                ->comEdicao(
                    $edicao,
                )
                ->comAutor(
                    $autor,
                )
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comData(
                    $data,
                )
                ->comResponsavel(
                    $autor,
                )
                ->comMetalThursday(
                    $metalThursday,
                )
                ->create();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-21 12:00:00',
                'Europe/Lisbon',
            ),
        );

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertDontSeeHtml(
                'id="reserva-metal-thursday-'.$reserva->getKey().'"',
            )
            ->assertSeeHtml(
                'id="metal-thursday-preparada-'.$metalThursday->getKey().'"',
            )
            ->assertSee(
                'Preparada',
            )
            ->assertSee(
                'Autor da Preparada',
            )
            ->assertSee(
                'Editar preparação',
            )
            ->assertSeeHtml(
                'href="'.
                    route(
                        'metal-thursday.editar',
                        $metalThursday,
                    ).
                    '"',
            );
    }

    /**
     * Confirma que uma MetalThursday preparada não é exposta por esta área
     * operacional a um utilizador sem autorização para a alterar.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_sem_permissao_nao_ve_metal_thursday_preparada(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $outroUtilizador =
            Utilizador::factory()
                ->create();

        $data =
            CarbonImmutable::parse(
                '2026-08-27',
            );

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    $data->startOfMonth(),
                    $data->endOfMonth(),
                )
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->comData(
                    $data,
                )
                ->comEdicao(
                    $edicao,
                )
                ->comAutor(
                    $autor,
                )
                ->create();

        ReservaMetalThursday::factory()
            ->comData(
                $data,
            )
            ->comResponsavel(
                $autor,
            )
            ->comMetalThursday(
                $metalThursday,
            )
            ->create();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-21 12:00:00',
                'Europe/Lisbon',
            ),
        );

        $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertDontSeeHtml(
                'id="metal-thursday-preparada-'.$metalThursday->getKey().'"',
            )
            ->assertDontSee(
                'Editar preparação',
            );
    }
}
