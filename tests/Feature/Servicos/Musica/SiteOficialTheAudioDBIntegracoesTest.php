<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoImportacaoArtista;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SiteOficialTheAudioDBIntegracoesTest extends TestCase
{
    private const MBID = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

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
    #[DataProvider('enderecosSiteOficial')]
    public function recupera_site_oficial_theaudiodb_quando_musicbrainz_nao_tem(
        string $enderecoOriginal,
        string $enderecoEsperado,
    ): void {
        $proposta = $this->obterProposta(
            [],
            $enderecoOriginal,
        );

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',
                    'url' => $enderecoEsperado,
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function enderecosSiteOficial(): array
    {
        return [
            'endereco_com_https' => [
                'https://artista-exemplo.pt/',
                'https://artista-exemplo.pt/',
            ],
            'endereco_sem_esquema' => [
                'artista-exemplo.pt/',
                'https://artista-exemplo.pt/',
            ],
        ];
    }

    #[Test]
    public function musicbrainz_mantem_prioridade_no_site_oficial(): void
    {
        $proposta = $this->obterProposta(
            [
                self::relacao(
                    'official homepage',
                    'https://site-musicbrainz.pt/',
                ),
            ],
            'https://site-theaudiodb.pt/',
        );

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',
                    'url' => 'https://site-musicbrainz.pt/',
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    #[Test]
    public function theaudiodb_nao_importa_discogs_como_site_oficial(): void
    {
        $proposta = $this->obterProposta(
            [],
            'https://www.discogs.com/artist/18839-Exemplo',
        );

        self::assertSame([], $proposta['ligacoes']);
    }

    #[Test]
    public function theaudiodb_nao_importa_musicbrainz_como_site_oficial(): void
    {
        $proposta = $this->obterProposta(
            [],
            'https://musicbrainz.org/artist/'.self::MBID,
        );

        self::assertSame([], $proposta['ligacoes']);
    }

    #[Test]
    public function theaudiodb_nao_duplica_bandcamp_musicbrainz(): void
    {
        $bandcamp = 'https://artista-exemplo.bandcamp.com/';

        $proposta = $this->obterProposta(
            [
                self::relacao('bandcamp', $bandcamp),
            ],
            $bandcamp,
        );

        self::assertSame(
            [
                [
                    'titulo' => 'Bandcamp',
                    'url' => $bandcamp,
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    #[Test]
    public function theaudiodb_complementa_bandcamp_musicbrainz(): void
    {
        $bandcamp = 'https://artista-exemplo.bandcamp.com/';
        $siteOficial = 'https://artista-exemplo.pt/';

        $proposta = $this->obterProposta(
            [
                self::relacao('bandcamp', $bandcamp),
            ],
            $siteOficial,
        );

        self::assertSame(
            [
                [
                    'titulo' => 'Site oficial',
                    'url' => $siteOficial,
                ],
                [
                    'titulo' => 'Bandcamp',
                    'url' => $bandcamp,
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    #[Test]
    public function site_theaudiodb_em_plataforma_conhecida_mantem_categoria(): void
    {
        $proposta = $this->obterProposta(
            [],
            'https://artista-exemplo.bandcamp.com/',
        );

        self::assertSame(
            [
                [
                    'titulo' => 'Bandcamp',
                    'url' => 'https://artista-exemplo.bandcamp.com/',
                ],
            ],
            $proposta['ligacoes'],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $relacoes
     * @return array<string, mixed>
     */
    private function obterProposta(
        array $relacoes,
        string $siteTheAudioDB,
    ): array {
        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.self::MBID.'*' => Http::response([
                'id' => self::MBID,
                'name' => 'Artista de teste',
                'relations' => $relacoes,
            ]),
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => self::MBID,
                        'strArtist' => 'Artista de teste',
                        'strWebsite' => $siteTheAudioDB,
                    ],
                ],
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
