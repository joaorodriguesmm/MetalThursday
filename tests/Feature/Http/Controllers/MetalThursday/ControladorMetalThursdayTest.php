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
 * Testa os pedidos HTTP associados às MetalThursdays.
 *
 * @since 2.0.0
 */
final class ControladorMetalThursdayTest extends TestCase
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
     * Confirma que a edição deixou de ser selecionável pelo utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_criacao_nao_apresenta_seletor_manual_de_edicao(): void
    {
        $utilizador = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $this->get(
            route(
                'metal-thursday.criar',
            ),
        )
            ->assertOk()
            ->assertSeeHtml(
                'id="edicao-metal-thursday"',
            )
            ->assertDontSeeHtml(
                'name="edicao_id"',
            )
            ->assertSee(
                'A edição é determinada automaticamente pela data da MetalThursday.',
            )
            ->assertSeeHtml(
                'id="dados-edicoes-metal-thursday"',
            )
            ->assertSeeHtml(
                'data-edicao-identificador="'.$edicao->getKey().'"',
            )
            ->assertSeeHtml(
                'data-edicao-inicio="2026-01-01"',
            )
            ->assertSeeHtml(
                'data-edicao-fim="2026-01-31"',
            )
            ->assertSeeHtml(
                'name="seccoes[__INDICE_SECCAO__][tipo_seccao_id]"',
            )
            ->assertDontSeeHtml(
                'name="seccoes[__INDICE_SECCAO__][tipo_secao_id]"',
            );
    }

    /**
     * Confirma que o formulário de criação apresenta os tipos de secção
     * segundo a ordem funcional configurada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_criacao_apresenta_tipos_seccao_pela_ordem_configurada(): void
    {
        $utilizador = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        TipoSeccao::factory()
            ->comDados(
                'tipo_primeiro',
                'Zulu',
                'Primeiro tipo.',
            )
            ->naOrdem(
                1,
            )
            ->create();

        TipoSeccao::factory()
            ->comDados(
                'tipo_segundo',
                'Alfa',
                'Segundo tipo.',
            )
            ->naOrdem(
                2,
            )
            ->create();

        TipoSeccao::factory()
            ->comDados(
                'tipo_terceiro',
                'Mike',
                'Terceiro tipo.',
            )
            ->naOrdem(
                3,
            )
            ->create();

        $this->get(
            route(
                'metal-thursday.criar',
            ),
        )
            ->assertOk()
            ->assertSeeInOrder([
                'Zulu',
                'Alfa',
                'Mike',
            ]);
    }

    /**
     * Confirma que um utilizador comum recebe o próprio autor fixo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_criacao_utilizador_comum_fixa_autor_autenticado(): void
    {
        $utilizador = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this->get(
            route(
                'metal-thursday.criar',
            ),
        )
            ->assertOk()
            ->assertSee(
                $utilizador->nome,
            )
            ->assertSeeHtml(
                'readonly',
            )
            ->assertDontSeeHtml(
                'placeholder="Seleciona o autor"',
            );
    }

    /**
     * Confirma que um administrador pode selecionar o autor.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_criacao_administrador_permite_selecionar_autor(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $autor = $this->criarUtilizador();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->get(
            route(
                'metal-thursday.criar',
            ),
        )
            ->assertOk()
            ->assertSeeHtml(
                'placeholder="Seleciona o autor"',
            )
            ->assertSee(
                $autor->nome,
            );
    }

    /**
     * Confirma que um utilizador comum recebe a data da reserva pendente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_criacao_utilizador_comum_fixa_data_da_reserva(): void
    {
        $utilizador = $this->criarUtilizador();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this->get(
            route(
                'metal-thursday.criar',
            ),
        )
            ->assertOk()
            ->assertSeeHtml(
                'value="2026-01-15"',
            )
            ->assertSeeHtml(
                'aria-readonly="true"',
            )
            ->assertSee(
                'A data corresponde à tua reserva pendente e não pode ser alterada.',
            );
    }

    /**
     * Confirma que um administrador pode escolher a data na criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_criacao_administrador_permite_alterar_data(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->get(
            route(
                'metal-thursday.criar',
            ),
        )
            ->assertOk()
            ->assertDontSeeHtml(
                'aria-readonly="true"',
            )
            ->assertDontSee(
                'A data é definida automaticamente como a data atual.',
            );
    }

    /**
     * Confirma que um utilizador comum não pode falsificar a data reservada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_nao_pode_criar_fora_da_data_reservada(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador();
        $proximoNomeado = $this->criarUtilizador();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'data' => '2026-01-16',

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
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data',
            ])
            ->assertJsonPath(
                'errors.data.0',
                'A data da MetalThursday deve corresponder à data da tua reserva pendente.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-16',
            ],
        );
    }

    /**
     * Confirma que utilizadores suspensos não são apresentados nas opções
     * disponíveis ao administrador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_criacao_nao_apresenta_utilizador_suspenso(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $utilizadorAtivo = Utilizador::factory()
            ->create([
                'nome' => 'Utilizador Ativo',
            ]);

        $utilizadorSuspenso = Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
            )
            ->create([
                'nome' => 'Utilizador Suspenso',
            ]);

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->get(
            route(
                'metal-thursday.criar',
            ),
        )
            ->assertOk()
            ->assertSee(
                $utilizadorAtivo->nome,
            )
            ->assertDontSee(
                $utilizadorSuspenso->nome,
            );
    }

    /**
     * Confirma que um administrador não pode definir o superadministrador
     * como autor através de um pedido manipulado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_pode_definir_superadministrador_como_autor(): void
    {
        Notification::fake();

        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'data' => '2026-01-19',

                'nome' => null,

                'autor_id' => $superAdministrador->getKey(),

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
                'O autor selecionado não existe ou não está disponível.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-19',
            ],
        );
    }

    /**
     * Confirma que o superadministrador não pode ser definido como próximo
     * nomeado através de um pedido manipulado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_pode_definir_superadministrador_como_proximo_nomeado(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador();

        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'data' => '2026-01-21',

                'nome' => null,

                'autor_id' => $utilizador->getKey(),

                'proximo_nomeado_id' => $superAdministrador->getKey(),

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
                'proximo_nomeado_id',
            ])
            ->assertJsonPath(
                'errors.proximo_nomeado_id.0',
                'O próximo nomeado selecionado não existe ou não está disponível.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-21',
            ],
        );
    }

    /**
     * Confirma que um administrador não pode definir um utilizador suspenso
     * como autor através de um pedido manipulado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_pode_definir_utilizador_suspenso_como_autor(): void
    {
        Notification::fake();

        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $autorSuspenso = Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
            )
            ->create();

        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'data' => '2026-01-22',

                'nome' => null,

                'autor_id' => $autorSuspenso->getKey(),

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
                'O autor selecionado não existe ou não está disponível.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-22',
            ],
        );
    }

    /**
     * Confirma que um utilizador suspenso não pode ser definido como próximo
     * nomeado através de um pedido manipulado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_pode_definir_utilizador_suspenso_como_proximo_nomeado(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador();

        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        $proximoNomeadoSuspenso = Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
            )
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'data' => '2026-01-23',

                'nome' => null,

                'autor_id' => $utilizador->getKey(),

                'proximo_nomeado_id' => $proximoNomeadoSuspenso->getKey(),

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
                'proximo_nomeado_id',
            ])
            ->assertJsonPath(
                'errors.proximo_nomeado_id.0',
                'O próximo nomeado selecionado não existe ou não está disponível.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-23',
            ],
        );
    }

    /**
     * Confirma que um utilizador comum não pode falsificar o autor.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_nao_pode_criar_metal_thursday_em_nome_de_outro(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador();
        $autorFalsificado = $this->criarUtilizador();
        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'edicao_id' => $edicao->getKey(),

                'data' => '2026-01-17',

                'nome' => null,

                'autor_id' => $autorFalsificado->getKey(),

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
                'Não tens permissão para definir este autor.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-17',
            ],
        );
    }

    /**
     * Confirma que um utilizador comum não pode alterar a data durante a edição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_nao_pode_alterar_data_na_edicao(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador();
        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-18',
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $utilizador,
            )
            ->comProximoNomeado(
                $proximoNomeado,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            [
                'data' => '2026-01-19',

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
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data',
            ])
            ->assertJsonPath(
                'errors.data.0',
                'Não tens permissão para alterar a data da MetalThursday.',
            );

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'data' => '2026-01-18',
            ],
        );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'data' => '2026-01-19',
            ],
        );
    }

    /**
     * Confirma que um utilizador comum não pode alterar o autor durante a edição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_nao_pode_alterar_autor_na_edicao(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador();
        $autorFalsificado = $this->criarUtilizador();
        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-18',
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $utilizador,
            )
            ->comProximoNomeado(
                $proximoNomeado,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            [
                'edicao_id' => $edicao->getKey(),

                'data' => '2026-01-18',

                'nome' => null,

                'autor_id' => $autorFalsificado->getKey(),

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
                'Não tens permissão para definir este autor.',
            );

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'autor_id' => $utilizador->getKey(),
            ],
        );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'autor_id' => $autorFalsificado->getKey(),
            ],
        );
    }

    /**
     * Confirma que é possível criar uma MetalThursday com uma secção simples.
     *
     * Este teste garante a coerência da chave `tipo_seccao_id` entre o
     * formulário, a validação HTTP e o serviço de persistência.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_metal_thursday_com_seccao_simples(): void
    {
        Notification::fake();

        $utilizador = $this->criarUtilizador();

        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $dataReserva = CarbonImmutable::parse(
            '2026-01-15',
        );

        $reserva = ReservaMetalThursday::factory()
            ->comData(
                $dataReserva,
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        $edicao = $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->post(
            route(
                'metal-thursday.guardar',
            ),
            [
                'data' => $dataReserva->format(
                    'Y-m-d',
                ),

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
            ->assertRedirectToRoute(
                'inicio',
            )
            ->assertSessionHas(
                'sucesso',
                'MetalThursday criada com sucesso.',
            );

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'edicao_id' => $edicao->getKey(),

                'data' => $dataReserva->format(
                    'Y-m-d',
                ),
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'tipo_seccao_id' => $tipoSeccao->getKey(),

                'descricao' => 'Secção de teste.',

                'deleted_at' => null,
            ],
        );

        $metalThursday = MetalThursday::query()
            ->where(
                'data',
                $dataReserva->format(
                    'Y-m-d',
                ),
            )
            ->firstOrFail();

        self::assertSame(
            $metalThursday->getKey(),
            $reserva
                ->refresh()
                ->metal_thursday_id,
        );
    }

    /**
     * Confirma que um identificador de edição manipulado pelo cliente é
     * ignorado e substituído pela edição correspondente à data.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_ignora_edicao_enviada_e_determina_edicao_pela_data(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();
        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicaoJaneiro = $this->criarEdicao();

        $edicaoFevereiro = Edicao::factory()
            ->comNome(
                'Edição de fevereiro',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-02-01',
                ),
                CarbonImmutable::parse(
                    '2026-02-28',
                ),
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'edicao_id' => $edicaoFevereiro->getKey(),

                'data' => '2026-01-20',

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
            ->assertCreated();

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'data' => '2026-01-20',

                'edicao_id' => $edicaoJaneiro->getKey(),
            ],
        );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-20',

                'edicao_id' => $edicaoFevereiro->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma data sem edição correspondente impede a criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_criacao_quando_data_nao_pertence_a_nenhuma_edicao(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();
        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'edicao_id' => 999999,

                'data' => '2026-05-15',

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
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data',
            ])
            ->assertJsonPath(
                'errors.data.0',
                'Não existe nenhuma edição que inclua a data selecionada.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-05-15',
            ],
        );
    }

    /**
     * Confirma que alterar a data durante a edição também altera
     * automaticamente a edição associada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_determina_nova_edicao_pela_data(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();
        $proximoNomeado = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicaoJaneiro = $this->criarEdicao();

        $edicaoFevereiro = Edicao::factory()
            ->comNome(
                'Edição de fevereiro',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-02-01',
                ),
                CarbonImmutable::parse(
                    '2026-02-28',
                ),
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-10',
                ),
            )
            ->comEdicao(
                $edicaoJaneiro,
            )
            ->comAutor(
                $utilizador,
            )
            ->comProximoNomeado(
                $proximoNomeado,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            [
                'edicao_id' => $edicaoJaneiro->getKey(),

                'data' => '2026-02-12',

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
            ->assertOk();

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'data' => '2026-02-12',

                'edicao_id' => $edicaoFevereiro->getKey(),
            ],
        );
    }

    /**
     * Confirma que o próximo nomeado não pode coincidir com o autor.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_proximo_nomeado_igual_ao_autor_na_criacao(): void
    {
        Notification::fake();

        $utilizador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $this->postJson(
            route(
                'metal-thursday.guardar',
            ),
            [
                'edicao_id' => $edicao->getKey(),

                'data' => '2026-01-16',

                'nome' => null,

                'autor_id' => $utilizador->getKey(),

                'proximo_nomeado_id' => $utilizador->getKey(),

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
                'proximo_nomeado_id',
            ])
            ->assertJsonPath(
                'errors.proximo_nomeado_id.0',
                'O próximo nomeado deve ser diferente do autor.',
            );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'data' => '2026-01-16',
            ],
        );
    }

    /**
     * Confirma que a vista completa apresenta a posição de cada registo na
     * edição sem depender de um accessor com consultas implícitas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function listagem_completa_apresenta_numero_semana_na_edicao(): void
    {
        $utilizador = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-01',
        );

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-08',
        );

        $this->get(
            route(
                'inicio',
            ),
        )
            ->assertOk()
            ->assertSee(
                'Semana 1',
            )
            ->assertSee(
                'Semana 2',
            );
    }

    /**
     * Confirma que a página de detalhes carrega explicitamente a posição na
     * edição e reutiliza o valor durante toda a renderização.
     *
     * @since 2.0.0
     */
    #[Test]
    public function detalhes_apresentam_numero_semana_na_edicao(): void
    {
        $utilizador = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = $this->criarEdicao();

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-01',
        );

        $segunda = $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-08',
        );

        $this->get(
            route(
                'metal-thursday.detalhes',
                $segunda,
            ),
        )
            ->assertOk()
            ->assertSee(
                'Semana 2',
            );
    }

    /**
     * Confirma que o utilizador nunca nomeado com precedência alfabética é
     * apresentado antes dos utilizadores já nomeados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_utilizador_ha_mais_tempo_sem_nomeacao(): void
    {
        $utilizadorAutenticado = Utilizador::factory()
            ->create([
                'nome' => 'Zelda',
            ]);

        $primeiroNuncaNomeado = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        $utilizadorJaNomeado = Utilizador::factory()
            ->create([
                'nome' => 'Carlos',
            ]);

        $this->actingAs(
            $utilizadorAutenticado,
            'sessao',
        );

        $this->criarMetalThursday(
            $this->criarEdicao(),
            $utilizadorJaNomeado,
            '2026-01-01',
        );

        $this
            ->getJson(
                route(
                    'utilizadores.ha-mais-tempo-sem-nomeacao',
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'identificador',
                $primeiroNuncaNomeado->getKey(),
            );
    }

    /**
     * Confirma que o autor é excluído da sugestão do nomeado mais antigo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_utilizador_ha_mais_tempo_sem_nomeacao_exclui_autor(): void
    {
        $utilizadorAutenticado = Utilizador::factory()
            ->create([
                'nome' => 'Zelda',
            ]);

        $autor = Utilizador::factory()
            ->create([
                'nome' => 'Ana',
            ]);

        $proximoDisponivel = Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        $this->actingAs(
            $utilizadorAutenticado,
            'sessao',
        );

        $this
            ->getJson(
                route(
                    'utilizadores.ha-mais-tempo-sem-nomeacao',
                    [
                        'excluir_utilizador_id' => $autor->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'identificador',
                $proximoDisponivel->getKey(),
            );
    }

    /**
     * Confirma que um utilizador suspenso é ignorado na sugestão do nomeado
     * há mais tempo sem nomeação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_utilizador_ha_mais_tempo_sem_nomeacao_ignora_suspenso(): void
    {
        $utilizadorAutenticado = Utilizador::factory()
            ->create([
                'nome' => 'Zelda',
            ]);

        $superAdministrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::SuperAdministrador,
            )
            ->create();

        Utilizador::factory()
            ->suspensoPor(
                $superAdministrador,
            )
            ->create([
                'nome' => 'Ana',
            ]);

        $proximoDisponivel = Utilizador::factory()
            ->create([
                'nome' => 'Beatriz',
            ]);

        $this->actingAs(
            $utilizadorAutenticado,
            'sessao',
        );

        $this
            ->getJson(
                route(
                    'utilizadores.ha-mais-tempo-sem-nomeacao',
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'identificador',
                $proximoDisponivel->getKey(),
            );
    }

    /**
     * Confirma que um utilizador sem autorização não elimina a
     * MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_sem_autorizacao_nao_elimina_metal_thursday(): void
    {
        $criador = $this->criarUtilizador();
        $outroUtilizador = $this->criarUtilizador();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday = $this->criarMetalThursday(
            $this->criarEdicao(),
            $criador,
            '2026-01-01',
        );

        $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'metal-thursday.eliminar',
                    $metalThursday,
                ),
            )
            ->assertForbidden();

        $this->assertNotSoftDeleted(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),
            ],
        );
    }

    /**
     * Confirma que o criador pode eliminar logicamente a MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criador_elimina_metal_thursday(): void
    {
        $criador = $this->criarUtilizador();

        $this->actingAs(
            $criador,
            'sessao',
        );

        $metalThursday = $this->criarMetalThursday(
            $this->criarEdicao(),
            $criador,
            '2026-01-01',
        );

        $this
            ->deleteJson(
                route(
                    'metal-thursday.eliminar',
                    $metalThursday,
                ),
            )
            ->assertNoContent();

        $this->assertSoftDeleted(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),
            ],
        );
    }

    /**
     * Cria um utilizador autenticável e verificado.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     */
    private function criarUtilizador(): Utilizador
    {
        return Utilizador::factory()
            ->create();
    }

    /**
     * Cria a edição utilizada nos testes.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicao(): Edicao
    {
        return Edicao::factory()
            ->comNome(
                'Edição de Teste',
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

    /**
     * Cria uma MetalThursday associada à edição e ao utilizador indicados.
     *
     * @param  Edicao  $edicao  Edição relacionada.
     * @param  Utilizador  $utilizador  Autor e próximo nomeado.
     * @param  string  $data  Data no formato AAAA-MM-DD.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
        Utilizador $utilizador,
        string $data,
    ): MetalThursday {
        return MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    $data,
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $utilizador,
            )
            ->comProximoNomeado(
                $utilizador,
            )
            ->create();
    }
}
