<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoImportacaoArtista;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClassificacaoLigacoesArtistaIntegracoesTest extends TestCase
{
    private const MBID = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

    private const SITE_OFICIAL = 'https://artista-exemplo.pt/';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set('musicbrainz.base_url', 'https://musicbrainz.org');
        config()->set('musicbrainz.user_agent', 'MetalThursdayTest/2.0');
        config()->set('musicbrainz.tentativas', 1);
        config()->set('musicbrainz.intervalo_repeticao_ms', 0);
        config()->set('musicbrainz.intervalo_minimo_pedidos_ms', 0);

        config()->set('theaudiodb.base_url', 'https://www.theaudiodb.com');
        config()->set('theaudiodb.api_key', '123');
        config()->set('theaudiodb.tentativas', 1);
        config()->set('theaudiodb.intervalo_repeticao_ms', 0);
        config()->set('theaudiodb.intervalo_minimo_pedidos_ms', 0);
    }

    #[Test]
    public function discogs_nao_pode_ocupar_categoria_site_oficial(): void
    {
        $proposta = $this->obterProposta([
            self::relacao(
                'official homepage',
                'https://www.discogs.com/artist/18839-Exemplo',
            ),
            self::relacao('official homepage', self::SITE_OFICIAL),
        ]);

        self::assertSame(18839, $proposta['discogs_id']);

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',
                    'url' => self::SITE_OFICIAL,
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    #[Test]
    public function musicbrainz_nao_pode_ocupar_categoria_site_oficial(): void
    {
        $proposta = $this->obterProposta([
            self::relacao(
                'official homepage',
                'https://musicbrainz.org/artist/'.self::MBID,
            ),
            self::relacao('official homepage', self::SITE_OFICIAL),
        ]);

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',
                    'url' => self::SITE_OFICIAL,
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    #[Test]
    #[DataProvider('plataformasComTipoOficial')]
    public function plataforma_conhecida_nao_e_classificada_como_site_oficial(
        string $url,
        string $titulo,
    ): void {
        $proposta = $this->obterProposta([
            self::relacao('official homepage', $url),
            self::relacao('official homepage', self::SITE_OFICIAL),
        ]);

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',
                    'url' => self::SITE_OFICIAL,
                ],
                [
                    'titulo' => $titulo,
                    'url' => $url,
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function plataformasComTipoOficial(): array
    {
        return [
            'bandcamp' => [
                'https://artista-exemplo.bandcamp.com/',
                'Bandcamp',
            ],
            'youtube' => [
                'https://www.youtube.com/@artista-exemplo',
                'YouTube',
            ],
            'apple_music' => [
                'https://music.apple.com/pt/artist/exemplo/12345',
                'Apple Music',
            ],
            'metal_archives' => [
                'https://www.metal-archives.com/bands/Exemplo/123',
                'Metal Archives',
            ],
            'spotify' => [
                'https://open.spotify.com/artist/12345',
                'Spotify',
            ],
        ];
    }

    #[Test]
    public function primeiras_ligacoes_prevalecem_entre_as_fontes(): void
    {
        $youtubeMusicBrainz =
            'https://www.youtube.com/@artista-musicbrainz';

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.self::MBID.'*' => Http::response([
                'id' => self::MBID,
                'name' => 'Artista de teste',
                'relations' => [
                    self::relacao(
                        'official homepage',
                        self::SITE_OFICIAL,
                    ),
                    self::relacao(
                        'official homepage',
                        'https://outro-site-exemplo.pt/',
                    ),
                    self::relacao(
                        'video channel',
                        $youtubeMusicBrainz,
                    ),
                ],
            ]),
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => self::MBID,
                        'strArtist' => 'Artista de teste',
                        'strYoutube' => 'https://www.youtube.com/@artista-audiodb',
                    ],
                ],
            ]),
        ]);

        $proposta = app(ServicoImportacaoArtista::class)
            ->obterProposta(self::MBID);

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',
                    'url' => self::SITE_OFICIAL,
                ],
                [
                    'titulo' => 'YouTube',
                    'url' => $youtubeMusicBrainz,
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    #[Test]
    public function site_oficial_legitimo_continua_a_ser_importado(): void
    {
        $proposta = $this->obterProposta([
            self::relacao('official homepage', self::SITE_OFICIAL),
        ]);

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',
                    'url' => self::SITE_OFICIAL,
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $relacoes
     * @return array<string, mixed>
     */
    private function obterProposta(array $relacoes): array
    {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.self::MBID.'*' => Http::response([
                'id' => self::MBID,
                'name' => 'Artista de teste',
                'relations' => $relacoes,
            ]),
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => null,
            ]),
        ]);

        return app(ServicoImportacaoArtista::class)
            ->obterProposta(self::MBID);
    }

    /**
     * @return array<string, mixed>
     */
    private static function relacao(
        string $tipo,
        string $url,
    ): array {
        return [
            'type' => $tipo,
            'url' => [
                'resource' => $url,
            ],
        ];
    }
}
