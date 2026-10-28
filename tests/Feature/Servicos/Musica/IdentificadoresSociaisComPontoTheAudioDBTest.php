<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IdentificadoresSociaisComPontoTheAudioDBTest extends TestCase
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
    #[DataProvider('ligacoesSociais')]
    public function distingue_identificador_social_de_url(
        string $campo,
        string $valor,
        string $titulo,
        string $enderecoEsperado,
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

        self::assertSame(
            [
                [
                    'titulo' => $titulo,
                    'url' => $enderecoEsperado,
                ],
            ],
            $resultado['ligacoes'],
        );

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function ligacoesSociais(): array
    {
        return [
            'youtube_identificador_com_ponto' => [
                'strYoutube',
                'artista.metal',
                'YouTube',
                'https://www.youtube.com/artista.metal',
            ],
            'instagram_identificador_com_ponto' => [
                'strInstagram',
                'banda.oficial',
                'Instagram',
                'https://www.instagram.com/banda.oficial',
            ],
            'facebook_identificador_com_ponto' => [
                'strFacebook',
                'banda.oficial',
                'Facebook',
                'https://www.facebook.com/banda.oficial',
            ],
            'tiktok_identificador_com_ponto' => [
                'strTikTok',
                'banda.metal',
                'TikTok',
                'https://www.tiktok.com/@banda.metal',
            ],
            'youtube_identificador_com_arroba' => [
                'strYoutube',
                '@artista.metal',
                'YouTube',
                'https://www.youtube.com/@artista.metal',
            ],
            'youtube_dominio_sem_esquema' => [
                'strYoutube',
                'youtube.com/@artista',
                'YouTube',
                'https://youtube.com/@artista',
            ],
            'youtube_www_sem_esquema' => [
                'strYoutube',
                'www.youtube.com/@artista',
                'YouTube',
                'https://www.youtube.com/@artista',
            ],
            'youtube_dominio_sem_caminho' => [
                'strYoutube',
                'youtube.com',
                'YouTube',
                'https://youtube.com',
            ],
            'instagram_dominio_sem_caminho' => [
                'strInstagram',
                'instagram.com',
                'Instagram',
                'https://instagram.com',
            ],
            'youtube_url_completo' => [
                'strYoutube',
                'https://www.youtube.com/@artista',
                'YouTube',
                'https://www.youtube.com/@artista',
            ],
        ];
    }
}
