<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa as principais operações HTTP de escrita das edições.
 *
 * @since 2.0.0
 */
final class ControladorEdicaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que um administrador cria uma edição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_cria_edicao(): void
    {
        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.guardar',
                ),
                [
                    'nome' => '  Edição   criada  ',

                    'data_inicio' => '2026-03-01',

                    'data_fim' => '',
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'mensagem',
                'Edição criada com sucesso.',
            )
            ->assertJsonPath(
                'edicao.nome',
                'Edição criada',
            )
            ->assertJsonPath(
                'edicao.data_inicio',
                '2026-03-01',
            )
            ->assertJsonPath(
                'edicao.data_fim',
                null,
            );

        $this->assertDatabaseHas(
            'edicoes',
            [
                'nome' => 'Edição criada',

                'data_inicio' => '2026-03-01',

                'data_fim' => null,

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que um período não pode partilhar a data final de outra edição.
     *
     * As datas dos períodos são inclusivas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_cria_edicao_com_periodo_sobreposto(): void
    {
        $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.guardar',
                ),
                [
                    'nome' => 'Edição sobreposta',

                    'data_inicio' => '2026-01-31',

                    'data_fim' => '2026-02-28',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data_inicio',
            ])
            ->assertJsonPath(
                'errors.data_inicio.0',
                'O período da edição sobrepõe-se ao período de outra edição.',
            );

        $this->assertDatabaseMissing(
            'edicoes',
            [
                'nome' => 'Edição sobreposta',
            ],
        );
    }

    /**
     * Confirma que uma edição pode começar no dia seguinte ao fim da anterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_cria_edicao_imediatamente_apos_periodo_existente(): void
    {
        $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.guardar',
                ),
                [
                    'nome' => 'Edição de fevereiro',

                    'data_inicio' => '2026-02-01',

                    'data_fim' => '2026-02-28',
                ],
            )
            ->assertCreated();

        $this->assertDatabaseHas(
            'edicoes',
            [
                'nome' => 'Edição de fevereiro',

                'data_inicio' => '2026-02-01',

                'data_fim' => '2026-02-28',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que uma edição aberta impede a criação de uma edição posterior.
     *
     * @since 2.0.0
     */
    #[Test]
    public function edicao_aberta_impede_criacao_de_edicao_posterior(): void
    {
        Edicao::factory()
            ->comNome(
                'Edição aberta',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
            )
            ->create();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.guardar',
                ),
                [
                    'nome' => 'Edição posterior',

                    'data_inicio' => '2026-03-01',

                    'data_fim' => '2026-03-31',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data_inicio',
            ])
            ->assertJsonPath(
                'errors.data_inicio.0',
                'O período da edição sobrepõe-se ao período de outra edição.',
            );

        $this->assertDatabaseMissing(
            'edicoes',
            [
                'nome' => 'Edição posterior',
            ],
        );
    }

    /**
     * Confirma que uma nova edição aberta não pode abranger uma edição futura.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_cria_edicao_aberta_que_abrange_edicao_futura(): void
    {
        Edicao::factory()
            ->comNome(
                'Edição futura',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-03-01',
                ),
                CarbonImmutable::parse(
                    '2026-03-31',
                ),
            )
            ->create();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.guardar',
                ),
                [
                    'nome' => 'Edição aberta',

                    'data_inicio' => '2026-02-01',

                    'data_fim' => null,
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data_inicio',
            ])
            ->assertJsonPath(
                'errors.data_inicio.0',
                'O período da edição sobrepõe-se ao período de outra edição.',
            );

        $this->assertDatabaseMissing(
            'edicoes',
            [
                'nome' => 'Edição aberta',
            ],
        );
    }

    /**
     * Confirma que um administrador atualiza os dados principais da edição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_atualiza_edicao(): void
    {
        $edicao = $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->patchJson(
                route(
                    'edicoes.atualizar',
                    $edicao,
                ),
                [
                    'nome' => '  Edição   atualizada  ',

                    'data_inicio' => '2026-02-01',

                    'data_fim' => '2026-02-28',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'edicao.nome',
                'Edição atualizada',
            )
            ->assertJsonPath(
                'edicao.data_inicio',
                '2026-02-01',
            )
            ->assertJsonPath(
                'edicao.data_fim',
                '2026-02-28',
            );

        $this->assertDatabaseHas(
            'edicoes',
            [
                'id' => $edicao->getKey(),

                'nome' => 'Edição atualizada',

                'data_inicio' => '2026-02-01',

                'data_fim' => '2026-02-28',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que uma edição não pode ser atualizada para sobrepor outra.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_atualiza_edicao_para_periodo_sobreposto(): void
    {
        $this->criarEdicao();

        $edicaoMarco = Edicao::factory()
            ->comNome(
                'Edição de março',
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-03-01',
                ),
                CarbonImmutable::parse(
                    '2026-03-31',
                ),
            )
            ->create();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->patchJson(
                route(
                    'edicoes.atualizar',
                    $edicaoMarco,
                ),
                [
                    'nome' => 'Edição de março',

                    'data_inicio' => '2026-01-15',

                    'data_fim' => '2026-02-15',
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'data_inicio',
            ])
            ->assertJsonPath(
                'errors.data_inicio.0',
                'O período da edição sobrepõe-se ao período de outra edição.',
            );

        $this->assertDatabaseHas(
            'edicoes',
            [
                'id' => $edicaoMarco->getKey(),

                'data_inicio' => '2026-03-01',

                'data_fim' => '2026-03-31',
            ],
        );
    }

    /**
     * Confirma que um administrador guarda as músicas favoritas de um
     * utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_guarda_musicas_favoritas(): void
    {
        $edicao = $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $utilizador = Utilizador::factory()
            ->create();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                route(
                    'edicoes.musicas-favoritas.guardar',
                    $edicao,
                ),
                [
                    'musicas_favoritas' => [
                        $utilizador->getKey() => [
                            "  Banda\t—\nPrimeira música  ",
                            'Segunda música',
                            '',
                        ],
                    ],
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'mensagem',
                'Músicas favoritas guardadas com sucesso.',
            );

        $this->assertDatabaseCount(
            'musicas_favoritas_edicao',
            2,
        );

        $this->assertDatabaseHas(
            'musicas_favoritas_edicao',
            [
                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $utilizador->getKey(),

                'posicao' => 1,

                'musica' => 'Banda — Primeira música',

                'registado_por_id' => $administrador->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'musicas_favoritas_edicao',
            [
                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $utilizador->getKey(),

                'posicao' => 2,

                'musica' => 'Segunda música',

                'registado_por_id' => $administrador->getKey(),
            ],
        );

        $this->assertDatabaseMissing(
            'musicas_favoritas_edicao',
            [
                'edicao_id' => $edicao->getKey(),

                'utilizador_id' => $utilizador->getKey(),

                'posicao' => 3,
            ],
        );
    }

    /**
     * Confirma que um utilizador comum não elimina uma edição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_nao_elimina_edicao(): void
    {
        $edicao = $this->criarEdicao();

        $utilizador = Utilizador::factory()
            ->create([
                'papel' => PapelUtilizador::Utilizador,
            ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'edicoes.eliminar',
                    $edicao,
                ),
            )
            ->assertForbidden();

        $this->assertNotSoftDeleted(
            'edicoes',
            [
                'id' => $edicao->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma edição sem MetalThursdays pode ser eliminada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_elimina_edicao_vazia(): void
    {
        $edicao = $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'edicoes.eliminar',
                    $edicao,
                ),
            )
            ->assertNoContent();

        $this->assertSoftDeleted(
            'edicoes',
            [
                'id' => $edicao->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma edição com MetalThursdays não pode ser eliminada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_nao_elimina_edicao_com_metal_thursdays(): void
    {
        $edicao = $this->criarEdicao();

        MetalThursday::factory()
            ->comEdicao(
                $edicao,
            )
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-08',
                ),
            )
            ->create();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'edicoes.eliminar',
                    $edicao,
                ),
            )
            ->assertConflict()
            ->assertJsonPath(
                'mensagem',
                'A edição não pode ser eliminada enquanto possuir MetalThursdays.',
            );

        $this->assertNotSoftDeleted(
            'edicoes',
            [
                'id' => $edicao->getKey(),
            ],
        );
    }

    /**
     * Confirma que um administrador atualiza a ligação da compilação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function administrador_atualiza_ligacao_compilacao(): void
    {
        $edicao = $this->criarEdicao();

        $administrador = $this->criarAdministrador();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->patchJson(
                route(
                    'edicoes.ligacao-compilacao.atualizar',
                    $edicao,
                ),
                [
                    'ligacao_compilacao' => 'https://example.com/compilacao',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'ligacao_compilacao',
                'https://example.com/compilacao',
            );

        $this->assertDatabaseHas(
            'edicoes',
            [
                'id' => $edicao->getKey(),

                'ligacao_compilacao' => 'https://example.com/compilacao',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Cria uma edição com um período conhecido.
     *
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicao(): Edicao
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

    /**
     * Cria um utilizador com privilégios administrativos.
     *
     * @return Utilizador Administrador criado.
     *
     * @since 2.0.0
     */
    private function criarAdministrador(): Utilizador
    {
        return Utilizador::factory()
            ->create([
                'papel' => PapelUtilizador::Administrador,
            ]);
    }
}
