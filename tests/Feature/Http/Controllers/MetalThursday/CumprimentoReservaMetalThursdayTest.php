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
 * Testa o cumprimento das reservas através da publicação de MetalThursdays.
 *
 * @since 2.0.0
 */
final class CumprimentoReservaMetalThursdayTest extends TestCase
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
     * Confirma que uma reserva antiga pode ser cumprida depois da quinta-feira.
     *
     * A indisponibilidade atual não cancela a reserva anteriormente atribuída.
     *
     * @since 2.0.0
     */
    #[Test]
    public function permite_publicacao_tardia_de_reserva_existente(): void
    {
        $utilizador = Utilizador::factory()
            ->indisponivelParaNomeacao()
            ->create();

        $proximoNomeado = Utilizador::factory()
            ->create();

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        $edicao = $this->criarEdicaoJaneiro();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-01-20 12:00:00',
                'Europe/Lisbon',
            ),
        );

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.reservas.guardar',
                    $reserva,
                ),
                [
                    'edicao_id' => $edicao->getKey(),

                    'data' => '2026-01-15',

                    'nome' => null,

                    'autor_id' => $utilizador->getKey(),

                    'proximo_nomeado_id' => $proximoNomeado->getKey(),

                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => $tipoSeccao->getKey(),

                            'descricao' => 'Secção de publicação tardia.',
                        ],
                    ],
                ],
            )
            ->assertCreated();

        $metalThursday = MetalThursday::query()
            ->where(
                'data',
                '2026-01-15',
            )
            ->firstOrFail();

        self::assertSame(
            $utilizador->getKey(),
            $metalThursday->autor_id,
        );

        self::assertSame(
            $metalThursday->getKey(),
            $reserva
                ->refresh()
                ->metal_thursday_id,
        );
    }

    /**
     * Confirma que um utilizador sem reserva pendente não é autorizado a
     * publicar.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_publicacao_de_utilizador_sem_reserva(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $proximoNomeado = Utilizador::factory()
            ->create();

        $edicao = $this->criarEdicaoJaneiro();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                [
                    'edicao_id' => $edicao->getKey(),

                    'data' => '2026-01-15',

                    'nome' => null,

                    'autor_id' => $utilizador->getKey(),

                    'proximo_nomeado_id' => $proximoNomeado->getKey(),

                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => $tipoSeccao->getKey(),

                            'descricao' => 'Secção de teste.',
                        ],
                    ],
                ],
            )
            ->assertForbidden();

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-15',
            ],
        );
    }

    /**
     * Confirma que um administrador não pode ocupar o slot reservado a outro
     * utilizador utilizando um autor diferente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_pode_substituir_responsavel_da_reserva_implicitamente(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $responsavel = Utilizador::factory()
            ->create();

        $outroAutor = Utilizador::factory()
            ->create();

        $proximoNomeado = Utilizador::factory()
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comResponsavel(
                $responsavel,
            )
            ->create();

        $edicao = $this->criarEdicaoJaneiro();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'metal-thursday.guardar',
                ),
                [
                    'edicao_id' => $edicao->getKey(),

                    'data' => '2026-01-15',

                    'nome' => null,

                    'autor_id' => $outroAutor->getKey(),

                    'proximo_nomeado_id' => $proximoNomeado->getKey(),

                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => $tipoSeccao->getKey(),

                            'descricao' => 'Secção de teste.',
                        ],
                    ],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'autor_id',
            ])
            ->assertJsonPath(
                'errors.autor_id.0',
                'O autor deve corresponder ao responsável da reserva desta data.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-15',
            ],
        );
    }

    /**
     * Cria a edição utilizada nos cenários de janeiro.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicaoJaneiro(): Edicao
    {
        return Edicao::factory()
            ->comNome(
                'Edição de janeiro',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();
    }
}
