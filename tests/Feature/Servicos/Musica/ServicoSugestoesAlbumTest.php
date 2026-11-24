<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Models\Musica\Artista;
use App\Servicos\Musica\ServicoSugestoesAlbum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Confirma a correspondência estrita entre a edição Discogs, o grupo
 * MusicBrainz e as sugestões das plataformas musicais.
 */
final class ServicoSugestoesAlbumTest extends TestCase
{
    use RefreshDatabase;

    private const MBID_ARTISTA = '893d4e4c-4951-4e67-aa01-6b5ece048411';

    private const MBID_GRUPO = 'e1a3dcd5-6518-4bc1-a21a-39393196cc21';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('musicbrainz.user_agent', 'MetalThursdayTest/2.0');
        config()->set('musicbrainz.intervalo_minimo_pedidos_ms', 0);
        config()->set('theaudiodb.api_key', '123');
        config()->set('theaudiodb.intervalo_minimo_pedidos_ms', 0);
        config()->set('itunes.intervalo_minimo_pedidos_ms', 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function discogs(): array
    {
        return [
            'titulo' => 'Beyond the Reach of the Sun',
            'ano_original' => 2024,
            'artistas' => [
                ['discogs_id' => 3241259, 'nome' => 'Anciients'],
            ],
        ];
    }

    private function artista(): Artista
    {
        return Artista::factory()->create([
            'nome' => 'Anciients',
            'musicbrainz_id' => self::MBID_ARTISTA,
            'discogs_id' => 3241259,
        ]);
    }

    #[Test]
    public function identifica_album_e_sugere_ligacoes_sem_importar_descricao(): void
    {
        $artista = $this->artista();

        Http::fake(function (Request $pedido) {
            $url = $pedido->url();

            if (str_contains($url, '/ws/2/release-group/?')) {
                return Http::response([
                    'release-groups' => [[
                        'id' => self::MBID_GRUPO,
                        'title' => 'Beyond the Reach of the Sun',
                        'first-release-date' => '2024-08-30',
                        'artist-credit' => [[
                            'artist' => ['id' => self::MBID_ARTISTA],
                        ]],
                    ]],
                ]);
            }

            if (str_contains($url, '/ws/2/release-group/'.self::MBID_GRUPO)) {
                return Http::response([
                    'id' => self::MBID_GRUPO,
                    'relations' => [[
                        'url' => ['resource' => 'https://anciientriffs.bandcamp.com/album/beyond-the-reach-of-the-sun'],
                    ]],
                    'releases' => [['id' => 'f1234567-1111-4111-8111-111111111111']],
                ]);
            }

            if (str_contains($url, '/ws/2/release/f1234567')) {
                return Http::response([
                    'relations' => [[
                        'url' => ['resource' => 'https://open.spotify.com/album/0123456789012345678901'],
                    ]],
                ]);
            }

            if (str_contains($url, 'album-mb.php')) {
                return Http::response([
                    'album' => [[
                        'strMusicBrainzAlbumID' => self::MBID_GRUPO,
                        'strArtist' => 'Anciients',
                        'strAlbum' => 'Beyond the Reach of the Sun',
                        'strDescriptionEN' => 'A description of the studio album.',
                    ]],
                ]);
            }

            if (str_contains($url, 'itunes.apple.com/search')) {
                return Http::response([
                    'results' => [[
                        'wrapperType' => 'collection',
                        'artistName' => 'Anciients',
                        'collectionName' => 'Beyond the Reach of the Sun',
                        'releaseDate' => '2024-08-30T00:00:00Z',
                        'collectionViewUrl' => 'https://music.apple.com/pt/album/beyond-the-reach-of-the-sun/1743465186',
                    ]],
                ]);
            }

            return Http::response([], 404);
        });

        $sugestoes = app(ServicoSugestoesAlbum::class)->obter($artista, $this->discogs());

        self::assertNull($sugestoes['descricao']);
        self::assertNull($sugestoes['descricao_fonte']);
        Http::assertNotSent(fn (Request $pedido): bool => str_contains($pedido->url(), 'theaudiodb'));
        self::assertSame(
            ['Bandcamp — álbum', 'Spotify — álbum', 'Apple Music — álbum'],
            array_column($sugestoes['ligacoes'], 'etiqueta'),
        );
    }

    #[Test]
    public function nao_consulta_outras_fontes_quando_artista_discogs_nao_corresponde(): void
    {
        $artista = $this->artista();
        Http::fake();

        $discogs = $this->discogs();
        $discogs['artistas'][0]['discogs_id'] = 444444;

        $sugestoes = app(ServicoSugestoesAlbum::class)->obter($artista, $discogs);

        self::assertNull($sugestoes['descricao']);
        self::assertSame([], $sugestoes['ligacoes']);
        Http::assertNothingSent();
    }

    #[Test]
    public function nao_confunde_grupo_de_outro_artista_com_o_mesmo_titulo(): void
    {
        $artista = $this->artista();

        Http::fake(function (Request $pedido) {
            if (str_contains($pedido->url(), 'musicbrainz.org')) {
                return Http::response([
                    'release-groups' => [[
                        'id' => self::MBID_GRUPO,
                        'title' => 'Beyond the Reach of the Sun',
                        'artist-credit' => [[
                            'artist' => ['id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'],
                        ]],
                    ]],
                ]);
            }

            return Http::response([
                'results' => [[
                    'wrapperType' => 'collection',
                    'artistName' => 'Outra Banda',
                    'collectionName' => 'Beyond the Reach of the Sun',
                    'collectionViewUrl' => 'https://music.apple.com/pt/album/outra-banda/12345',
                ]],
            ]);
        });

        $sugestoes = app(ServicoSugestoesAlbum::class)->obter($artista, $this->discogs());

        self::assertSame([], $sugestoes['ligacoes']);
        self::assertNull($sugestoes['descricao']);
    }

    #[Test]
    public function uma_fonte_indisponivel_nao_impede_a_sugestao_apple_music(): void
    {
        $artista = $this->artista();

        Http::fake(function (Request $pedido) {
            if (str_contains($pedido->url(), 'musicbrainz.org')) {
                return Http::response([], 503);
            }

            return Http::response([
                'results' => [[
                    'wrapperType' => 'collection',
                    'artistName' => 'Anciients',
                    'collectionName' => 'Beyond the Reach of the Sun',
                    'releaseDate' => '2024-08-30',
                    'collectionViewUrl' => 'https://music.apple.com/pt/album/beyond-the-reach-of-the-sun/1743465186',
                ]],
            ]);
        });

        $sugestoes = app(ServicoSugestoesAlbum::class)->obter($artista, $this->discogs());

        self::assertSame('Apple Music — álbum', $sugestoes['ligacoes'][0]['etiqueta']);
    }
}
