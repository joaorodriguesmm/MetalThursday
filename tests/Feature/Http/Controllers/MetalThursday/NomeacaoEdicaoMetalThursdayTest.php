<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a imutabilidade da nomeação efetiva na edição de MetalThursdays.
 *
 * @since 2.0.0
 */
final class NomeacaoEdicaoMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a criação continua a disponibilizar o seletor de nomeação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_mantem_seletor_de_proximo_nomeado(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        Utilizador::factory()
            ->create([
                'nome' => 'Nomeado disponível R7.3b',
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
            ->assertSeeHtml(
                'name="proximo_nomeado_id"',
            )
            ->assertSeeHtml(
                'id="botao-selecionar-nomeado-aleatorio"',
            )
            ->assertSeeHtml(
                'id="botao-selecionar-nomeado-mais-antigo"',
            );
    }

    /**
     * Confirma que a edição apresenta a nomeação efetiva apenas como
     * informação e não volta a submetê-la.
     *
     * @since 2.0.0
     */
    #[Test]
    public function edicao_apresenta_nomeado_efetivo_sem_campo_editavel(): void
    {
        [
            'administrador' => $administrador,
            'metalThursday' => $metalThursday,
        ] = $this->criarContextoEdicao();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->get(
            route(
                'metal-thursday.editar',
                $metalThursday,
            ),
        )
            ->assertOk()
            ->assertSee(
                'Nomeado efetivo R7.3b',
            )
            ->assertSee(
                'A nomeação desta MetalThursday já está definida pela reserva seguinte e não pode ser alterada.',
            )
            ->assertDontSeeHtml(
                'name="proximo_nomeado_id"',
            )
            ->assertDontSeeHtml(
                'id="botao-selecionar-nomeado-aleatorio"',
            )
            ->assertDontSeeHtml(
                'id="botao-selecionar-nomeado-mais-antigo"',
            );
    }

    /**
     * Confirma que o formulário de edição pode ser submetido sem reenviar o
     * próximo nomeado e que a persistência conserva o espelho existente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_sem_campo_de_nomeacao_preserva_nomeado(): void
    {
        [
            'administrador' => $administrador,
            'metalThursday' => $metalThursday,
            'tipoSeccao' => $tipoSeccao,
            'seccao' => $seccao,
            'nomeado' => $nomeado,
        ] = $this->criarContextoEdicao();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            [
                'data' => '2026-01-08',

                'nome' => 'Nome atualizado',

                'autor_id' => $administrador->getKey(),

                'seccoes' => [
                    [
                        'id' => $seccao->getKey(),

                        'tipo_seccao_id' => $tipoSeccao->getKey(),

                        'descricao' => 'Descrição atualizada.',
                    ],
                ],
            ],
        )
            ->assertOk();

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'nome' => 'Nome atualizado',

                'proximo_nomeado_id' => $nomeado->getKey(),
            ],
        );
    }

    /**
     * Confirma que um pedido manipulado não consegue substituir a nomeação
     * efetiva depois da publicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_rejeita_alteracao_do_proximo_nomeado(): void
    {
        [
            'administrador' => $administrador,
            'metalThursday' => $metalThursday,
            'tipoSeccao' => $tipoSeccao,
            'seccao' => $seccao,
            'nomeado' => $nomeado,
        ] = $this->criarContextoEdicao();

        $novoNomeado = Utilizador::factory()
            ->create();

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            [
                'data' => '2026-01-08',

                'nome' => null,

                'autor_id' => $administrador->getKey(),

                'proximo_nomeado_id' => $novoNomeado->getKey(),

                'seccoes' => [
                    [
                        'id' => $seccao->getKey(),

                        'tipo_seccao_id' => $tipoSeccao->getKey(),

                        'descricao' => 'Descrição válida.',
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
                'O próximo nomeado não pode ser alterado depois da publicação.',
            );

        $this->assertDatabaseHas(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'proximo_nomeado_id' => $nomeado->getKey(),
            ],
        );

        $this->assertDatabaseMissing(
            'metal_thursdays',
            [
                'id' => $metalThursday->getKey(),

                'proximo_nomeado_id' => $novoNomeado->getKey(),
            ],
        );
    }

    /**
     * Cria um contexto coerente com uma MetalThursday publicada e a respetiva
     * reserva seguinte.
     *
     * @return array{
     *     administrador: Utilizador,
     *     nomeado: Utilizador,
     *     metalThursday: MetalThursday,
     *     tipoSeccao: TipoSeccao,
     *     seccao: SeccaoMetalThursday
     * } Contexto criado.
     *
     * @since 2.0.0
     */
    private function criarContextoEdicao(): array
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create();

        $nomeado = Utilizador::factory()
            ->create([
                'nome' => 'Nomeado efetivo R7.3b',
            ]);

        $edicao = Edicao::factory()
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

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-08',
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $administrador,
            )
            ->comProximoNomeado(
                $nomeado,
            )
            ->create();

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comResponsavel(
                $nomeado,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->semDetalhes()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->comConteudo(
                'Descrição válida.',
            )
            ->create();

        return [
            'administrador' => $administrador,

            'nomeado' => $nomeado,

            'metalThursday' => $metalThursday,

            'tipoSeccao' => $tipoSeccao,

            'seccao' => $seccao,
        ];
    }
}
