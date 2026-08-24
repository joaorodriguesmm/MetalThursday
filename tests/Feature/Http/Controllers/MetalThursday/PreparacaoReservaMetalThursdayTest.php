<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o acesso e a submissão explícitos de uma reserva de MetalThursday.
 *
 * @since 2.0.0
 */
final class PreparacaoReservaMetalThursdayTest extends TestCase
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

        Notification::fake();
    }

    /**
     * Confirma que o responsável comum abre a reserva explicitamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function responsavel_comum_abre_preparacao_da_reserva(): void
    {
        $responsavel = Utilizador::factory()
            ->create([
                'nome' => 'Responsável Comum',
            ]);

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-10',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.reservas.preparar',
                    $reserva,
                ),
            )
            ->assertOk()
            ->assertViewHas(
                'modoPreparacaoReserva',
                true,
            )
            ->assertViewHas(
                'reservaPendente',
                static fn (
                    mixed $valor,
                ): bool => (
                    $valor instanceof ReservaMetalThursday
                    && $valor->is(
                        $reserva,
                    )
                ),
            )
            ->assertViewHas(
                'podeAlterarData',
                false,
            )
            ->assertViewHas(
                'podeSelecionarAutor',
                false,
            )
            ->assertSee(
                'Preparar MetalThursday',
            )
            ->assertSeeHtml(
                'value="2026-09-10"',
            )
            ->assertSee(
                'Responsável Comum',
            )
            ->assertSeeHtml(
                'action="'.
                    route(
                        'metal-thursday.reservas.guardar',
                        $reserva,
                    ).
                    '"',
            );
    }

    /**
     * Confirma que a preparação também bloqueia data e autor quando o
     * responsável é administrador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_responsavel_prepara_reserva_com_data_e_autor_fixos(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'nome' => 'Administrador Responsável',
            ]);

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-10',
                ),
            )
            ->comResponsavel(
                $administrador,
            )
            ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.reservas.preparar',
                    $reserva,
                ),
            )
            ->assertOk()
            ->assertViewHas(
                'podeAlterarData',
                false,
            )
            ->assertViewHas(
                'podeSelecionarAutor',
                false,
            )
            ->assertSeeHtml(
                'value="2026-09-10"',
            )
            ->assertSee(
                'Administrador Responsável',
            );
    }

    /**
     * Confirma que outro utilizador não pode preparar a reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function outro_utilizador_nao_prepara_reserva(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $outroUtilizador = Utilizador::factory()
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-17',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.reservas.preparar',
                    $reserva,
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que uma reserva já cumprida não pode voltar a ser preparada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reserva_cumprida_nao_pode_ser_preparada(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $data = CarbonImmutable::parse(
            '2026-09-10',
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
            ->comAutor(
                $responsavel,
            )
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comMetalThursday(
                $metalThursday,
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.reservas.preparar',
                    $reserva,
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que uma reserva sem responsável não pode ser preparada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reserva_sem_responsavel_nao_pode_ser_preparada(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-10',
                ),
            )
            ->semResponsavel()
            ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.reservas.preparar',
                    $reserva,
                ),
            )
            ->assertForbidden();
    }

    /**
     * Confirma que a ação da slot aponta para a reserva concreta.
     *
     * @since 2.0.0
     */
    #[Test]
    public function slot_aponta_para_rota_explicita_da_reserva(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-10',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

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
                'href="'.
                    route(
                        'metal-thursday.reservas.preparar',
                        $reserva,
                    ).
                    '"',
            );
    }

    /**
     * Confirma que a submissão da reserva ignora uma tentativa de adulterar a
     * data e o autor e cumpre a slot indicada na rota.
     *
     * @since 2.0.0
     */
    #[Test]
    public function submissao_da_reserva_impoe_data_e_autor_da_slot(): void
    {
        $administradorResponsavel = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $outroAutor = Utilizador::factory()
            ->create();

        $proximoNomeado = Utilizador::factory()
            ->create();

        $dataReserva = CarbonImmutable::parse(
            '2026-09-10',
        );

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                $dataReserva,
            )
            ->comResponsavel(
                $administradorResponsavel,
            )
            ->create();

        Edicao::factory()
            ->comPeriodo(
                $dataReserva->startOfMonth(),
                $dataReserva->endOfMonth(),
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this
            ->actingAs(
                $administradorResponsavel,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.reservas.guardar',
                    $reserva,
                ),
                [
                    'data' => '2026-09-24',

                    'autor_id' => $outroAutor->getKey(),

                    'proximo_nomeado_id' => $proximoNomeado->getKey(),

                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => $tipoSeccao->getKey(),

                            'descricao' => 'Publicação preparada pela reserva.',
                        ],
                    ],
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'metal_thursday.data',
                '2026-09-10',
            );

        $metalThursday = MetalThursday::query()
            ->where(
                'data',
                '2026-09-10',
            )
            ->firstOrFail();

        self::assertSame(
            $administradorResponsavel->getKey(),
            $metalThursday->autor_id,
        );

        self::assertSame(
            $metalThursday->getKey(),
            $reserva
                ->refresh()
                ->metal_thursday_id,
        );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-09-24',
            ],
        );
    }

    /**
     * Confirma que outro utilizador também não consegue submeter a reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function outro_utilizador_nao_submete_reserva(): void
    {
        $responsavel = Utilizador::factory()
            ->create();

        $outroUtilizador = Utilizador::factory()
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-09-10',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.reservas.guardar',
                    $reserva,
                ),
                [],
            )
            ->assertForbidden();

        self::assertTrue(
            $reserva
                ->refresh()
                ->estaPendente(),
        );
    }
}
