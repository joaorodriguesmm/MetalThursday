<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o enriquecimento de artistas através do TheAudioDB.
 *
 * @since 2.0.0
 */
final class ServicoTheAudioDBTest extends TestCase
{
    /**
     * Configura a integração utilizada pelos testes.
     *
     * Os intervalos temporais são desativados porque todos os pedidos HTTP
     * destes testes são simulados e não atingem o serviço externo.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

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
     * Confirma a normalização dos dados complementares de um artista.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_artista_por_mbid(): void
    {
        Http::fake([
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

                            'intDiedYear' => null,

                            'strDisbanded' => null,

                            'strBiographyPT' => 'Biografia em português.',

                            'strBiographyEN' => 'Biography in English.',

                            'strArtistThumb' => 'https://r2.theaudiodb.com/images/metallica.jpg',

                            'strArtistLogo' => 'https://r2.theaudiodb.com/images/metallica-logo.png',

                            'strWebsite' => 'www.metallica.com',

                            'strFacebook' => 'metallica',

                            'strTwitter' => '1',

                            'strInstagram' => 'metallica',

                            'strYoutube' => 'https://www.youtube.com/@metallica',
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $artista =
            app(
                ServicoTheAudioDB::class,
            )->obterArtistaPorMusicBrainz(
                '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
            );

        self::assertNotNull(
            $artista,
        );

        self::assertSame(
            111279,
            $artista['id'],
        );

        self::assertSame(
            'Metallica',
            $artista['nome'],
        );

        self::assertSame(
            'US',
            $artista['codigo_pais'],
        );

        self::assertSame(
            1981,
            $artista['ano_inicio'],
        );

        self::assertSame(
            'Biografia em português.',
            $artista['biografia'],
        );

        self::assertSame(
            'pt',
            $artista['idioma_biografia'],
        );

        self::assertSame(
            'https://r2.theaudiodb.com/images/metallica.jpg',
            $artista['imagem'],
        );

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',

                    'url' => 'https://www.metallica.com',
                ],
                [
                    'titulo' => 'Facebook',

                    'url' => 'https://www.facebook.com/metallica',
                ],
                [
                    'titulo' => 'Instagram',

                    'url' => 'https://www.instagram.com/metallica',
                ],
                [
                    'titulo' => 'YouTube',

                    'url' => 'https://www.youtube.com/@metallica',
                ],
            ],
            $artista['ligacoes'],
        );
    }

    /**
     * Confirma que a ausência de ficha no TheAudioDB não constitui erro.
     *
     * @since 2.0.0
     */
    #[Test]
    public function devolve_nulo_quando_artista_nao_existe(): void
    {
        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response(
                [
                    'artists' => null,
                ],
                200,
            ),
        ]);

        $artista =
            app(
                ServicoTheAudioDB::class,
            )->obterArtistaPorMusicBrainz(
                '70d5e4d1-31aa-498f-9e9b-177d9349681f',
            );

        self::assertNull(
            $artista,
        );
    }
}
