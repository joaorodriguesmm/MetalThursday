<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a visibilidade temporal do histórico de um artista.
 *
 * Apenas secções pertencentes a MetalThursdays já publicadas podem surgir no
 * histórico público do artista. Conteúdo preparado para datas futuras permanece
 * oculto até à respetiva publicação.
 *
 * @since 2.0.0
 */
final class VisibilidadeHistoricoArtistaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara o teste com uma referência temporal determinística.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->travelTo(
            CarbonImmutable::parse(
                '2026-08-27 12:00:00',
                'Europe/Lisbon',
            ),
        );
    }

    /**
     * Confirma que o histórico do artista apresenta passado e dia atual, mas
     * exclui secções pertencentes a uma MetalThursday preparada futura.
     *
     * @since 2.0.0
     */
    #[Test]
    public function historico_apresenta_apenas_metal_thursdays_publicadas(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista =
            Artista::factory()
                ->create();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-09-30',
                    ),
                )
                ->create();

        $publicadaPassada =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-08-20',
            );

        $publicadaHoje =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-08-27',
            );

        $preparadaFutura =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-09-03',
            );

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $seccaoPassada =
            $this->criarSeccao(
                $publicadaPassada,
                $tipoSeccao,
                $artista,
                'Secção histórica passada',
            );

        $seccaoHoje =
            $this->criarSeccao(
                $publicadaHoje,
                $tipoSeccao,
                $artista,
                'Secção histórica atual',
            );

        $seccaoFutura =
            $this->criarSeccao(
                $preparadaFutura,
                $tipoSeccao,
                $artista,
                'Secção futura privada',
            );

        $resposta =
            $this->get(
                route(
                    'artistas.detalhes',
                    $artista,
                ),
            );

        $resposta->assertOk();

        $paginador =
            $resposta->viewData(
                'seccoes',
            );

        self::assertInstanceOf(
            LengthAwarePaginator::class,
            $paginador,
        );

        $identificadores =
            $paginador
                ->getCollection()
                ->modelKeys();

        self::assertContains(
            (int) $seccaoPassada->getKey(),
            $identificadores,
        );

        self::assertContains(
            (int) $seccaoHoje->getKey(),
            $identificadores,
        );

        self::assertNotContains(
            (int) $seccaoFutura->getKey(),
            $identificadores,
        );
    }

    /**
     * Confirma que o endpoint contextual apresenta apenas aparições publicadas e
     * exclui explicitamente a MetalThursday atualmente editada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function endpoint_contextual_exclui_metal_thursday_atual(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista =
            Artista::factory()
                ->create();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-09-30',
                    ),
                )
                ->create();

        $publicadaAnterior =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-08-20',
            );

        $publicadaAtual =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-08-27',
            );

        $preparadaFutura =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-09-03',
            );

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $seccaoAnterior =
            $this->criarSeccao(
                $publicadaAnterior,
                $tipoSeccao,
                $artista,
                'Aparição anterior',
            );

        $seccaoAtual =
            $this->criarSeccao(
                $publicadaAtual,
                $tipoSeccao,
                $artista,
                'Aparição atual excluída',
            );

        $seccaoFutura =
            $this->criarSeccao(
                $preparadaFutura,
                $tipoSeccao,
                $artista,
                'Aparição futura privada',
            );

        $resposta =
            $this->getJson(
                route(
                    'artistas.aparicoes-metal-thursday',
                    [
                        'identificadorArtista' => $artista->getKey(),

                        'metal_thursday_excluida' => $publicadaAtual->getKey(),
                    ],
                ),
            );

        $resposta
            ->assertOk()
            ->assertJsonCount(
                1,
                'aparicoes',
            )
            ->assertJsonPath(
                'aparicoes.0.identificador',
                (int) $seccaoAnterior->getKey(),
            )
            ->assertJsonPath(
                'aparicoes.0.tipo',
                $tipoSeccao->nome,
            )
            ->assertJsonPath(
                'aparicoes.0.titulo',
                'Aparição anterior',
            )
            ->assertJsonPath(
                'aparicoes.0.ano',
                $seccaoAnterior->ano,
            )
            ->assertJsonPath(
                'aparicoes.0.autor',
                $utilizador->nome,
            )
            ->assertJsonPath(
                'aparicoes.0.data',
                '2026-08-20',
            )
            ->assertJsonPath(
                'aparicoes.0.endereco_metal_thursday',
                route(
                    'metal-thursday.detalhes',
                    $publicadaAnterior,
                ),
            )
            ->assertJsonMissing([
                'identificador' => (int) $seccaoAtual->getKey(),
            ])
            ->assertJsonMissing([
                'identificador' => (int) $seccaoFutura->getKey(),
            ]);
    }

    /**
     * Confirma que um artista sem aparições publicadas devolve uma lista vazia.
     *
     * @since 2.0.0
     */
    #[Test]
    public function endpoint_contextual_devolve_lista_vazia_sem_historico(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista =
            Artista::factory()
                ->create();

        $this
            ->getJson(
                route(
                    'artistas.aparicoes-metal-thursday',
                    [
                        'identificadorArtista' => $artista->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertExactJson([
                'aparicoes' => [],
            ]);
    }

    /**
     * Confirma que o endpoint contextual rejeita um identificador de exclusão
     * inválido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function endpoint_contextual_rejeita_identificador_exclusao_invalido(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista =
            Artista::factory()
                ->create();

        $this
            ->getJson(
                route(
                    'artistas.aparicoes-metal-thursday',
                    [
                        'identificadorArtista' => $artista->getKey(),

                        'metal_thursday_excluida' => 'invalida',
                    ],
                ),
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'metal_thursday_excluida',
            ]);
    }

    /**
     * Confirma que o contexto de edição continua a disponibilizar o histórico
     * de um artista eliminado que já pertence à MetalThursday editada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function endpoint_contextual_permite_artista_eliminado_ja_associado(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista =
            Artista::factory()
                ->create();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-09-30',
                    ),
                )
                ->create();

        $publicadaAnterior =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-08-20',
            );

        $metalThursdayEditada =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-09-03',
            );

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $seccaoAnterior =
            $this->criarSeccao(
                $publicadaAnterior,
                $tipoSeccao,
                $artista,
                'Aparição histórica preservada',
            );

        $this->criarSeccao(
            $metalThursdayEditada,
            $tipoSeccao,
            $artista,
            'Aparição atualmente editada',
        );

        $artista->deleteOrFail();

        $this
            ->getJson(
                route(
                    'artistas.aparicoes-metal-thursday',
                    [
                        'identificadorArtista' => $artista->getKey(),

                        'metal_thursday_excluida' => $metalThursdayEditada->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'aparicoes',
            )
            ->assertJsonPath(
                'aparicoes.0.identificador',
                (int) $seccaoAnterior->getKey(),
            )
            ->assertJsonPath(
                'aparicoes.0.titulo',
                'Aparição histórica preservada',
            );
    }

    /**
     * Confirma que uma MetalThursday diferente não pode ser usada para
     * consultar o contexto histórico de um artista eliminado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function endpoint_contextual_rejeita_artista_eliminado_nao_associado(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista =
            Artista::factory()
                ->create();

        $outroArtista =
            Artista::factory()
                ->create();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    CarbonImmutable::parse(
                        '2026-08-01',
                    ),
                    CarbonImmutable::parse(
                        '2026-09-30',
                    ),
                )
                ->create();

        $publicadaAnterior =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-08-20',
            );

        $metalThursdayDiferente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-09-03',
            );

        $tipoSeccao =
            TipoSeccao::factory()
                ->comDetalhes()
                ->create();

        $this->criarSeccao(
            $publicadaAnterior,
            $tipoSeccao,
            $artista,
            'Histórico do artista eliminado',
        );

        $this->criarSeccao(
            $metalThursdayDiferente,
            $tipoSeccao,
            $outroArtista,
            'Secção de outro artista',
        );

        $artista->deleteOrFail();

        $this
            ->getJson(
                route(
                    'artistas.aparicoes-metal-thursday',
                    [
                        'identificadorArtista' => $artista->getKey(),

                        'metal_thursday_excluida' => $metalThursdayDiferente->getKey(),
                    ],
                ),
            )
            ->assertNotFound();
    }

    /**
     * Cria uma MetalThursday numa data determinada.
     *
     * @param  Edicao  $edicao  Edição associada.
     * @param  Utilizador  $autor  Autor associado.
     * @param  string  $data  Data pretendida.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
        Utilizador $autor,
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
                $autor,
            )
            ->create();
    }

    /**
     * Cria uma secção associada ao artista e à MetalThursday indicados.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday associada.
     * @param  TipoSeccao  $tipoSeccao  Tipo da secção.
     * @param  Artista  $artista  Artista associado.
     * @param  string  $titulo  Título identificável.
     * @return SeccaoMetalThursday Secção criada.
     *
     * @since 2.0.0
     */
    private function criarSeccao(
        MetalThursday $metalThursday,
        TipoSeccao $tipoSeccao,
        Artista $artista,
        string $titulo,
    ): SeccaoMetalThursday {
        return SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $artista,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->comConteudo(
                'Descrição de teste.',
                $titulo,
            )
            ->create();
    }
}
