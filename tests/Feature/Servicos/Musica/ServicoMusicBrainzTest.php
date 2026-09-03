<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoMusicBrainz;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa a integração isolada com o MusicBrainz.
 *
 * @since 2.0.0
 */
final class ServicoMusicBrainzTest extends TestCase
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
            'musicbrainz.base_url',
            'https://musicbrainz.org',
        );

        config()->set(
            'musicbrainz.user_agent',
            'MetalThursdayTest/2.0',
        );

        config()->set(
            'musicbrainz.tentativas',
            3,
        );

        config()->set(
            'musicbrainz.intervalo_repeticao_ms',
            0,
        );

        config()->set(
            'musicbrainz.intervalo_minimo_pedidos_ms',
            0,
        );
    }

    /**
     * Confirma a normalização de resultados de pesquisa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_artistas(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/*' => Http::response(
                [
                    'artists' => [
                        [
                            'id' => '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',

                            'type' => 'Group',

                            'score' => 100,

                            'name' => 'Metallica',

                            'country' => 'US',

                            'area' => [
                                'name' => 'United States',
                            ],

                            'begin-area' => [
                                'name' => 'Los Angeles',
                            ],

                            'life-span' => [
                                'begin' => '1981-10-28',

                                'ended' => null,
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $resultados =
            app(
                ServicoMusicBrainz::class,
            )->pesquisarArtistas(
                'Metallica',
            );

        self::assertCount(
            1,
            $resultados,
        );

        self::assertSame(
            '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
            $resultados[0]['mbid'],
        );

        self::assertSame(
            'Metallica',
            $resultados[0]['nome'],
        );

        self::assertSame(
            'Group',
            $resultados[0]['tipo'],
        );

        self::assertSame(
            'US',
            $resultados[0]['codigo_pais'],
        );

        self::assertSame(
            'United States',
            $resultados[0]['area'],
        );

        self::assertSame(
            'Los Angeles',
            $resultados[0]['area_inicio'],
        );

        self::assertSame(
            1981,
            $resultados[0]['ano_inicio'],
        );

        self::assertNull(
            $resultados[0]['ano_fim'],
        );

        self::assertNull(
            $resultados[0]['terminado'],
        );

        Http::assertSent(
            static fn (
                Request $pedido,
            ): bool => $pedido->hasHeader(
                'User-Agent',
                'MetalThursdayTest/2.0',
            )
                && $pedido['query'] === 'artist:"Metallica"'
                && (int) $pedido['limit'] === 10,
        );
    }

    /**
     * Confirma a obtenção de relações externas e do identificador Discogs.
     *
     * O identificador Discogs é obtido exclusivamente através da relação
     * publicada pelo MusicBrainz, sem consultar a API do Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_artista_com_relacoes_externas(): void
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab*' => Http::response(
                [
                    'id' => '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',

                    'name' => 'Metallica',

                    'country' => 'US',

                    'life-span' => [
                        'begin' => '1981',

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
                            'type' => 'discogs',

                            'url' => [
                                'resource' => 'https://www.discogs.com/artist/18839-Metallica',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $artista =
            app(
                ServicoMusicBrainz::class,
            )->obterArtista(
                '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
            );

        self::assertSame(
            18839,
            $artista['discogs_id'],
        );

        self::assertSame(
            false,
            $artista['terminado'],
        );

        self::assertSame(
            'https://www.metallica.com/',
            $artista['relacoes'][0]['url'],
        );

        self::assertSame(
            'https://musicbrainz.org/artist/65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
            $artista['url_musicbrainz'],
        );
    }

    /**
     * Confirma que uma indisponibilidade transitória é repetida.
     *
     * @since 2.0.0
     */
    #[Test]
    public function repete_pedido_perante_indisponibilidade_transitoria(): void
    {
        Http::fakeSequence()
            ->push(
                [
                    'error' => 'busy',
                ],
                503,
            )
            ->push(
                [
                    'artists' => [
                        [
                            'id' => '1d7aa899-5343-4fcf-a79c-1a97bc56b437',

                            'name' => 'Filii Nigrantium Infernalium',

                            'country' => 'PT',

                            'life-span' => [
                                'begin' => '1992',
                            ],
                        ],
                    ],
                ],
                200,
            );

        $resultados =
            app(
                ServicoMusicBrainz::class,
            )->pesquisarArtistas(
                'Filii Nigrantium Infernalium',
            );

        self::assertSame(
            'Filii Nigrantium Infernalium',
            $resultados[0]['nome'],
        );

        self::assertSame(
            1992,
            $resultados[0]['ano_inicio'],
        );

        Http::assertSentCount(
            2,
        );
    }

    /**
     * Confirma que um MBID inválido nunca é enviado à API.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_mbid_invalido(): void
    {
        Http::fake();

        $this->expectException(
            RuntimeException::class,
        );

        try {
            app(
                ServicoMusicBrainz::class,
            )->obterArtista(
                'identificador-invalido',
            );
        } finally {
            Http::assertNothingSent();
        }
    }
}
