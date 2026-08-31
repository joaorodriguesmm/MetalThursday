<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\RascunhoMetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa a persistência HTTP dos rascunhos associados a reservas.
 *
 * @since 2.0.0
 */
final class RascunhoReservaMetalThursdayTest extends TestCase
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
     * Confirma que o responsável pode guardar um rascunho completamente vazio.
     *
     * A reserva permanece pendente e nenhuma MetalThursday final é criada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function responsavel_guarda_rascunho_vazio(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
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
            ->post(
                route(
                    'metal-thursday.reservas.rascunho.guardar',
                    $reserva,
                ),
                [],
            )
            ->assertRedirect(
                route(
                    'metal-thursday.reservas.preparar',
                    $reserva,
                ),
            )
            ->assertSessionHas(
                'sucesso',
                'Rascunho guardado com sucesso.',
            );

        $rascunho =
            RascunhoMetalThursday::query()
                ->firstOrFail();

        self::assertSame(
            [
                'nome' => null,

                'proximo_nomeado_id' => null,

                'seccoes' => [],
            ],
            $rascunho->dados,
        );

        self::assertTrue(
            $reserva
                ->refresh()
                ->estaPendente(),
        );

        self::assertNull(
            $reserva->metal_thursday_id,
        );

        self::assertSame(
            0,
            MetalThursday::query()->count(),
        );
    }

    /**
     * Confirma que um rascunho aceita conteúdo estruturalmente válido mesmo
     * quando ainda não constitui uma MetalThursday final válida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function responsavel_guarda_rascunho_incompleto(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
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
            ->post(
                route(
                    'metal-thursday.reservas.rascunho.guardar',
                    $reserva,
                ),
                [
                    'data' => '2099-01-01',

                    'autor_id' => 999999,

                    'edicao_id' => 999999,

                    'nome' => '  Especial   incompleto  ',

                    'seccoes' => [
                        [
                            'titulo' => '  Título   provisório  ',

                            'ligacao' => 'https://',
                        ],
                    ],
                ],
            )
            ->assertRedirect(
                route(
                    'metal-thursday.reservas.preparar',
                    $reserva,
                ),
            );

        $rascunho =
            RascunhoMetalThursday::query()
                ->firstOrFail();

        self::assertSame(
            'Especial incompleto',
            $rascunho->dados['nome'],
        );

        self::assertEquals(
            [
                [
                    'id' => null,

                    'tipo_seccao_id' => null,

                    'titulo' => 'Título provisório',

                    'descricao' => null,

                    'artista_id' => null,

                    'ligacao' => 'https://',

                    'tipo_incorporacao' => null,

                    'ano' => null,
                ],
            ],
            $rascunho->dados['seccoes'],
        );

        self::assertArrayNotHasKey(
            'data',
            $rascunho->dados,
        );

        self::assertArrayNotHasKey(
            'autor_id',
            $rascunho->dados,
        );

        self::assertArrayNotHasKey(
            'edicao_id',
            $rascunho->dados,
        );

        self::assertTrue(
            $reserva
                ->refresh()
                ->estaPendente(),
        );
    }

    /**
     * Confirma que novos guardamentos atualizam o mesmo rascunho.
     *
     * @since 2.0.0
     */
    #[Test]
    public function novo_guardamento_atualiza_rascunho_existente(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        $rota = route(
            'metal-thursday.reservas.rascunho.guardar',
            $reserva,
        );

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->post(
                $rota,
                [
                    'nome' => 'Primeira versão',
                ],
            )
            ->assertRedirect();

        $rascunhoInicial =
            RascunhoMetalThursday::query()
                ->firstOrFail();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->post(
                $rota,
                [
                    'nome' => 'Segunda versão',
                ],
            )
            ->assertRedirect();

        self::assertSame(
            1,
            RascunhoMetalThursday::query()->count(),
        );

        $rascunhoAtualizado =
            RascunhoMetalThursday::query()
                ->firstOrFail();

        self::assertSame(
            $rascunhoInicial->getKey(),
            $rascunhoAtualizado->getKey(),
        );

        self::assertSame(
            'Segunda versão',
            $rascunhoAtualizado->dados['nome'],
        );
    }

    /**
     * Confirma que outro utilizador não pode guardar o rascunho da reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function outro_utilizador_nao_guarda_rascunho(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $outroUtilizador =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->post(
                route(
                    'metal-thursday.reservas.rascunho.guardar',
                    $reserva,
                ),
                [
                    'nome' => 'Tentativa indevida',
                ],
            )
            ->assertForbidden();

        self::assertSame(
            0,
            RascunhoMetalThursday::query()->count(),
        );
    }

    /**
     * Confirma que uma reserva cumprida não aceita um novo rascunho.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reserva_cumprida_nao_aceita_rascunho(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $data =
            CarbonImmutable::parse(
                '2026-09-10',
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
                    $responsavel,
                )
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
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
            ->post(
                route(
                    'metal-thursday.reservas.rascunho.guardar',
                    $reserva,
                ),
                [
                    'nome' => 'Não permitido',
                ],
            )
            ->assertForbidden();

        self::assertSame(
            0,
            RascunhoMetalThursday::query()->count(),
        );
    }

    /**
     * Confirma que uma estrutura desconhecida não é persistida silenciosamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_campos_desconhecidos_numa_seccao(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->post(
                route(
                    'metal-thursday.reservas.rascunho.guardar',
                    $reserva,
                ),
                [
                    'seccoes' => [
                        [
                            'campo_inesperado' => 'valor',
                        ],
                    ],
                ],
            )
            ->assertSessionHasErrors(
                'seccoes.0',
            );

        self::assertSame(
            0,
            RascunhoMetalThursday::query()->count(),
        );
    }

    /**
     * Confirma que a preparação recupera os dados do rascunho persistido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preparacao_recupera_dados_do_rascunho(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $proximoNomeado =
            Utilizador::factory()
                ->create([
                    'nome' => 'Nomeado do Rascunho',
                ]);

        $reserva =
            ReservaMetalThursday::factory()
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        RascunhoMetalThursday::factory()
            ->comReserva(
                $reserva,
            )
            ->comDados([
                'nome' => 'Especial guardado',

                'proximo_nomeado_id' => $proximoNomeado->getKey(),

                'seccoes' => [
                    [
                        'id' => null,

                        'tipo_seccao_id' => null,

                        'titulo' => 'Título guardado',

                        'descricao' => 'Descrição guardada no rascunho.',

                        'artista_id' => null,

                        'ligacao' => 'https://exemplo.test',

                        'tipo_incorporacao' => null,

                        'ano' => null,
                    ],
                ],
            ])
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
                'dadosRascunhoFormulario',
                static fn (
                    mixed $valor,
                ): bool => (
                    is_array($valor)
                    && ($valor['nome'] ?? null)
                    === 'Especial guardado'
                ),
            )
            ->assertSeeHtml(
                'value="Especial guardado"',
            )
            ->assertSeeHtml(
                'value="Título guardado"',
            )
            ->assertSee(
                'Descrição guardada no rascunho.',
            )
            ->assertSeeHtml(
                'value="https://exemplo.test"',
            )
            ->assertSeeHtml(
                'value="'.
                    $proximoNomeado->getKey().
                    '"',
            );
    }

    /**
     * Confirma que dados antigos de uma submissão inválida prevalecem sobre o
     * conteúdo anteriormente guardado no rascunho.
     *
     * @since 2.0.0
     */
    #[Test]
    public function dados_antigos_prevalecem_sobre_rascunho(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        RascunhoMetalThursday::factory()
            ->comReserva(
                $reserva,
            )
            ->comDados([
                'nome' => 'Nome do rascunho',

                'proximo_nomeado_id' => null,

                'seccoes' => [
                    [
                        'id' => null,

                        'tipo_seccao_id' => null,

                        'titulo' => 'Título do rascunho',

                        'descricao' => null,

                        'artista_id' => null,

                        'ligacao' => null,

                        'tipo_incorporacao' => null,

                        'ano' => null,
                    ],
                ],
            ])
            ->create();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->withSession([
                '_old_input' => [
                    'nome' => 'Nome submetido',

                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => null,

                            'titulo' => 'Título submetido',

                            'descricao' => 'Descrição submetida.',

                            'artista_id' => null,

                            'ligacao' => null,

                            'tipo_incorporacao' => null,

                            'ano' => null,
                        ],
                    ],
                ],
            ])
            ->get(
                route(
                    'metal-thursday.reservas.preparar',
                    $reserva,
                ),
            )
            ->assertOk()
            ->assertSeeHtml(
                'value="Nome submetido"',
            )
            ->assertDontSeeHtml(
                'value="Nome do rascunho"',
            )
            ->assertSeeHtml(
                'value="Título submetido"',
            )
            ->assertDontSeeHtml(
                'value="Título do rascunho"',
            )
            ->assertSee(
                'Descrição submetida.',
            );
    }

    /**
     * Confirma que uma reserva sem rascunho continua a abrir um formulário
     * sem secções previamente preenchidas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preparacao_sem_rascunho_mantem_formulario_inicial_vazio(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
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
                'dadosRascunhoFormulario',
                [],
            )
            ->assertViewHas(
                'seccoesFormulario',
                [],
            );
    }

    /**
     * Confirma que a preparação apresenta separadamente a finalização e o
     * guardamento permissivo do rascunho.
     *
     * A ação principal permanece a primeira submissão do formulário para que uma
     * submissão implícita continue a representar a finalização.
     *
     * @since 2.0.0
     */
    #[Test]
    public function preparacao_apresenta_acoes_de_finalizacao_e_rascunho(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
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
            ->assertSee(
                'Marcar como preparada',
            )
            ->assertSee(
                'Guardar rascunho',
            )
            ->assertSeeHtml(
                'formaction="'.
                    route(
                        'metal-thursday.reservas.rascunho.guardar',
                        $reserva,
                    ).
                    '"',
            )
            ->assertSeeHtml(
                'formnovalidate',
            );
    }

    /**
     * Confirma que finalizar a preparação cria a MetalThursday, cumpre a
     * reserva e elimina o rascunho anterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function finalizacao_elimina_rascunho_e_cumpre_reserva(): void
    {
        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );

        $responsavel =
            Utilizador::factory()
                ->create();

        $proximoNomeado =
            Utilizador::factory()
                ->create();

        $data =
            CarbonImmutable::parse(
                '2026-09-10',
            );

        Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        $tipoSeccao =
            TipoSeccao::factory()
                ->semDetalhes()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comData(
                    $data,
                )
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        $rascunho =
            RascunhoMetalThursday::factory()
                ->comReserva(
                    $reserva,
                )
                ->comDados([
                    'nome' => 'Versão antiga do rascunho',

                    'proximo_nomeado_id' => null,

                    'seccoes' => [],
                ])
                ->create();

        $identificadorRascunho =
            $rascunho->getKey();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->post(
                route(
                    'metal-thursday.reservas.guardar',
                    $reserva,
                ),
                [
                    'proximo_nomeado_id' => $proximoNomeado->getKey(),

                    'seccoes' => [
                        [
                            'id' => null,

                            'tipo_seccao_id' => $tipoSeccao->getKey(),

                            'descricao' => 'Conteúdo definitivo da MetalThursday.',
                        ],
                    ],
                ],
            )
            ->assertRedirect(
                route(
                    'inicio',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                'MetalThursday marcada como preparada com sucesso.',
            );

        $reservaAtualizada =
            $reserva->refresh();

        self::assertTrue(
            $reservaAtualizada->estaCumprida(),
        );

        self::assertNotNull(
            $reservaAtualizada->metal_thursday_id,
        );

        $metalThursday =
            MetalThursday::query()
                ->findOrFail(
                    $reservaAtualizada->metal_thursday_id,
                );

        self::assertSame(
            $responsavel->getKey(),
            $metalThursday->autor_id,
        );

        self::assertSame(
            '2026-09-10',
            $metalThursday->data->format(
                'Y-m-d',
            ),
        );

        self::assertSame(
            1,
            $metalThursday
                ->seccoes()
                ->count(),
        );

        $this->assertDatabaseMissing(
            'rascunhos_metal_thursday',
            [
                'id' => $identificadorRascunho,
            ],
        );
    }

    /**
     * Confirma que uma falha ocorrida durante a persistência definitiva reverte
     * toda a finalização e preserva o rascunho anterior.
     *
     * A falha é provocada ao criar a secção, depois de a MetalThursday e a eventual
     * reserva seguinte já terem começado a ser persistidas dentro da transação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function falha_durante_persistencia_reverte_finalizacao_e_preserva_rascunho(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $proximoNomeado =
            Utilizador::factory()
                ->create();

        $data =
            CarbonImmutable::parse(
                '2026-09-10',
            );

        Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        $tipoSeccao =
            TipoSeccao::factory()
                ->semDetalhes()
                ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comData(
                    $data,
                )
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        $rascunho =
            RascunhoMetalThursday::factory()
                ->comReserva(
                    $reserva,
                )
                ->comDados([
                    'nome' => 'Rascunho a preservar perante falha',

                    'proximo_nomeado_id' => $proximoNomeado->getKey(),

                    'seccoes' => [],
                ])
                ->create();

        $identificadorRascunho =
            $rascunho->getKey();

        $totalReservasAntes =
            ReservaMetalThursday::query()
                ->count();

        SeccaoMetalThursday::creating(
            static function (
                SeccaoMetalThursday $seccao,
            ): void {
                throw new RuntimeException(
                    'Falha simulada ao persistir a secção.',
                );
            },
        );

        $this->withoutExceptionHandling();

        try {
            $this
                ->actingAs(
                    $responsavel,
                    'sessao',
                )
                ->post(
                    route(
                        'metal-thursday.reservas.guardar',
                        $reserva,
                    ),
                    [
                        'proximo_nomeado_id' => $proximoNomeado->getKey(),

                        'seccoes' => [
                            [
                                'id' => null,

                                'tipo_seccao_id' => $tipoSeccao->getKey(),

                                'descricao' => 'Conteúdo definitivo que deve ser revertido.',
                            ],
                        ],
                    ],
                );

            self::fail(
                'Era esperada uma falha durante a persistência da secção.',
            );
        } catch (RuntimeException $excecao) {
            self::assertSame(
                'Falha simulada ao persistir a secção.',
                $excecao->getMessage(),
            );
        }

        $reservaAtualizada =
            $reserva->refresh();

        self::assertTrue(
            $reservaAtualizada->estaPendente(),
        );

        self::assertNull(
            $reservaAtualizada->metal_thursday_id,
        );

        self::assertSame(
            0,
            MetalThursday::query()
                ->count(),
        );

        self::assertSame(
            $totalReservasAntes,
            ReservaMetalThursday::query()
                ->count(),
        );

        $this->assertDatabaseHas(
            'rascunhos_metal_thursday',
            [
                'id' => $identificadorRascunho,

                'reserva_metal_thursday_id' => $reserva->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma tentativa inválida de finalização não elimina o
     * rascunho nem cumpre a reserva.
     *
     * @since 2.0.0
     */
    #[Test]
    public function finalizacao_invalida_preserva_rascunho(): void
    {
        $responsavel =
            Utilizador::factory()
                ->create();

        $proximoNomeado =
            Utilizador::factory()
                ->create();

        $data =
            CarbonImmutable::parse(
                '2026-09-10',
            );

        Edicao::factory()
            ->comPeriodo(
                $data->startOfMonth(),
                $data->endOfMonth(),
            )
            ->create();

        $reserva =
            ReservaMetalThursday::factory()
                ->comData(
                    $data,
                )
                ->comResponsavel(
                    $responsavel,
                )
                ->create();

        $rascunho =
            RascunhoMetalThursday::factory()
                ->comReserva(
                    $reserva,
                )
                ->comDados([
                    'nome' => 'Rascunho a preservar',

                    'proximo_nomeado_id' => $proximoNomeado->getKey(),

                    'seccoes' => [],
                ])
                ->create();

        $identificadorRascunho =
            $rascunho->getKey();

        $this
            ->actingAs(
                $responsavel,
                'sessao',
            )
            ->post(
                route(
                    'metal-thursday.reservas.guardar',
                    $reserva,
                ),
                [
                    'proximo_nomeado_id' => $proximoNomeado->getKey(),

                    'seccoes' => [],
                ],
            )
            ->assertSessionHasErrors(
                'seccoes',
            );

        $reservaAtualizada =
            $reserva->refresh();

        self::assertTrue(
            $reservaAtualizada->estaPendente(),
        );

        self::assertNull(
            $reservaAtualizada->metal_thursday_id,
        );

        self::assertSame(
            0,
            MetalThursday::query()->count(),
        );

        $this->assertDatabaseHas(
            'rascunhos_metal_thursday',
            [
                'id' => $identificadorRascunho,

                'reserva_metal_thursday_id' => $reserva->getKey(),
            ],
        );
    }
}
