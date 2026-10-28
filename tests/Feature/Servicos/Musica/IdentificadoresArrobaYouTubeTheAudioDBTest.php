<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class IdentificadoresArrobaYouTubeTheAudioDBTest extends TestCase
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
    #[DataProvider('identificadoresSociais')]
    public function preserva_formato_correto_do_identificador(
        string $campo,
        string $valor,
        string $titulo,
        string $urlEsperado,
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
                    'url' => $urlEsperado,
                ],
            ],
            $resultado['ligacoes'],
        );

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function identificadoresSociais(): array
    {
        return [
            'youtube_handle_simples' => [
                'strYoutube',
                '@artista',
                'YouTube',
                'https://www.youtube.com/@artista',
            ],
            'youtube_handle_com_ponto' => [
                'strYoutube',
                '@banda.metal',
                'YouTube',
                'https://www.youtube.com/@banda.metal',
            ],
            'youtube_identificador_legado' => [
                'strYoutube',
                'artista',
                'YouTube',
                'https://www.youtube.com/artista',
            ],
            'youtube_url_handle_completo' => [
                'strYoutube',
                'https://www.youtube.com/@artista',
                'YouTube',
                'https://www.youtube.com/@artista',
            ],
            'tiktok_handle' => [
                'strTikTok',
                '@banda.metal',
                'TikTok',
                'https://www.tiktok.com/@banda.metal',
            ],
            'instagram_handle' => [
                'strInstagram',
                '@banda.metal',
                'Instagram',
                'https://www.instagram.com/banda.metal',
            ],
        ];
    }
}
