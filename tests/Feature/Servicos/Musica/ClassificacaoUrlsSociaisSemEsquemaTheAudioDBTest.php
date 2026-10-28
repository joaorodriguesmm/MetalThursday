<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClassificacaoUrlsSociaisSemEsquemaTheAudioDBTest extends TestCase
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
    #[DataProvider('casosClassificacao')]
    public function classifica_urls_e_identificadores_corretamente(
        string $campo,
        string $entrada,
        ?string $titulo,
        ?string $urlEsperado,
    ): void {
        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [
                    [
                        'strMusicBrainzID' => self::MBID,
                        'strArtist' => 'Artista de teste',
                        $campo => $entrada,
                    ],
                ],
            ]),
        ]);

        $resultado = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz(self::MBID);

        self::assertIsArray($resultado);

        $ligacoesEsperadas = $urlEsperado === null
            ? []
            : [
                [
                    'titulo' => $titulo,
                    'url' => $urlEsperado,
                ],
            ];

        self::assertSame(
            $ligacoesEsperadas,
            $resultado['ligacoes'],
        );

        Http::assertSentCount(1);
    }

    /**
     * @return array<string, array{string, string, string|null, string|null}>
     */
    public static function casosClassificacao(): array
    {
        return [
            'twitter_subdominio_sem_caminho' => [
                'strTwitter',
                'mobile.twitter.com',
                'X',
                'https://mobile.twitter.com',
            ],
            'youtube_curto_subdominio_sem_caminho' => [
                'strYoutube',
                'm.youtu.be',
                'YouTube',
                'https://m.youtu.be',
            ],
            'twitter_dominio_com_query' => [
                'strTwitter',
                'twitter.com?lang=pt',
                'X',
                'https://twitter.com?lang=pt',
            ],
            'youtube_dominio_com_query' => [
                'strYoutube',
                'youtube.com?feature=shared',
                'YouTube',
                'https://youtube.com?feature=shared',
            ],
            'twitter_subdominio_com_caminho' => [
                'strTwitter',
                'mobile.twitter.com/artista',
                'X',
                'https://mobile.twitter.com/artista',
            ],
            'youtube_curto_com_caminho' => [
                'strYoutube',
                'youtu.be/abc123',
                'YouTube',
                'https://youtu.be/abc123',
            ],
            'youtube_identificador_com_arroba' => [
                'strYoutube',
                '@banda.metal',
                'YouTube',
                'https://www.youtube.com/@banda.metal',
            ],
            'instagram_identificador_com_ponto' => [
                'strInstagram',
                'banda.metal',
                'Instagram',
                'https://www.instagram.com/banda.metal',
            ],
            'youtube_rejeita_instagram' => [
                'strYoutube',
                'https://instagram.com/outra-banda',
                null,
                null,
            ],
            'youtube_rejeita_dominio_imitador' => [
                'strYoutube',
                'https://youtube.com.evil.example/@artista',
                null,
                null,
            ],
        ];
    }
}
