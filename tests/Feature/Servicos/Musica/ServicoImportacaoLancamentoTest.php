<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Enumeracoes\TipoLancamento;
use App\Models\Musica\Artista;
use App\Models\Musica\Lancamento;
use App\Models\Musica\Musica;
use App\Servicos\Musica\ServicoImportacaoLancamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a importação persistente de lançamentos musicais.
 *
 * @since 2.0.0
 */
final class ServicoImportacaoLancamentoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura a integração Discogs utilizada pelos testes.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'discogs.base_url',
            'https://api.discogs.com',
        );

        config()->set(
            'discogs.user_agent',
            'MetalThursdayTest/2.0',
        );

        config()->set(
            'discogs.tentativas',
            3,
        );

        config()->set(
            'discogs.intervalo_repeticao_ms',
            0,
        );

        config()->set(
            'discogs.intervalo_minimo_pedidos_ms',
            0,
        );
    }

    /**
     * Confirma a persistência de uma edição concreta obtida do Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function importa_lancamento_base_do_discogs(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'artists' => [],
                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        self::assertInstanceOf(
            Lancamento::class,
            $lancamento,
        );

        self::assertTrue(
            $lancamento->exists,
        );

        self::assertSame(
            'Master Of Puppets',
            $lancamento->titulo,
        );

        self::assertSame(
            249504,
            $lancamento->discogs_release_id,
        );

        self::assertNull(
            $lancamento->tipo,
        );

        $this->assertDatabaseHas(
            'lancamentos',
            [
                'id' => $lancamento->getKey(),
                'titulo' => 'Master Of Puppets',
                'discogs_release_id' => 249504,
                'tipo' => null,
            ],
        );
    }

    /**
     * Confirma a persistência da sugestão de tipo e do ano original do Master.
     *
     * @since 2.0.0
     */
    #[Test]
    public function persiste_tipo_sugerido_e_ano_original_do_master(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'year' => 1991,
                    'master_id' => 12345,

                    'formats' => [
                        [
                            'name' => 'CD',

                            'descriptions' => [
                                'Album',
                                'Reissue',
                            ],
                        ],
                    ],

                    'artists' => [],
                    'tracklist' => [],
                ],
                200,
            ),

            'https://api.discogs.com/masters/12345' => Http::response(
                [
                    'id' => 12345,
                    'year' => 1986,
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        self::assertSame(
            TipoLancamento::AlbumEstudio,
            $lancamento->tipo,
        );

        self::assertSame(
            1986,
            $lancamento->ano_original,
        );

        $this->assertDatabaseHas(
            'lancamentos',
            [
                'id' => $lancamento->getKey(),
                'tipo' => TipoLancamento::AlbumEstudio->value,
                'ano_original' => 1986,
            ],
        );
    }

    /**
     * Confirma que uma edição Discogs já importada é reutilizada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reutiliza_lancamento_discogs_ja_importado(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'year' => 1986,
                    'formats' => [
                        [
                            'name' => 'Vinyl',
                            'descriptions' => ['Album'],
                        ],
                    ],
                    'artists' => [],
                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $servico =
            app(
                ServicoImportacaoLancamento::class,
            );

        $primeiroLancamento =
            $servico->importar(
                249504,
            );

        $segundoLancamento =
            $servico->importar(
                249504,
            );

        self::assertSame(
            $primeiroLancamento->getKey(),
            $segundoLancamento->getKey(),
        );

        self::assertSame(
            1,
            Lancamento::query()
                ->where(
                    'discogs_release_id',
                    249504,
                )
                ->count(),
        );

        Http::assertSentCount(
            1,
        );
    }

    /**
     * Confirma que uma importação reutiliza um lançamento criado por outro
     * pedido enquanto a consulta ao Discogs estava em curso.
     *
     * O cenário simula a janela de concorrência entre a consulta inicial à
     * base de dados e a persistência local da resposta externa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reutiliza_lancamento_criado_concorrentemente_durante_consulta_discogs(): void
    {
        $lancamentoConcorrente = null;

        Http::fake(
            static function () use (
                &$lancamentoConcorrente,
            ) {
                $lancamentoConcorrente =
                    Lancamento::factory()
                        ->create([
                            'titulo' => 'Título persistido por outro pedido',
                            'tipo' => TipoLancamento::AlbumEstudio,
                            'ano_original' => 1986,
                            'discogs_release_id' => 249504,
                        ]);

                return Http::response(
                    [
                        'id' => 249504,
                        'title' => 'Master Of Puppets',
                        'year' => 1986,
                        'formats' => [
                            [
                                'name' => 'Vinyl',
                                'descriptions' => ['Album'],
                            ],
                        ],
                        'artists' => [],
                        'tracklist' => [],
                    ],
                    200,
                );
            },
        );

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        self::assertInstanceOf(
            Lancamento::class,
            $lancamentoConcorrente,
        );

        self::assertSame(
            $lancamentoConcorrente->getKey(),
            $lancamento->getKey(),
        );

        self::assertSame(
            'Título persistido por outro pedido',
            $lancamento->titulo,
        );

        self::assertSame(
            1,
            Lancamento::withTrashed()
                ->where(
                    'discogs_release_id',
                    249504,
                )
                ->count(),
        );
    }

    /**
     * Confirma que metadados em falta são completados numa importação antiga.
     *
     * Valores já existentes continuam preservados para não substituir
     * correções efetuadas manualmente pelo utilizador.
     *
     * @since 2.0.0
     */
    #[Test]
    public function completa_metadados_em_falta_ao_reutilizar_lancamento_discogs(): void
    {
        $existente =
            Lancamento::factory()
                ->create([
                    'titulo' => 'Título corrigido manualmente',
                    'tipo' => TipoLancamento::AlbumEstudio,
                    'ano_original' => null,
                    'discogs_release_id' => 2609819,
                ]);

        Http::fake([
            'https://api.discogs.com/releases/2609819' => Http::response(
                [
                    'id' => 2609819,
                    'title' => 'Master Of Puppets',
                    'year' => 1986,
                    'formats' => [
                        [
                            'name' => 'Vinyl',
                            'descriptions' => ['Album'],
                        ],
                    ],
                    'artists' => [],
                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                2609819,
            );

        self::assertSame(
            $existente->getKey(),
            $lancamento->getKey(),
        );

        self::assertSame(
            'Título corrigido manualmente',
            $lancamento->titulo,
        );

        self::assertSame(
            TipoLancamento::AlbumEstudio,
            $lancamento->tipo,
        );

        self::assertSame(
            1986,
            $lancamento->ano_original,
        );

        Http::assertSentCount(
            1,
        );
    }

    /**
     * Confirma que uma edição Discogs eliminada logicamente é restaurada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function restaura_lancamento_discogs_eliminado(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'year' => 1986,
                    'formats' => [
                        [
                            'name' => 'Vinyl',
                            'descriptions' => ['Album'],
                        ],
                    ],
                    'artists' => [],
                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $servico =
            app(
                ServicoImportacaoLancamento::class,
            );

        $lancamento =
            $servico->importar(
                249504,
            );

        $identificador =
            $lancamento->getKey();

        $lancamento->delete();

        self::assertSoftDeleted(
            'lancamentos',
            [
                'id' => $identificador,
            ],
        );

        $restaurado =
            $servico->importar(
                249504,
            );

        self::assertSame(
            $identificador,
            $restaurado->getKey(),
        );

        self::assertFalse(
            $restaurado->trashed(),
        );

        self::assertSame(
            1,
            Lancamento::withTrashed()
                ->where(
                    'discogs_release_id',
                    249504,
                )
                ->count(),
        );

        Http::assertSentCount(
            1,
        );
    }

    /**
     * Confirma que um artista Discogs já existente não é associado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_associa_artista_discogs_ja_existente(): void
    {
        $artista =
            Artista::factory()
                ->create([
                    'nome' => 'Metallica',
                    'discogs_id' => 18839,
                ]);

        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',

                    'artists' => [
                        [
                            'id' => 18839,
                            'name' => 'Metallica',
                        ],
                    ],

                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        self::assertSame(
            0,
            $lancamento
                ->artistas()
                ->count(),
        );

        self::assertSame(
            1,
            Artista::query()
                ->whereKey(
                    $artista->getKey(),
                )
                ->count(),
        );
    }

    /**
     * Confirma que um artista Discogs inexistente não é criado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_cria_artista_discogs_inexistente(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',

                    'artists' => [
                        [
                            'id' => 18839,
                            'name' => 'Metallica',
                        ],
                    ],

                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'discogs_id' => 18839,
            ],
        );

        self::assertSame(
            0,
            $lancamento
                ->artistas()
                ->count(),
        );
    }

    /**
     * Confirma que um artista Discogs eliminado não é restaurado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_restaura_artista_discogs_eliminado(): void
    {
        $artista =
            Artista::factory()
                ->create([
                    'nome' => 'Metallica',
                    'discogs_id' => 18839,
                ]);

        $identificadorArtista =
            $artista->getKey();

        $artista->delete();

        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',

                    'artists' => [
                        [
                            'id' => 18839,
                            'name' => 'Metallica',
                        ],
                    ],

                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        app(
            ServicoImportacaoLancamento::class,
        )->importar(
            249504,
        );

        $artistaDepois =
            Artista::withTrashed()
                ->findOrFail(
                    $identificadorArtista,
                );

        self::assertTrue(
            $artistaDepois->trashed(),
        );
    }

    /**
     * Confirma que dados inválidos de artista não interferem com a importação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function ignora_dados_de_artista_invalidos_na_importacao(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',

                    'artists' => [
                        [
                            'id' => 18839,
                            'name' => str_repeat(
                                'A',
                                256,
                            ),
                        ],
                    ],

                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        self::assertTrue(
            $lancamento->exists,
        );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'discogs_id' => 18839,
            ],
        );
    }

    /**
     * Confirma a persistência das músicas e das respetivas ocorrências na Release.
     *
     * @since 2.0.0
     */
    #[Test]
    public function importa_musicas_e_faixas_do_lancamento(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'artists' => [],

                    'tracklist' => [
                        [
                            'position' => 'A1',
                            'type_' => 'track',
                            'title' => 'Battery',
                        ],
                        [
                            'position' => 'A2',
                            'type_' => 'track',
                            'title' => 'Master Of Puppets',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        $faixas =
            $lancamento
                ->faixas()
                ->with(
                    'musica',
                )
                ->get();

        self::assertCount(
            2,
            $faixas,
        );

        self::assertSame(
            'Battery',
            $faixas[0]->musica->titulo,
        );

        self::assertSame(
            'A1',
            $faixas[0]->posicao,
        );

        self::assertSame(
            1,
            $faixas[0]->ordem,
        );

        self::assertSame(
            'Master Of Puppets',
            $faixas[1]->musica->titulo,
        );

        self::assertSame(
            'A2',
            $faixas[1]->posicao,
        );

        self::assertSame(
            2,
            $faixas[1]->ordem,
        );

        self::assertSame(
            2,
            Musica::query()
                ->count(),
        );
    }

    /**
     * Confirma que um artista existente indicado na faixa não é associado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_associa_artista_existente_indicado_na_faixa(): void
    {
        $artista =
            Artista::factory()
                ->create([
                    'nome' => 'Artista Convidado',
                    'discogs_id' => 987654,
                ]);

        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Lançamento de Exemplo',
                    'artists' => [],

                    'tracklist' => [
                        [
                            'position' => '1',
                            'type_' => 'track',
                            'title' => 'Música Convidada',

                            'artists' => [
                                [
                                    'id' => 987654,
                                    'name' => 'Artista Convidado',
                                ],
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        $musica =
            $lancamento
                ->faixas()
                ->firstOrFail()
                ->musica;

        self::assertSame(
            0,
            $musica
                ->artistas()
                ->count(),
        );

        self::assertSame(
            1,
            Artista::query()
                ->whereKey(
                    $artista->getKey(),
                )
                ->count(),
        );
    }

    /**
     * Confirma que um artista inexistente indicado na faixa não é criado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_cria_artista_inexistente_indicado_na_faixa(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Lançamento de Exemplo',
                    'artists' => [],

                    'tracklist' => [
                        [
                            'position' => '1',
                            'type_' => 'track',
                            'title' => 'Música Convidada',

                            'artists' => [
                                [
                                    'id' => 987654,
                                    'name' => 'Artista Convidado',
                                ],
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'discogs_id' => 987654,
            ],
        );

        $musica =
            $lancamento
                ->faixas()
                ->firstOrFail()
                ->musica;

        self::assertSame(
            0,
            $musica
                ->artistas()
                ->count(),
        );
    }

    /**
     * Confirma que um artista eliminado indicado numa faixa não é restaurado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_restaura_artista_eliminado_indicado_na_faixa(): void
    {
        $artista =
            Artista::factory()
                ->create([
                    'nome' => 'Artista Convidado',
                    'discogs_id' => 987654,
                ]);

        $identificadorArtista =
            $artista->getKey();

        $artista->delete();

        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Lançamento de Exemplo',
                    'artists' => [],

                    'tracklist' => [
                        [
                            'position' => '1',
                            'type_' => 'track',
                            'title' => 'Música Convidada',

                            'artists' => [
                                [
                                    'id' => 987654,
                                    'name' => 'Artista Convidado',
                                ],
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        $artistaDepois =
            Artista::withTrashed()
                ->findOrFail(
                    $identificadorArtista,
                );

        self::assertTrue(
            $artistaDepois->trashed(),
        );

        $musica =
            $lancamento
                ->faixas()
                ->firstOrFail()
                ->musica;

        self::assertSame(
            0,
            $musica
                ->artistas()
                ->count(),
        );
    }

    /**
     * Confirma que artistas da Release não são associados às faixas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_associa_artistas_do_lancamento_a_faixa(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',

                    'artists' => [
                        [
                            'id' => 18839,
                            'name' => 'Metallica',
                        ],
                    ],

                    'tracklist' => [
                        [
                            'position' => 'A1',
                            'type_' => 'track',
                            'title' => 'Battery',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        $musica =
            $lancamento
                ->faixas()
                ->firstOrFail()
                ->musica;

        self::assertSame(
            0,
            $musica
                ->artistas()
                ->count(),
        );

        self::assertSame(
            0,
            $lancamento
                ->artistas()
                ->count(),
        );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'discogs_id' => 18839,
            ],
        );
    }

    /**
     * Confirma que artistas próprios da faixa também são ignorados.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_associa_artistas_proprios_da_faixa(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Lançamento de Exemplo',

                    'artists' => [
                        [
                            'id' => 18839,
                            'name' => 'Artista Principal',
                        ],
                    ],

                    'tracklist' => [
                        [
                            'position' => '1',
                            'type_' => 'track',
                            'title' => 'Música Convidada',

                            'artists' => [
                                [
                                    'id' => 987654,
                                    'name' => 'Artista Convidado',
                                ],
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoImportacaoLancamento::class,
            )->importar(
                249504,
            );

        $musica =
            $lancamento
                ->faixas()
                ->firstOrFail()
                ->musica;

        self::assertSame(
            0,
            $musica
                ->artistas()
                ->count(),
        );

        self::assertSame(
            0,
            $lancamento
                ->artistas()
                ->count(),
        );

        self::assertSame(
            0,
            Artista::query()
                ->count(),
        );
    }
}
