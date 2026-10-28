<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DominiosAlternativosSemCaminhoTheAudioDBTest extends TestCase
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
    #[DataProvider('dominiosSociais')]
    public function preserva_dominios_sociais_alternativos(
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
    public static function dominiosSociais(): array
    {
        return [
            'twitter_dominio_sem_caminho' => [
                'strTwitter',
                'twitter.com',
                'X',
                'https://twitter.com',
            ],
            'youtube_dominio_curto_sem_caminho' => [
                'strYoutube',
                'youtu.be',
                'YouTube',
                'https://youtu.be',
            ],
            'twitter_dominio_com_caminho' => [
                'strTwitter',
                'twitter.com/artista',
                'X',
                'https://twitter.com/artista',
            ],
            'youtube_dominio_curto_com_caminho' => [
                'strYoutube',
                'youtu.be/abc123',
                'YouTube',
                'https://youtu.be/abc123',
            ],
            'twitter_dominio_atual_sem_caminho' => [
                'strTwitter',
                'x.com',
                'X',
                'https://x.com',
            ],
            'youtube_dominio_principal_sem_caminho' => [
                'strYoutube',
                'youtube.com',
                'YouTube',
                'https://youtube.com',
            ],
        ];
    }
}
