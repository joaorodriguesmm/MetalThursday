<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DominiosSociaisTheAudioDBTest extends TestCase
{
    private const MBID = '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set('theaudiodb.base_url', 'https://www.theaudiodb.com');
        config()->set('theaudiodb.api_key', '123');
        config()->set('theaudiodb.tentativas', 1);
        config()->set('theaudiodb.intervalo_repeticao_ms', 0);
        config()->set('theaudiodb.intervalo_minimo_pedidos_ms', 0);
    }

    #[Test]
    #[DataProvider('casosDominioSocial')]
    public function aceita_apenas_dominios_da_rede_social(
        string $campo,
        string $valor,
        ?string $tituloEsperado,
        ?string $urlEsperado,
    ): void {
        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => self::MBID,
                        'strArtist' => 'Artista de teste',
                        $campo => $valor,
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(self::MBID);

        self::assertIsArray($resultado);

        $esperado = $tituloEsperado === null
            ? []
            : [
                [
                    'titulo' => $tituloEsperado,
                    'url' => $urlEsperado,
                ],
            ];

        self::assertSame($esperado, $resultado['ligacoes']);

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, array{string, string, string|null, string|null}>
     */
    public static function casosDominioSocial(): array
    {
        return [
            'youtube_aponta_instagram' => [
                'strYoutube',
                'https://www.instagram.com/outra-banda',
                null,
                null,
            ],
            'instagram_aponta_youtube' => [
                'strInstagram',
                'https://www.youtube.com/@outra-banda',
                null,
                null,
            ],
            'facebook_aponta_spotify' => [
                'strFacebook',
                'https://open.spotify.com/artist/12345',
                null,
                null,
            ],
            'tiktok_aponta_musicbrainz' => [
                'strTikTok',
                'https://musicbrainz.org/artist/'.self::MBID,
                null,
                null,
            ],
            'youtube_dominio_imitador' => [
                'strYoutube',
                'https://youtube.com.evil.example/@artista',
                null,
                null,
            ],
            'youtube_dominio_semelhante' => [
                'strYoutube',
                'https://notyoutube.com/@artista',
                null,
                null,
            ],
            'youtube_subdominio_legitimo' => [
                'strYoutube',
                'https://m.youtube.com/@artista',
                'YouTube',
                'https://m.youtube.com/@artista',
            ],
            'twitter_subdominio_legitimo' => [
                'strTwitter',
                'https://www.twitter.com/artista',
                'X',
                'https://www.twitter.com/artista',
            ],
            'youtube_legitimo' => [
                'strYoutube',
                'https://www.youtube.com/@artista',
                'YouTube',
                'https://www.youtube.com/@artista',
            ],
            'youtube_dominio_curto' => [
                'strYoutube',
                'https://youtu.be/abc123',
                'YouTube',
                'https://youtu.be/abc123',
            ],
            'twitter_dominio_antigo' => [
                'strTwitter',
                'twitter.com/artista',
                'X',
                'https://twitter.com/artista',
            ],
            'twitter_dominio_atual' => [
                'strTwitter',
                'https://x.com/artista',
                'X',
                'https://x.com/artista',
            ],
        ];
    }
}
