<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\MetalThursday;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integração do perfil enriquecido dos artistas nos formulários de
 * MetalThursday.
 *
 * @since 2.0.0
 */
final class PerfilArtistaMetalThursdayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que as opções de artista carregam o ano de início necessário
     * ao rótulo contextual.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_apresenta_ano_inicio_no_rotulo_do_artista(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $origem = OrigemGeografica::factory()
            ->create([
                'nome' => 'Suécia',

                'codigo' => 'SE',
            ]);

        $genero = Genero::factory()
            ->create([
                'nome' => 'Heavy Metal',
            ]);

        $artista = Artista::factory()
            ->create([
                'nome' => 'Ghost',

                'origem_geografica_id' => $origem->getKey(),

                'ano_inicio_atividade' => 2006,
            ]);

        $artista
            ->generos()
            ->attach(
                $genero->getKey(),
            );

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Ghost — Suécia · 2006 · Heavy Metal',
            );
    }

    /**
     * Confirma que os géneros são opcionais na criação rápida de artistas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function modal_criacao_rapida_apresenta_generos_como_opcionais(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $resposta = $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            );

        $resposta->assertOk();

        self::assertMatchesRegularExpression(
            '/<label\b(?=[^>]*\bfor="generos-novo-artista")[^>]*>(?:(?!<\/label>).)*Géneros(?:(?!<\/label>).)*\(opcional\)(?:(?!<\/label>).)*<\/label>/s',
            $resposta->getContent(),
        );

        self::assertDoesNotMatchRegularExpression(
            '/<select\b(?=[^>]*\bid="generos-novo-artista")[^>]*\brequired\b[^>]*>/s',
            $resposta->getContent(),
        );
    }

    /**
     * Confirma que um artista sem géneros mantém um rótulo de seleção válido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_apresenta_rotulo_valido_para_artista_sem_generos(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $origem = OrigemGeografica::factory()
            ->create([
                'nome' => 'Portugal',

                'codigo' => 'PT',
            ]);

        $artista = Artista::factory()
            ->create([
                'nome' => 'Moonspell',

                'origem_geografica_id' => $origem->getKey(),
            ]);

        $resposta = $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            );

        $resposta->assertOk();

        self::assertMatchesRegularExpression(
            '/<option\b(?=[^>]*\bvalue="'.preg_quote((string) $artista->getKey(), '/').'")[^>]*>\s*Moonspell — Portugal\s*<\/option>/s',
            $resposta->getContent(),
        );
    }

    /**
     * Confirma que a edição mantém disponível um artista eliminado logicamente
     * quando este já pertence a uma secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function edicao_apresenta_artista_eliminado_logicamente_ja_associado(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $artista = Artista::factory()
            ->comNome(
                'Artista Histórico Eliminado',
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $artista,
            )
            ->create();

        $artista->deleteOrFail();

        $resposta = $this->get(
            route(
                'metal-thursday.editar',
                $metalThursday,
            ),
        );

        $resposta->assertOk();

        self::assertMatchesRegularExpression(
            '/<option\b(?=[^>]*\bvalue="'
                .preg_quote((string) $artista->getKey(), '/')
                .'")[^>]*>\s*Artista Histórico Eliminado(?:\s|<|—|·).*?<\/option>/s',
            $resposta->getContent(),
        );
    }

    /**
     * Confirma que uma secção pode conservar um artista eliminado logicamente
     * durante a edição da MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_preserva_artista_eliminado_logicamente_ja_associado(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();

        $artista = Artista::factory()
            ->comNome(
                'Artista Histórico Eliminado',
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $administrador,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->comDetalhes(
                $artista,
            )
            ->create();

        $artista->deleteOrFail();

        $resposta = $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            [
                'data' => '2026-01-15',

                'nome' => $metalThursday->nome,

                'autor_id' => $administrador->getKey(),

                'seccoes' => [
                    [
                        'id' => $seccao->getKey(),

                        'tipo_seccao_id' => $tipoSeccao->getKey(),

                        'titulo' => $seccao->titulo,

                        'descricao' => $seccao->descricao,

                        'artista_id' => $artista->getKey(),

                        'ligacao' => $seccao->ligacao,

                        'tipo_incorporacao' => $seccao
                            ->tipo_incorporacao
                            ?->value,

                        'ano' => $seccao->ano,
                    ],
                ],
            ],
        );

        $resposta->assertOk();

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccao->getKey(),

                'artista_id' => $artista->getKey(),

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que um artista eliminado logicamente não pode ser transferido
     * para outra secção durante a edição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_rejeita_transferencia_de_artista_eliminado_para_outra_seccao(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();

        $artistaHistorico = Artista::factory()
            ->comNome(
                'Artista Histórico',
            )
            ->create();

        $outroArtista = Artista::factory()
            ->comNome(
                'Outro Artista',
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $administrador,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $seccaoHistorica = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $artistaHistorico,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->naOrdem(
                1,
            )
            ->create();

        $outraSeccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $outroArtista,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->naOrdem(
                2,
            )
            ->create();

        $artistaHistorico->deleteOrFail();

        $resposta = $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            [
                'data' => '2026-01-15',

                'nome' => $metalThursday->nome,

                'autor_id' => $administrador->getKey(),

                'seccoes' => [
                    [
                        'id' => $seccaoHistorica->getKey(),

                        'tipo_seccao_id' => $tipoSeccao->getKey(),

                        'titulo' => $seccaoHistorica->titulo,

                        'descricao' => $seccaoHistorica->descricao,

                        'artista_id' => $artistaHistorico->getKey(),

                        'ligacao' => $seccaoHistorica->ligacao,

                        'tipo_incorporacao' => $seccaoHistorica
                            ->tipo_incorporacao
                            ?->value,

                        'ano' => $seccaoHistorica->ano,
                    ],
                    [
                        'id' => $outraSeccao->getKey(),

                        'tipo_seccao_id' => $tipoSeccao->getKey(),

                        'titulo' => $outraSeccao->titulo,

                        'descricao' => $outraSeccao->descricao,

                        'artista_id' => $artistaHistorico->getKey(),

                        'ligacao' => $outraSeccao->ligacao,

                        'tipo_incorporacao' => $outraSeccao
                            ->tipo_incorporacao
                            ?->value,

                        'ano' => $outraSeccao->ano,
                    ],
                ],
            ],
        );

        $resposta
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'seccoes.1.artista_id',
            ]);

        $erros =
            $resposta->json(
                'errors',
            );

        self::assertIsArray(
            $erros,
        );

        self::assertSame(
            'O artista selecionado não existe ou não está disponível.',
            $erros['seccoes.1.artista_id'][0]
                ?? null,
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccaoHistorica->getKey(),

                'artista_id' => $artistaHistorico->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $outraSeccao->getKey(),

                'artista_id' => $outroArtista->getKey(),
            ],
        );
    }

    /**
     * Confirma que uma secção nova não pode utilizar um artista eliminado
     * logicamente durante a edição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualizacao_rejeita_artista_eliminado_logicamente_em_nova_seccao(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $this->actingAs(
            $administrador,
            'sessao',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();

        $artistaAtivo = Artista::factory()
            ->comNome(
                'Artista Ativo',
            )
            ->create();

        $artistaEliminado = Artista::factory()
            ->comNome(
                'Artista Eliminado',
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $administrador,
            )
            ->create();

        $tipoSeccao = TipoSeccao::factory()
            ->comDetalhes()
            ->create();

        $seccaoExistente = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $artistaAtivo,
            )
            ->doTipo(
                $tipoSeccao,
            )
            ->naOrdem(
                1,
            )
            ->create();

        $artistaEliminado->deleteOrFail();

        $resposta = $this->patchJson(
            route(
                'metal-thursday.atualizar',
                $metalThursday,
            ),
            [
                'data' => '2026-01-15',

                'nome' => $metalThursday->nome,

                'autor_id' => $administrador->getKey(),

                'seccoes' => [
                    [
                        'id' => $seccaoExistente->getKey(),

                        'tipo_seccao_id' => $tipoSeccao->getKey(),

                        'titulo' => $seccaoExistente->titulo,

                        'descricao' => $seccaoExistente->descricao,

                        'artista_id' => $artistaAtivo->getKey(),

                        'ligacao' => $seccaoExistente->ligacao,

                        'tipo_incorporacao' => $seccaoExistente
                            ->tipo_incorporacao
                            ?->value,

                        'ano' => $seccaoExistente->ano,
                    ],
                    [
                        'id' => null,

                        'tipo_seccao_id' => $tipoSeccao->getKey(),

                        'titulo' => 'Nova secção histórica',

                        'descricao' => 'Descrição da nova secção.',

                        'artista_id' => $artistaEliminado->getKey(),

                        'ligacao' => 'https://example.com/nova-seccao',

                        'tipo_incorporacao' => 'ligacao',

                        'ano' => 2020,
                    ],
                ],
            ],
        );

        $resposta
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'seccoes.1.artista_id',
            ]);

        $erros =
            $resposta->json(
                'errors',
            );

        self::assertIsArray(
            $erros,
        );

        self::assertSame(
            'O artista selecionado não existe ou não está disponível.',
            $erros['seccoes.1.artista_id'][0]
                ?? null,
        );

        $this->assertDatabaseHas(
            'seccoes_metal_thursday',
            [
                'id' => $seccaoExistente->getKey(),

                'artista_id' => $artistaAtivo->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'seccoes_metal_thursday',
            [
                'metal_thursday_id' => $metalThursday->getKey(),

                'titulo' => 'Nova secção histórica',
            ],
        );
    }

    /**
     * Confirma que a vista simplificada preserva o artista eliminado logicamente
     * de uma secção histórica.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_simplificada_apresenta_artista_eliminado_logicamente(): void
    {
        $utilizador = Utilizador::factory()
            ->create([
                'email_verified_at' => now(),
            ]);

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();

        $artista = Artista::factory()
            ->comNome(
                'Artista Histórico Simplificado',
            )
            ->create([
                'origem_geografica_id' => null,
            ]);

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $utilizador,
            )
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $artista,
            )
            ->create();

        $artista->deleteOrFail();

        $this
            ->get(
                route(
                    'inicio',
                    [
                        'vista' => 'simplificada',
                    ],
                ),
            )
            ->assertOk()
            ->assertSee(
                'Artista Histórico Simplificado',
            )
            ->assertSee(
                $seccao->titulo,
            )
            ->assertSee(
                'Origem não indicada',
            )
            ->assertDontSee(
                'Artista indisponível',
            );
    }

    /**
     * Confirma que a vista completa preserva o artista eliminado logicamente
     * de uma secção histórica.
     *
     * @since 2.0.0
     */
    #[Test]
    public function vista_completa_apresenta_artista_eliminado_logicamente(): void
    {
        $utilizador = Utilizador::factory()
            ->create([
                'email_verified_at' => now(),
            ]);

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $edicao = Edicao::factory()
            ->comPeriodo(
                CarbonImmutable::parse(
                    '2026-01-01',
                ),
                CarbonImmutable::parse(
                    '2026-01-31',
                ),
            )
            ->create();

        $artista = Artista::factory()
            ->comNome(
                'Artista Histórico Completo',
            )
            ->create();

        $metalThursday = MetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comEdicao(
                $edicao,
            )
            ->comAutor(
                $utilizador,
            )
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->paraMetalThursday(
                $metalThursday,
            )
            ->comDetalhes(
                $artista,
            )
            ->create();

        $artista->deleteOrFail();

        $this
            ->get(
                route(
                    'inicio',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Artista Histórico Completo',
            )
            ->assertSee(
                $seccao->titulo,
            )
            ->assertDontSee(
                'Artista indisponível',
            );
    }

    /**
     * Confirma que a criação de uma MetalThursday não disponibiliza artistas
     * eliminados logicamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formulario_criacao_nao_apresenta_artista_eliminado_logicamente(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(
                PapelUtilizador::Administrador,
            )
            ->create([
                'email_verified_at' => now(),
            ]);

        $artistaAtivo = Artista::factory()
            ->comNome(
                'Artista Ativo Selecionável',
            )
            ->create();

        $artistaEliminado = Artista::factory()
            ->comNome(
                'Artista Eliminado Não Selecionável',
            )
            ->create();

        $artistaEliminado->deleteOrFail();

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->get(
                route(
                    'metal-thursday.criar',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Artista Ativo Selecionável',
            )
            ->assertDontSee(
                'Artista Eliminado Não Selecionável',
            );
    }
}
