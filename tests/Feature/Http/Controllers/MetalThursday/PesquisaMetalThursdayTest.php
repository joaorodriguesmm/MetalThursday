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
use Illuminate\Pagination\LengthAwarePaginator;
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
            ->assertViewHas(
                'seccoesSimplificadas',
                static function (
                    mixed $valor,
                ) use (
                    $seccaoCorrespondente,
                ): bool {
                    if (! $valor instanceof LengthAwarePaginator) {
                        return false;
                    }

                    $seccaoApresentada =
                        $valor
                            ->getCollection()
                            ->first();

                    if (
                        ! $seccaoApresentada instanceof SeccaoMetalThursday
                        || ! $seccaoApresentada->is(
                            $seccaoCorrespondente,
                        )
                    ) {
                        return false;
                    }

                    $atributos =
                        $seccaoApresentada->getAttributes();

                    return ! array_key_exists(
                        'avaliacoes_count',
                        $atributos,
                    )
                        && ! array_key_exists(
                            'audicoes_count',
                            $atributos,
                        )
                        && ! array_key_exists(
                            'avaliacoes_avg_pontuacao',
                            $atributos,
                        )
                        && $seccaoApresentada->relationLoaded(
                            'avaliacoes',
                        )
                        && $seccaoApresentada->relationLoaded(
                            'audicoes',
                        );
                },
            )
            ->assertSeeHtml(
                'id="seccao-simplificada-'
                    .$seccaoCorrespondente->id
                    .'"',
            )
            ->assertDontSeeHtml(
                'id="seccao-simplificada-'
                    .$seccaoDiferente->id
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

        $primeiraSeccao
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
                'pontuacao' => 9.5,
            ]);

        $segundaSeccao
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
                'pontuacao' => 4.0,
            ]);

        $this->get(
            route(
                'inicio',
                [
                    'vista' => 'simplificada',
                    'pesquisa' => 'especial doom',
                    'ordenar_por' => 'classificacao',
                ],
            ),
        )
            ->assertOk()
            ->assertViewHas(
                'seccoesSimplificadas',
                static function (
                    mixed $valor,
                ) use (
                    $primeiraSeccao,
                ): bool {
                    if (! $valor instanceof LengthAwarePaginator) {
                        return false;
                    }

                    $seccaoApresentada =
                        $valor
                            ->getCollection()
                            ->first(
                                static fn (mixed $seccao): bool => $seccao instanceof SeccaoMetalThursday
                                    && $seccao->is($primeiraSeccao),
                            );

                    if (! $seccaoApresentada instanceof SeccaoMetalThursday) {
                        return false;
                    }

                    $atributos =
                        $seccaoApresentada->getAttributes();

                    return ! array_key_exists(
                        'avaliacoes_count',
                        $atributos,
                    )
                        && ! array_key_exists(
                            'audicoes_count',
                            $atributos,
                        )
                        && array_key_exists(
                            'avaliacoes_avg_pontuacao',
                            $atributos,
                        )
                        && is_numeric(
                            $atributos[
                                'avaliacoes_avg_pontuacao'
                            ],
                        )
                        && (float) $atributos[
                            'avaliacoes_avg_pontuacao'
                        ] === 9.5
                        && $seccaoApresentada->relationLoaded(
                            'avaliacoes',
                        )
                        && $seccaoApresentada->relationLoaded(
                            'audicoes',
                        );
                },
            )
            ->assertSeeHtml(
                'id="seccao-simplificada-'
                    .$primeiraSeccao->id
                    .'"',
            )
            ->assertSeeHtml(
                'id="seccao-simplificada-'
                    .$segundaSeccao->id
                    .'"',
            )
            ->assertDontSeeHtml(
                'id="seccao-simplificada-'
                    .$seccaoDiferente->id
                    .'"',
            );

        $this->get(
            route(
                'inicio',
                [
                    'vista' => 'simplificada',
                    'pesquisa' => 'especial doom',
                    'ordenar_por' => 'minha_classificacao',
                ],
            ),
        )
            ->assertOk()
            ->assertViewHas(
                'seccoesSimplificadas',
                static function (
                    mixed $valor,
                ) use (
                    $primeiraSeccao,
                    $segundaSeccao,
                ): bool {
                    if (! $valor instanceof LengthAwarePaginator) {
                        return false;
                    }

                    $seccoes =
                        $valor
                            ->getCollection()
                            ->values();

                    $primeiraApresentada =
                        $seccoes->get(0);

                    $segundaApresentada =
                        $seccoes->get(1);

                    if (
                        ! $primeiraApresentada instanceof SeccaoMetalThursday
                        || ! $segundaApresentada instanceof SeccaoMetalThursday
                        || ! $primeiraApresentada->is($primeiraSeccao)
                        || ! $segundaApresentada->is($segundaSeccao)
                    ) {
                        return false;
                    }

                    $atributosPrimeira =
                        $primeiraApresentada->getAttributes();

                    $atributosSegunda =
                        $segundaApresentada->getAttributes();

                    return ! array_key_exists(
                        'avaliacoes_count',
                        $atributosPrimeira,
                    )
                        && ! array_key_exists(
                            'audicoes_count',
                            $atributosPrimeira,
                        )
                        && ! array_key_exists(
                            'avaliacoes_avg_pontuacao',
                            $atributosPrimeira,
                        )
                        && array_key_exists(
                            'classificacao_utilizador',
                            $atributosPrimeira,
                        )
                        && array_key_exists(
                            'classificacao_utilizador',
                            $atributosSegunda,
                        )
                        && is_numeric(
                            $atributosPrimeira[
                                'classificacao_utilizador'
                            ],
                        )
                        && is_numeric(
                            $atributosSegunda[
                                'classificacao_utilizador'
                            ],
                        )
                        && (float) $atributosPrimeira[
                            'classificacao_utilizador'
                        ] === 9.5
                        && (float) $atributosSegunda[
                            'classificacao_utilizador'
                        ] === 4.0
                        && $primeiraApresentada->relationLoaded(
                            'avaliacoes',
                        )
                        && $primeiraApresentada->relationLoaded(
                            'audicoes',
                        );
                },
            );
    }

    /**
     * Confirma que a vista completa calcula antes da paginação apenas os
     * agregados necessários à apresentação e à ordenação.
     *
     * As contagens de avaliações e audições são derivadas das relações já
     * carregadas. A média só permanece como agregado SQL quando é utilizada
     * para ordenar por classificação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_completa_calcula_apenas_agregados_necessarios_antes_da_paginacao(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $primeira =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'Primeira MetalThursday',
            );

        $segunda =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-15',
                'Segunda MetalThursday',
            );

        $primeira
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
                'pontuacao' => 9.5,
            ]);

        $segunda
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
                'pontuacao' => 4.0,
            ]);

        $primeira
            ->audicoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
            ]);

        foreach (
            [
                'data' => [
                    'parametros' => [],
                    'inclui_media' => false,
                    'inclui_classificacao_utilizador' => false,
                ],
                'classificacao' => [
                    'parametros' => [
                        'ordenar_por' => 'classificacao',
                    ],
                    'inclui_media' => true,
                    'inclui_classificacao_utilizador' => false,
                ],
                'minha_classificacao' => [
                    'parametros' => [
                        'ordenar_por' => 'minha_classificacao',
                    ],
                    'inclui_media' => false,
                    'inclui_classificacao_utilizador' => true,
                ],
            ] as $cenario
        ) {
            $this->get(
                route(
                    'inicio',
                    [
                        'vista' => 'completa',
                        ...$cenario['parametros'],
                    ],
                ),
            )
                ->assertOk()
                ->assertViewHas(
                    'registosMetalThursday',
                    static function (
                        mixed $valor,
                    ) use (
                        $cenario,
                    ): bool {
                        if (! $valor instanceof LengthAwarePaginator) {
                            return false;
                        }

                        foreach (
                            $valor->getCollection() as $metalThursday
                        ) {
                            if (! $metalThursday instanceof MetalThursday) {
                                return false;
                            }

                            $atributos =
                                $metalThursday->getAttributes();

                            if (
                                ! array_key_exists(
                                    'comentarios_count',
                                    $atributos,
                                )
                                || array_key_exists(
                                    'avaliacoes_count',
                                    $atributos,
                                )
                                || array_key_exists(
                                    'audicoes_count',
                                    $atributos,
                                )
                                || array_key_exists(
                                    'avaliacoes_avg_pontuacao',
                                    $atributos,
                                ) !== $cenario['inclui_media']
                                || array_key_exists(
                                    'classificacao_utilizador',
                                    $atributos,
                                ) !== $cenario[
                                    'inclui_classificacao_utilizador'
                                ]
                                || ! array_key_exists(
                                    'pontuacao_utilizador_autenticado',
                                    $atributos,
                                )
                                || ! array_key_exists(
                                    'ouvido_pelo_utilizador_autenticado',
                                    $atributos,
                                )
                                || $metalThursday->relationLoaded(
                                    'avaliacaoUtilizadorAutenticado',
                                )
                                || $metalThursday->relationLoaded(
                                    'audicaoUtilizadorAutenticado',
                                )
                                || ! $metalThursday->relationLoaded(
                                    'avaliacoes',
                                )
                                || ! $metalThursday->relationLoaded(
                                    'audicoes',
                                )
                            ) {
                                return false;
                            }
                        }

                        return true;
                    },
                );
        }
    }

    /**
     * Confirma que a vista completa carrega o estado do utilizador nas secções
     * através de atributos escalares, sem relações redundantes.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_completa_carrega_estado_do_utilizador_das_seccoes_com_atributos_escalares(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        $metalThursday =
            $this->criarMetalThursday(
                $edicao,
                $utilizador,
                '2026-01-08',
                'MetalThursday com estado escalar',
            );

        $seccao =
            $this->criarSeccaoDetalhada(
                $metalThursday,
                'Secção com estado escalar',
                'Descrição da secção.',
            );

        $seccao
            ->avaliacoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
                'pontuacao' => 8.5,
            ]);

        $seccao
            ->audicoes()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
            ]);

        $this->get(
            route(
                'inicio',
                [
                    'vista' => 'completa',
                ],
            ),
        )
            ->assertOk()
            ->assertViewHas(
                'registosMetalThursday',
                static function (
                    mixed $valor,
                ) use (
                    $seccao,
                ): bool {
                    if (! $valor instanceof LengthAwarePaginator) {
                        return false;
                    }

                    foreach ($valor->getCollection() as $metalThursday) {
                        if (! $metalThursday instanceof MetalThursday) {
                            continue;
                        }

                        $seccaoApresentada =
                            $metalThursday
                                ->seccoes
                                ->first(
                                    static fn (
                                        mixed $item,
                                    ): bool => $item instanceof SeccaoMetalThursday
                                        && $item->is(
                                            $seccao,
                                        ),
                                );

                        if (! $seccaoApresentada instanceof SeccaoMetalThursday) {
                            continue;
                        }

                        $atributos =
                            $seccaoApresentada->getAttributes();

                        return array_key_exists(
                            'comentarios_count',
                            $atributos,
                        )
                            && ! array_key_exists(
                                'avaliacoes_count',
                                $atributos,
                            )
                            && ! array_key_exists(
                                'audicoes_count',
                                $atributos,
                            )
                            && ! array_key_exists(
                                'avaliacoes_avg_pontuacao',
                                $atributos,
                            )
                            && array_key_exists(
                                'pontuacao_utilizador_autenticado',
                                $atributos,
                            )
                            && array_key_exists(
                                'ouvido_pelo_utilizador_autenticado',
                                $atributos,
                            )
                            && array_key_exists(
                                'nome_artista_apresentacao',
                                $atributos,
                            )
                            && ! $seccaoApresentada->relationLoaded(
                                'artista',
                            )
                            && (float) $atributos[
                                'pontuacao_utilizador_autenticado'
                            ] === 8.5
                            && (bool) $atributos[
                                'ouvido_pelo_utilizador_autenticado'
                            ]
                            && ! $seccaoApresentada->relationLoaded(
                                'avaliacaoUtilizadorAutenticado',
                            )
                            && ! $seccaoApresentada->relationLoaded(
                                'audicaoUtilizadorAutenticado',
                            )
                            && $seccaoApresentada->relationLoaded(
                                'avaliacoes',
                            )
                            && $seccaoApresentada->relationLoaded(
                                'audicoes',
                            )
                            && $seccaoApresentada
                                ->pontuacao_utilizador_autenticado === 8.5
                            && $seccaoApresentada
                                ->ouvido_pelo_utilizador_autenticado;
                    }

                    return false;
                },
            );
    }

    /**
     * Confirma que uma página sem registos preserva o total nas duas vistas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function paginacao_preserva_total_em_pagina_sem_resultados_nas_duas_vistas(): void
    {
        $utilizador = $this->autenticarUtilizador();

        $edicao = $this->criarEdicao();

        foreach (
            [
                '2026-01-08',
                '2026-01-15',
                '2026-01-22',
            ] as $indice => $data
        ) {
            $metalThursday =
                $this->criarMetalThursday(
                    $edicao,
                    $utilizador,
                    $data,
                    'Resultado de paginação '.($indice + 1),
                );

            $this->criarSeccaoDetalhada(
                $metalThursday,
                'Alvo paginacao '.($indice + 1),
                'Descrição para validar a paginação.',
            );
        }

        foreach (
            [
                'completa' => 'registosMetalThursday',
                'simplificada' => 'seccoesSimplificadas',
            ] as $vista => $chavePaginador
        ) {
            $this->get(
                route(
                    'inicio',
                    [
                        'vista' => $vista,
                        'por_pagina' => 5,
                        'pesquisa' => 'alvo paginacao',
                        'page' => 2,
                    ],
                ),
            )
                ->assertOk()
                ->assertViewHas(
                    $chavePaginador,
                    static function (
                        mixed $valor,
                    ): bool {
                        return $valor instanceof LengthAwarePaginator
                            && $valor->total() === 3
                            && $valor->currentPage() === 2
                            && $valor->lastPage() === 1
                            && $valor->count() === 0
                            && $valor->firstItem() === null
                            && $valor->lastItem() === null;
                    },
                );
        }
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
