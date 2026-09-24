<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoDiscogs;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a integração isolada com o Discogs.
 *
 * @since 2.0.0
 */
final class ServicoDiscogsTest extends TestCase
{
    /**
     * Configura a integração utilizada pelos testes.
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

        config()->set(
            'discogs.token',
            'token-de-teste',
        );
    }

    /**
     * Confirma a obtenção de uma edição concreta pelo identificador Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_lancamento_por_identificador(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master of Puppets',
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            249504,
            $lancamento['discogs_release_id'],
        );

        self::assertSame(
            'Master of Puppets',
            $lancamento['titulo'],
        );

        Http::assertSent(
            static fn (
                Request $pedido,
            ): bool => $pedido->url()
                === 'https://api.discogs.com/releases/249504'
                && $pedido->hasHeader(
                    'User-Agent',
                    'MetalThursdayTest/2.0',
                ),
        );
    }

    /**
     * Confirma a normalização da tracklist da edição concreta.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_tracklist_do_lancamento(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,

                    'title' => 'Master of Puppets',

                    'tracklist' => [
                        [
                            'position' => 'A1',
                            'type_' => 'track',
                            'title' => 'Battery',
                        ],
                        [
                            'position' => 'A2',
                            'type_' => 'track',
                            'title' => 'Master of Puppets',
                        ],
                        [
                            'position' => 'A3',
                            'type_' => 'track',
                            'title' => 'The Thing That Should Not Be',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            [
                [
                    'titulo' => 'Battery',
                    'posicao' => 'A1',
                    'ordem' => 1,
                    'artistas' => [],
                ],
                [
                    'titulo' => 'Master of Puppets',
                    'posicao' => 'A2',
                    'ordem' => 2,
                    'artistas' => [],
                ],
                [
                    'titulo' => 'The Thing That Should Not Be',
                    'posicao' => 'A3',
                    'ordem' => 3,
                    'artistas' => [],
                ],
            ],
            $lancamento['faixas'],
        );
    }

    /**
     * Confirma a pesquisa de edições concretas no Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_lancamentos(): void
    {
        Http::fake([
            'https://api.discogs.com/database/search*' => Http::response(
                [
                    'results' => [
                        [
                            'id' => 249504,
                            'type' => 'release',
                            'title' => 'Metallica - Master Of Puppets',
                        ],
                        [
                            'id' => 123456,
                            'type' => 'release',
                            'title' => 'Metallica - Master Of Puppets',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $resultados =
            app(
                ServicoDiscogs::class,
            )->pesquisarLancamentos(
                'Master of Puppets',
            );

        self::assertSame(
            [
                [
                    'discogs_release_id' => 249504,
                    'titulo' => 'Metallica - Master Of Puppets',
                    'ano' => null,
                    'pais' => null,
                    'formatos' => [],
                ],
                [
                    'discogs_release_id' => 123456,
                    'titulo' => 'Metallica - Master Of Puppets',
                    'ano' => null,
                    'pais' => null,
                    'formatos' => [],
                ],
            ],
            $resultados,
        );

        Http::assertSent(
            static fn (
                Request $pedido,
            ): bool => str_starts_with(
                $pedido->url(),
                'https://api.discogs.com/database/search?',
            )
                && $pedido['q'] === 'Master of Puppets'
                && $pedido['type'] === 'release'
                && (int) $pedido['per_page'] === 10
                && $pedido->hasHeader(
                    'Authorization',
                    'Discogs token=token-de-teste',
                )
                && $pedido->hasHeader(
                    'User-Agent',
                    'MetalThursdayTest/2.0',
                ),
        );
    }

    /**
     * Confirma que a tracklist ignora títulos descritivos e inclui subfaixas.
     *
     * @since 2.0.0
     */
    #[Test]
    public function normaliza_elementos_estruturais_da_tracklist(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,

                    'title' => 'Exemplo Estruturado',

                    'tracklist' => [
                        [
                            'position' => '1',
                            'type_' => 'track',
                            'title' => 'Primeira Música',
                        ],
                        [
                            'position' => '',
                            'type_' => 'heading',
                            'title' => 'Parte Dois',
                        ],
                        [
                            'position' => '',
                            'type_' => 'index',
                            'title' => 'Suite',
                            'sub_tracks' => [
                                [
                                    'position' => '2.1',
                                    'type_' => 'track',
                                    'title' => 'Primeiro Movimento',
                                ],
                                [
                                    'position' => '2.2',
                                    'type_' => 'track',
                                    'title' => 'Segundo Movimento',
                                ],
                            ],
                        ],
                        [
                            'position' => '3',
                            'type_' => 'track',
                            'title' => 'Última Música',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            [
                [
                    'titulo' => 'Primeira Música',
                    'posicao' => '1',
                    'ordem' => 1,
                    'artistas' => [],
                ],
                [
                    'titulo' => 'Primeiro Movimento',
                    'posicao' => '2.1',
                    'ordem' => 2,
                    'artistas' => [],
                ],
                [
                    'titulo' => 'Segundo Movimento',
                    'posicao' => '2.2',
                    'ordem' => 3,
                    'artistas' => [],
                ],
                [
                    'titulo' => 'Última Música',
                    'posicao' => '3',
                    'ordem' => 4,
                    'artistas' => [],
                ],
            ],
            $lancamento['faixas'],
        );
    }

    /**
     * Confirma que a pesquisa preserva dados úteis para distinguir edições.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_lancamentos_com_dados_de_desambiguacao(): void
    {
        Http::fake([
            'https://api.discogs.com/database/search*' => Http::response(
                [
                    'results' => [
                        [
                            'id' => 249504,
                            'type' => 'release',
                            'title' => 'Metallica - Master Of Puppets',
                            'year' => '1986',
                            'country' => 'US',
                            'format' => [
                                'Vinyl',
                                'LP',
                                'Album',
                            ],
                        ],
                        [
                            'id' => 123456,
                            'type' => 'release',
                            'title' => 'Metallica - Master Of Puppets',
                            'year' => '1991',
                            'country' => 'UK',
                            'format' => [
                                'CD',
                                'Album',
                                'Reissue',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $resultados =
            app(
                ServicoDiscogs::class,
            )->pesquisarLancamentos(
                'Master of Puppets',
            );

        self::assertSame(
            [
                [
                    'discogs_release_id' => 249504,
                    'titulo' => 'Metallica - Master Of Puppets',
                    'ano' => 1986,
                    'pais' => 'US',
                    'formatos' => [
                        'Vinyl',
                        'LP',
                        'Album',
                    ],
                ],
                [
                    'discogs_release_id' => 123456,
                    'titulo' => 'Metallica - Master Of Puppets',
                    'ano' => 1991,
                    'pais' => 'UK',
                    'formatos' => [
                        'CD',
                        'Album',
                        'Reissue',
                    ],
                ],
            ],
            $resultados,
        );
    }

    /**
     * Confirma que a pesquisa pode ser restringida ao artista indicado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_lancamentos_por_artista(): void
    {
        Http::fake([
            'https://api.discogs.com/database/search*' => Http::response(
                [
                    'results' => [
                        [
                            'id' => 249504,
                            'type' => 'release',
                            'title' => 'Metallica - Master Of Puppets',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $resultados =
            app(
                ServicoDiscogs::class,
            )->pesquisarLancamentos(
                'Master of Puppets',
                'Metallica',
            );

        self::assertCount(
            1,
            $resultados,
        );

        Http::assertSent(
            static fn (
                Request $pedido,
            ): bool => $pedido['q'] === 'Master of Puppets'
                && $pedido['artist'] === 'Metallica'
                && $pedido['type'] === 'release'
                && (int) $pedido['per_page'] === 10,
        );
    }

    /**
     * Confirma a normalização dos artistas principais da edição concreta.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_artistas_do_lancamento(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,

                    'title' => 'Split de Exemplo',

                    'artists' => [
                        [
                            'id' => 18839,
                            'name' => 'Metallica',
                        ],
                        [
                            'id' => 987654,
                            'name' => 'Artista Convidado',
                        ],
                    ],

                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $lancamento =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            [
                [
                    'discogs_id' => 18839,
                    'nome' => 'Metallica',
                ],
                [
                    'discogs_id' => 987654,
                    'nome' => 'Artista Convidado',
                ],
            ],
            $lancamento['artistas'],
        );
    }

    /**
     * Confirma a normalização dos artistas específicos de cada faixa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_artistas_especificos_das_faixas(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,

                    'title' => 'Split de Exemplo',

                    'artists' => [
                        [
                            'id' => 100,
                            'name' => 'Artista Principal',
                        ],
                    ],

                    'tracklist' => [
                        [
                            'position' => 'A1',
                            'type_' => 'track',
                            'title' => 'Primeira Música',

                            'artists' => [
                                [
                                    'id' => 200,
                                    'name' => 'Primeiro Artista',
                                ],
                            ],
                        ],
                        [
                            'position' => 'B1',
                            'type_' => 'track',
                            'title' => 'Segunda Música',

                            'artists' => [
                                [
                                    'id' => 300,
                                    'name' => 'Segundo Artista',
                                ],
                                [
                                    'id' => 400,
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
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            [
                [
                    'discogs_id' => 200,
                    'nome' => 'Primeiro Artista',
                ],
            ],
            $lancamento['faixas'][0]['artistas'],
        );

        self::assertSame(
            [
                [
                    'discogs_id' => 300,
                    'nome' => 'Segundo Artista',
                ],
                [
                    'discogs_id' => 400,
                    'nome' => 'Artista Convidado',
                ],
            ],
            $lancamento['faixas'][1]['artistas'],
        );
    }

    /**
     * Confirma os dados de identificação da edição concreta.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_dados_de_identificacao_do_lancamento(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,

                    'title' => 'Master Of Puppets',

                    'year' => 1986,

                    'country' => 'US',

                    'formats' => [
                        [
                            'name' => 'Vinyl',

                            'qty' => '1',

                            'descriptions' => [
                                'LP',
                                'Album',
                            ],
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
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            1986,
            $lancamento['ano'],
        );

        self::assertSame(
            'US',
            $lancamento['pais'],
        );

        self::assertSame(
            [
                'Vinyl',
                'LP',
                'Album',
            ],
            $lancamento['formatos'],
        );
    }

    /**
     * Confirma que um pedido bem-sucedido não é repetido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_repete_pedido_bem_sucedido(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                ],
                200,
            ),
        ]);

        app(
            ServicoDiscogs::class,
        )->obterLancamento(
            249504,
        );

        Http::assertSentCount(
            1,
        );
    }

    /**
     * Confirma a repetição de um pedido perante indisponibilidade transitória.
     *
     * @since 2.0.0
     */
    #[Test]
    public function repete_pedido_perante_indisponibilidade_transitoria(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::sequence()
                ->push(
                    [],
                    503,
                )
                ->push(
                    [
                        'id' => 249504,
                        'title' => 'Master Of Puppets',
                    ],
                    200,
                ),
        ]);

        $lancamento =
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );

        self::assertSame(
            249504,
            $lancamento['discogs_release_id'],
        );

        Http::assertSentCount(
            2,
        );
    }

    /**
     * Confirma que o limite do fornecedor não desencadeia uma nova tentativa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_repete_pedido_quando_discogs_atinge_limite(): void
    {
        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::sequence()
                ->push(
                    [],
                    429,
                )
                ->push(
                    [
                        'id' => 249504,
                        'title' => 'Master Of Puppets',
                    ],
                    200,
                ),
        ]);

        $this->expectException(
            \RuntimeException::class,
        );

        try {
            app(
                ServicoDiscogs::class,
            )->obterLancamento(
                249504,
            );
        } finally {
            Http::assertSentCount(
                1,
            );
        }
    }
}
