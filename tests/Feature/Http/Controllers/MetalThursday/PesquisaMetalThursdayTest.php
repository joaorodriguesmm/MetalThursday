<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a pesquisa textual da listagem de MetalThursdays.
 *
 * @since 2.0.0
 */
final class PesquisaMetalThursdayTest extends TestCase
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
     * Confirma que a pesquisa encontra uma MetalThursday pelo respetivo nome.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_por_nome_da_metal_thursday(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-08',
            'Arquivo Obscuro',
        );

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-15',
            'Registo Secundário',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => 'arquivo',
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Arquivo Obscuro',
            )
            ->assertDontSee(
                'Registo Secundário',
            );
    }

    /**
     * Confirma que a pesquisa encontra uma MetalThursday pelo título de uma
     * das respetivas secções.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_por_titulo_da_seccao(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $metalThursdayCorrespondente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'Resultado pelo título',
            );

        $metalThursdayDiferente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-15',
                'Resultado diferente',
            );

        $this->criarSeccaoDetalhada(
            $metalThursdayCorrespondente,
            'World Painted Blood',
            'Descrição sem correspondência.',
        );

        $this->criarSeccaoDetalhada(
            $metalThursdayDiferente,
            'Outro lançamento',
            'Outro conteúdo.',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => 'painted blood',
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Resultado pelo título',
            )
            ->assertDontSee(
                'Resultado diferente',
            );
    }

    /**
     * Confirma que a pesquisa encontra uma MetalThursday pelo conteúdo da
     * descrição de uma secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_por_descricao_da_seccao(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $metalThursdayCorrespondente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'Resultado pela descrição',
            );

        $metalThursdayDiferente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-15',
                'Resultado sem descrição',
            );

        $this->criarSeccaoDetalhada(
            $metalThursdayCorrespondente,
            'Primeiro título',
            'Uma composição marcada por riffs glaciais e atmosfera densa.',
        );

        $this->criarSeccaoDetalhada(
            $metalThursdayDiferente,
            'Segundo título',
            'Uma descrição completamente diferente.',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => 'riffs glaciais',
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Resultado pela descrição',
            )
            ->assertDontSee(
                'Resultado sem descrição',
            );
    }

    /**
     * Confirma que a pesquisa encontra uma MetalThursday pelo nome parcial do
     * artista associado a uma secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_por_nome_do_artista(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $ironMaiden = Artista::factory()
            ->comNome(
                'Iron Maiden',
            )
            ->create();

        $judasPriest = Artista::factory()
            ->comNome(
                'Judas Priest',
            )
            ->create();

        $metalThursdayCorrespondente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'Resultado Iron',
            );

        $metalThursdayDiferente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-15',
                'Resultado Judas',
            );

        $this->criarSeccaoDetalhada(
            $metalThursdayCorrespondente,
            'Primeiro álbum',
            'Primeira descrição.',
            $ironMaiden,
        );

        $this->criarSeccaoDetalhada(
            $metalThursdayDiferente,
            'Segundo álbum',
            'Segunda descrição.',
            $judasPriest,
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => 'maiden',
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Resultado Iron',
            )
            ->assertDontSee(
                'Resultado Judas',
            );
    }

    /**
     * Confirma a normalização de espaços e a pesquisa sem distinção entre
     * capitalização e acentos.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_normaliza_espacos_capitalizacao_e_acentos(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $metalThursdayCorrespondente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'Resultado normalizado',
            );

        $metalThursdayDiferente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-15',
                'Resultado não correspondente',
            );

        $this->criarSeccaoDetalhada(
            $metalThursdayCorrespondente,
            'Música Épica Progressiva',
            'Descrição principal.',
        );

        $this->criarSeccaoDetalhada(
            $metalThursdayDiferente,
            'Música diferente',
            'Outra descrição.',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => '   musica   EPICA   ',
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Resultado normalizado',
            )
            ->assertDontSee(
                'Resultado não correspondente',
            );
    }

    /**
     * Confirma que o sinal de percentagem é pesquisado literalmente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_percentagem_como_caractere_literal(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $metalThursdayCorrespondente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'Resultado com percentagem',
            );

        $metalThursdayDiferente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-15',
                'Resultado sem percentagem',
            );

        $this->criarSeccaoDetalhada(
            $metalThursdayCorrespondente,
            'Primeiro conteúdo',
            'Heavy metal 100% tradicional.',
        );

        $this->criarSeccaoDetalhada(
            $metalThursdayDiferente,
            'Segundo conteúdo',
            'Heavy metal 100X tradicional.',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => '%',
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Resultado com percentagem',
            )
            ->assertDontSee(
                'Resultado sem percentagem',
            );
    }

    /**
     * Confirma que o carácter de sublinhado é pesquisado literalmente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_sublinhado_como_caractere_literal(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $metalThursdayCorrespondente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'Resultado com sublinhado',
            );

        $metalThursdayDiferente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-15',
                'Resultado sem sublinhado',
            );

        $this->criarSeccaoDetalhada(
            $metalThursdayCorrespondente,
            'Primeiro conteúdo',
            'Identificador especial metal_extremo.',
        );

        $this->criarSeccaoDetalhada(
            $metalThursdayDiferente,
            'Segundo conteúdo',
            'Identificador especial metalXextremo.',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => '_',
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Resultado com sublinhado',
            )
            ->assertDontSee(
                'Resultado sem sublinhado',
            );
    }

    /**
     * Confirma que um valor estruturado não é interpretado como pesquisa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_ignora_valor_estruturado(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-08',
            'Primeiro resultado existente',
        );

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-15',
            'Segundo resultado existente',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => [
                        'valor manipulado',
                    ],
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Primeiro resultado existente',
            )
            ->assertSee(
                'Segundo resultado existente',
            );
    }

    /**
     * Confirma que a pesquisa textual é acumulada com os filtros estruturados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_combina_com_filtro_de_edicao(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicaoJaneiro = $this->criarEdicao(
            'Edição de janeiro',
            '2026-01-01',
            '2026-01-31',
        );

        $edicaoFevereiro = $this->criarEdicao(
            'Edição de fevereiro',
            '2026-02-01',
            '2026-02-28',
        );

        $metalThursdayJaneiro =
            $this->criarMetalThursday(
                $edicaoJaneiro,
                $utilizador,
                '2026-01-08',
                'Resultado de janeiro',
            );

        $metalThursdayFevereiro =
            $this->criarMetalThursday(
                $edicaoFevereiro,
                $utilizador,
                '2026-02-05',
                'Resultado de fevereiro',
            );

        $this->criarSeccaoDetalhada(
            $metalThursdayJaneiro,
            'Tema comum de pesquisa',
            'Descrição de janeiro.',
        );

        $this->criarSeccaoDetalhada(
            $metalThursdayFevereiro,
            'Tema comum de pesquisa',
            'Descrição de fevereiro.',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => 'tema comum',
                    'filtro_edicao' => $edicaoJaneiro->getKey(),
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Resultado de janeiro',
            )
            ->assertDontSee(
                'Resultado de fevereiro',
            );
    }

    /**
     * Confirma que a vista simplificada apresenta apenas a secção que
     * corresponde ao conteúdo pesquisado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_simplificada_apresenta_apenas_seccao_correspondente(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $metalThursday =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'MetalThursday sem correspondência nominal',
            );

        $seccaoCorrespondente =
            $this->criarSeccaoDetalhada(
                $metalThursday,
                'Álbum Alvo da Pesquisa',
                'Descrição principal.',
            );

        $seccaoDiferente =
            $this->criarSeccaoDetalhada(
                $metalThursday,
                'Álbum completamente diferente',
                'Outra descrição.',
            );

        $this->get(
            route(
                'inicio',
                [
                    'vista' => 'simplificada',
                    'pesquisa' => 'alvo da pesquisa',
                ],
            ),
        )
            ->assertOk()
            ->assertSeeHtml(
                'id="seccao-simplificada-'
                    .$seccaoCorrespondente->getKey()
                    .'"',
            )
            ->assertDontSeeHtml(
                'id="seccao-simplificada-'
                    .$seccaoDiferente->getKey()
                    .'"',
            );
    }

    /**
     * Confirma que a correspondência pelo nome da MetalThursday apresenta
     * todas as respetivas secções elegíveis na vista simplificada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_simplificada_pesquisa_nome_da_metal_thursday(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $metalThursdayCorrespondente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'Especial Doom',
            );

        $metalThursdayDiferente =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-15',
                'Especial Thrash',
            );

        $primeiraSeccao =
            $this->criarSeccaoDetalhada(
                $metalThursdayCorrespondente,
                'Primeiro lançamento',
                'Primeira descrição.',
            );

        $segundaSeccao =
            $this->criarSeccaoDetalhada(
                $metalThursdayCorrespondente,
                'Segundo lançamento',
                'Segunda descrição.',
            );

        $seccaoDiferente =
            $this->criarSeccaoDetalhada(
                $metalThursdayDiferente,
                'Terceiro lançamento',
                'Terceira descrição.',
            );

        $this->get(
            route(
                'inicio',
                [
                    'vista' => 'simplificada',
                    'pesquisa' => 'especial doom',
                ],
            ),
        )
            ->assertOk()
            ->assertSeeHtml(
                'id="seccao-simplificada-'
                    .$primeiraSeccao->getKey()
                    .'"',
            )
            ->assertSeeHtml(
                'id="seccao-simplificada-'
                    .$segundaSeccao->getKey()
                    .'"',
            )
            ->assertDontSeeHtml(
                'id="seccao-simplificada-'
                    .$seccaoDiferente->getKey()
                    .'"',
            );
    }

    /**
     * Confirma que conteúdo semelhante a SQL não altera a consulta.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_nao_interpreta_conteudo_como_sql(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-08',
            'Primeiro resultado protegido',
        );

        $this->criarMetalThursday(
            $edicao,
            $utilizador,
            '2026-01-15',
            'Segundo resultado protegido',
        );

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => "%' OR 1=1 --",
                ],
            ),
        )
            ->assertOk()
            ->assertSee(
                'Nenhum resultado encontrado.',
            )
            ->assertDontSee(
                'Primeiro resultado protegido',
            )
            ->assertDontSee(
                'Segundo resultado protegido',
            );
    }

    /**
     * Confirma que o termo pesquisado permanece no respetivo campo após a
     * submissão da listagem.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_preserva_termo_de_pesquisa(): void
    {
        $this->autenticarUtilizador();

        $this->get(
            route(
                'inicio',
                [
                    'pesquisa' => 'Iron Maiden',
                ],
            ),
        )
            ->assertOk()
            ->assertSeeHtml(
                'name="pesquisa"',
            )
            ->assertSeeHtml(
                'value="Iron Maiden"',
            );
    }

    /**
     * Confirma que o formulário apresenta permanentemente o campo de pesquisa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_apresenta_campo_de_pesquisa_textual(): void
    {
        $this->autenticarUtilizador();

        $this->get(
            route(
                'inicio',
            ),
        )
            ->assertOk()
            ->assertSeeText(
                'Pesquisar no arquivo',
            )
            ->assertSeeHtml(
                'type="search"',
            )
            ->assertSeeHtml(
                'name="pesquisa"',
            );
    }

    /**
     * Autentica um utilizador válido.
     *
     * @return Utilizador Utilizador autenticado.
     *
     * @since 2.0.0
     */
    private function autenticarUtilizador(): Utilizador
    {
        $utilizador = Utilizador::factory()
            ->create();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        return $utilizador;
    }

    /**
     * Cria uma edição para os testes de pesquisa.
     *
     * @param  string  $nome  Nome da edição.
     * @param  string  $dataInicio  Data inicial.
     * @param  string  $dataFim  Data final.
     * @return Edicao Edição criada.
     *
     * @since 2.0.0
     */
    private function criarEdicao(
        string $nome = 'Edição de Pesquisa',
        string $dataInicio = '2026-01-01',
        string $dataFim = '2026-01-31',
    ): Edicao {
        return Edicao::factory()
            ->comNome(
                $nome,
            )
            ->comPeriodo(
                CarbonImmutable::parse(
                    $dataInicio,
                ),
                CarbonImmutable::parse(
                    $dataFim,
                ),
            )
            ->create();
    }

    /**
     * Cria uma MetalThursday identificável nos testes.
     *
     * @param  Edicao  $edicao  Edição associada.
     * @param  Utilizador  $autor  Autor associado.
     * @param  string  $data  Data da MetalThursday.
     * @param  string  $nome  Nome da MetalThursday.
     * @return MetalThursday MetalThursday criada.
     *
     * @since 2.0.0
     */
    private function criarMetalThursday(
        Edicao $edicao,
        Utilizador $autor,
        string $data,
        string $nome,
    ): MetalThursday {
        return MetalThursday::factory()
            ->comNome(
                $nome,
            )
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
     * Cria uma secção musical detalhada na posição seguinte disponível.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday associada.
     * @param  string  $titulo  Título da secção.
     * @param  string  $descricao  Descrição da secção.
     * @param  Artista|null  $artista  Artista existente ou nulo.
     * @return SeccaoMetalThursday Secção criada.
     *
     * @since 2.0.0
     */
    private function criarSeccaoDetalhada(
        MetalThursday $metalThursday,
        string $titulo,
        string $descricao,
        ?Artista $artista = null,
    ): SeccaoMetalThursday {
        $artista ??= Artista::factory()
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $ultimaOrdem = SeccaoMetalThursday::query()
            ->where(
                'metal_thursday_id',
                $metalThursday->getKey(),
            )
            ->max(
                'ordem',
            );

        $ordem = is_numeric($ultimaOrdem)
            ? (int) $ultimaOrdem + 1
            : SeccaoMetalThursday::ORDEM_MINIMA;

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
            ->naOrdem(
                $ordem,
            )
            ->comConteudo(
                $descricao,
                $titulo,
            )
            ->create();
    }
}
