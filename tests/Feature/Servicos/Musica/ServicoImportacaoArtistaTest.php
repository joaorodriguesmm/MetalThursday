<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoImportacaoArtista;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a agregação das fontes externas dos artistas.
 *
 * @since 2.0.0
 */
final class ServicoImportacaoArtistaTest extends TestCase
{
    /**
     * Configura as integrações utilizadas nos testes.
     *
     * Os intervalos temporais são desativados porque todos os pedidos HTTP
     * destes testes são simulados e não atingem os serviços externos.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'musicbrainz.base_url',
            'https://musicbrainz.org',
        );

        config()->set(
            'musicbrainz.user_agent',
            'MetalThursdayTest/2.0',
        );

        config()->set(
            'musicbrainz.intervalo_repeticao_ms',
            0,
        );

        config()->set(
            'musicbrainz.intervalo_minimo_pedidos_ms',
            0,
        );

        config()->set(
            'theaudiodb.base_url',
            'https://www.theaudiodb.com',
        );

        config()->set(
            'theaudiodb.api_key',
            '123',
        );

        config()->set(
            'theaudiodb.intervalo_repeticao_ms',
            0,
        );

        config()->set(
            'theaudiodb.intervalo_minimo_pedidos_ms',
            0,
        );
    }

    /**
     * Confirma a combinação do MusicBrainz com o TheAudioDB e a filtragem das
     * ligações.
     *
     * Apenas as categorias relevantes são propostas e cada categoria aparece
     * no máximo uma vez. A relação Discogs é conservada apenas através do
     * identificador fornecido pelo MusicBrainz.
     *
     * @since 2.0.0
     */
    #[Test]
    public function agrega_dados_e_importa_apenas_ligacoes_relevantes(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab*' => Http::response(
                [
                    'id' => '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',

                    'name' => 'Metallica',

                    'type' => 'Group',

                    'country' => 'US',

                    'area' => [
                        'name' => 'United States',
                    ],

                    'begin-area' => [
                        'name' => 'Los Angeles',
                    ],

                    'life-span' => [
                        'begin' => '1981-10-28',

                        'ended' => false,
                    ],

                    'relations' => [
                        [
                            'type' => 'official homepage',

                            'url' => [
                                'resource' => 'https://www.metallica.com/',
                            ],
                        ],
                        [
                            'type' => 'purchase for download',

                            'url' => [
                                'resource' => 'https://metallica.bandcamp.com/',
                            ],
                        ],
                        [
                            'type' => 'social network',

                            'url' => [
                                'resource' => 'https://www.instagram.com/metallica/',
                            ],
                        ],
                        [
                            'type' => 'streaming music',

                            'url' => [
                                'resource' => 'https://open.spotify.com/artist/2ye2Wgw4gimLv2eAKyk1NB',
                            ],
                        ],
                        [
                            'type' => 'streaming music',

                            'url' => [
                                'resource' => 'https://music.apple.com/us/artist/metallica/3996865',
                            ],
                        ],
                        [
                            'type' => 'discogs',

                            'url' => [
                                'resource' => 'https://www.discogs.com/artist/18839-Metallica',
                            ],
                        ],
                        [
                            'type' => 'other databases',

                            'url' => [
                                'resource' => 'https://www.metal-archives.com/bands/Metallica/125',
                            ],
                        ],
                        [
                            'type' => 'other databases',

                            'url' => [
                                'resource' => 'https://www.allmusic.com/artist/mn0000446509',
                            ],
                        ],
                    ],
                ],
                200,
            ),

            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response(
                [
                    'artists' => [
                        [
                            'idArtist' => '111279',

                            'strArtist' => 'Metallica',

                            'strMusicBrainzID' => '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',

                            'strCountry' => 'Los Angeles, USA',

                            'strCountryCode' => 'US',

                            'intFormedYear' => '1981',

                            'strBiographyPT' => 'Biografia proveniente do TheAudioDB.',

                            'strArtistThumb' => 'https://r2.theaudiodb.com/images/metallica.jpg',

                            'strWebsite' => 'www.metallica.com',

                            'strTwitter' => '1',

                            'strYoutube' => 'https://www.youtube.com/@metallica',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $resultado =
            app(
                ServicoImportacaoArtista::class,
            )->obterProposta(
                '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
            );

        self::assertSame(
            '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
            $resultado['musicbrainz_id'],
        );

        self::assertSame(
            18839,
            $resultado['discogs_id'],
        );

        self::assertSame(
            'https://www.discogs.com/artist/18839',
            $resultado['url_discogs'],
        );

        self::assertSame(
            'Metallica',
            $resultado['nome'],
        );

        self::assertSame(
            'US',
            $resultado['origem']['codigo_pais'],
        );

        self::assertSame(
            'Los Angeles',
            $resultado['origem']['area_inicio'],
        );

        self::assertSame(
            1981,
            $resultado['ano_inicio_atividade'],
        );

        self::assertSame(
            'ativo',
            $resultado['estado_atividade'],
        );

        self::assertSame(
            'Biografia proveniente do TheAudioDB.',
            $resultado['biografia'],
        );

        self::assertSame(
            'https://r2.theaudiodb.com/images/metallica.jpg',
            $resultado['imagem'],
        );

        self::assertSame(
            'theaudiodb',
            $resultado['fontes']['biografia'],
        );

        self::assertSame(
            'theaudiodb',
            $resultado['fontes']['imagem'],
        );

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',

                    'url' => 'https://www.metallica.com/',
                ],
                [
                    'titulo' => 'Bandcamp',

                    'url' => 'https://metallica.bandcamp.com/',
                ],
                [
                    'titulo' => 'YouTube',

                    'url' => 'https://www.youtube.com/@metallica',
                ],
                [
                    'titulo' => 'Spotify',

                    'url' => 'https://open.spotify.com/artist/2ye2Wgw4gimLv2eAKyk1NB',
                ],
                [
                    'titulo' => 'Apple Music',

                    'url' => 'https://music.apple.com/us/artist/metallica/3996865',
                ],
                [
                    'titulo' => 'Metal Archives',

                    'url' => 'https://www.metal-archives.com/bands/Metallica/125',
                ],
            ],
            $resultado['ligacoes'],
        );

        Http::assertNotSent(
            static fn (
                Request $pedido,
            ): bool => str_contains(
                $pedido->url(),
                'api.discogs.com',
            ),
        );
    }

    /**
     * Confirma que a ausência de dados no TheAudioDB não desencadeia uma
     * consulta ao Discogs.
     *
     * O identificador Discogs continua disponível porque é extraído da relação
     * publicada pelo MusicBrainz, mas a biografia e a imagem permanecem vazias.
     *
     * @since 2.0.0
     */
    #[Test]
    public function conserva_discogs_id_sem_consultar_api_discogs(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/70d5e4d1-31aa-498f-9e9b-177d9349681f*' => Http::response(
                [
                    'id' => '70d5e4d1-31aa-498f-9e9b-177d9349681f',

                    'name' => 'Black Cilice',

                    'country' => 'PT',

                    'life-span' => [
                        'begin' => '2007',

                        'end' => '2020',

                        'ended' => true,
                    ],

                    'relations' => [
                        [
                            'type' => 'discogs',

                            'url' => [
                                'resource' => 'https://www.discogs.com/artist/98765-Black-Cilice',
                            ],
                        ],
                    ],
                ],
                200,
            ),

            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response(
                [
                    'artists' => null,
                ],
                200,
            ),
        ]);

        $resultado =
            app(
                ServicoImportacaoArtista::class,
            )->obterProposta(
                '70d5e4d1-31aa-498f-9e9b-177d9349681f',
            );

        self::assertSame(
            'terminado',
            $resultado['estado_atividade'],
        );

        self::assertSame(
            2007,
            $resultado['ano_inicio_atividade'],
        );

        self::assertSame(
            2020,
            $resultado['ano_fim_atividade'],
        );

        self::assertSame(
            98765,
            $resultado['discogs_id'],
        );

        self::assertSame(
            'https://www.discogs.com/artist/98765',
            $resultado['url_discogs'],
        );

        self::assertNull(
            $resultado['biografia'],
        );

        self::assertNull(
            $resultado['imagem'],
        );

        self::assertNull(
            $resultado['fontes']['biografia'],
        );

        self::assertNull(
            $resultado['fontes']['imagem'],
        );

        self::assertSame(
            [],
            $resultado['ligacoes'],
        );

        Http::assertNotSent(
            static fn (
                Request $pedido,
            ): bool => str_contains(
                $pedido->url(),
                'api.discogs.com',
            ),
        );
    }
}
