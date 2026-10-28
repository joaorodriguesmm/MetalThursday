<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoImportacaoArtista;
use App\Servicos\Musica\ServicoMusicBrainz;
use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProtecaoCredenciaisUrlsIntegracoesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    public function musicbrainz_rejeita_ligacao_com_credenciais(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.$mbid.'*' => Http::response([
                'id' => $mbid,
                'name' => 'Artista de teste',
                'relations' => [
                    [
                        'type' => 'official homepage',
                        'url' => [
                            'resource' => 'https://utilizador:segredo@example.com/oficial',
                        ],
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoMusicBrainz::class)
            ->obterArtista($mbid);

        self::assertSame([], $resultado['relacoes']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function theaudiodb_rejeita_imagem_e_ligacao_com_credenciais(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => $mbid,
                        'strArtist' => 'Artista de teste',
                        'strArtistThumb' => 'https://utilizador:segredo@example.com/imagem.jpg',
                        'strWebsite' => 'https://utilizador:segredo@exemplo.bandcamp.com/',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz($mbid);

        self::assertIsArray($resultado);
        self::assertNull($resultado['imagem']);
        self::assertSame([], $resultado['ligacoes']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function proposta_agregada_nao_expoe_urls_com_credenciais(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.$mbid.'*' => Http::response([
                'id' => $mbid,
                'name' => 'Artista de teste',
                'relations' => [
                    [
                        'type' => 'official homepage',
                        'url' => [
                            'resource' => 'https://utilizador:segredo@example.com/',
                        ],
                    ],
                ],
            ]),

            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => $mbid,
                        'strArtist' => 'Artista de teste',
                        'strArtistThumb' => 'https://utilizador:segredo@example.com/imagem.jpg',
                        'strWebsite' => 'https://utilizador:segredo@exemplo.bandcamp.com/',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoImportacaoArtista::class)
            ->obterProposta($mbid);

        self::assertSame($mbid, $resultado['musicbrainz_id']);
        self::assertNull($resultado['imagem']);
        self::assertSame([], $resultado['ligacoes']);

        Http::assertSentCount(2);
    }

    #[Test]
    public function theaudiodb_nao_recicla_url_inseguro_como_identificador_social(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => $mbid,
                        'strArtist' => 'Artista de teste',
                        'strYoutube' => 'https://utilizador:segredo@www.youtube.com/@artista',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz($mbid);

        self::assertIsArray($resultado);
        self::assertSame([], $resultado['ligacoes']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function agregador_nao_reintroduz_credenciais_em_url_youtube(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/'.$mbid.'*' => Http::response([
                'id' => $mbid,
                'name' => 'Artista de teste',
            ]),

            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => $mbid,
                        'strArtist' => 'Artista de teste',
                        'strYoutube' => 'https://utilizador:segredo@www.youtube.com/@artista',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoImportacaoArtista::class)
            ->obterProposta($mbid);

        self::assertSame([], $resultado['ligacoes']);

        Http::assertSentCount(2);
    }

    #[Test]
    public function theaudiodb_nao_recicla_url_com_credenciais_sem_esquema(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => $mbid,
                        'strArtist' => 'Artista de teste',
                        'strYoutube' => 'utilizador:segredo@www.youtube.com/@artista',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz($mbid);

        self::assertIsArray($resultado);
        self::assertSame([], $resultado['ligacoes']);

        Http::assertSentCount(1);
    }

    #[Test]
    public function theaudiodb_preserva_identificadores_sociais_validos(): void
    {
        $mbid = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => $mbid,
                        'strArtist' => 'Artista de teste',
                        'strFacebook' => 'metallica_rocks',
                        'strYoutube' => '@artista-teste',
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz($mbid);

        self::assertIsArray($resultado);

        self::assertSame(
            [
                [
                    'titulo' => 'Facebook',
                    'url' => 'https://www.facebook.com/metallica_rocks',
                ],
                [
                    'titulo' => 'YouTube',
                    'url' => 'https://www.youtube.com/@artista-teste',
                ],
            ],
            $resultado['ligacoes'],
        );

        Http::assertSentCount(1);
    }
}
