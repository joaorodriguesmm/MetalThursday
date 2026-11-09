<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoTheAudioDB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BiografiaGenericaTheAudioDBTest extends TestCase
{
    public static function casosDeBiografia(): array
    {
        return [
            'portugues prioritario' => [
                'Biografia portuguesa.', 'English biography.',
                'Generic biography.', 'Biografia portuguesa.', 'pt',
            ],
            'ingles antes da generica' => [
                null, 'English biography.',
                'Generic biography.', 'English biography.', 'en',
            ],
            'apenas generica' => [
                null, null,
                'Generic biography.', 'Generic biography.', null,
            ],
        ];
    }

    #[Test]
    #[DataProvider('casosDeBiografia')]
    public function respeita_prioridade_das_biografias(
        ?string $portugues,
        ?string $ingles,
        string $generica,
        string $esperada,
        ?string $idioma,
    ): void {
        config()->set('theaudiodb.base_url', 'https://www.theaudiodb.com');
        config()->set('theaudiodb.api_key', '123');
        config()->set('theaudiodb.intervalo_minimo_pedidos_ms', 0);

        $mbid = '386b19c6-a3d2-414a-bef2-c178572b1cbe';

        Http::preventStrayRequests();

        Http::fake([
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => [[
                    'strArtist' => 'Moonspell',
                    'strMusicBrainzID' => $mbid,
                    'strBiographyPT' => $portugues,
                    'strBiographyEN' => $ingles,
                    'strBiography' => $generica,
                ]],
            ]),
        ]);

        $artista = app(ServicoTheAudioDB::class)
            ->obterArtistaPorMusicBrainz($mbid);

        self::assertNotNull($artista);
        self::assertSame($esperada, $artista['biografia']);
        self::assertSame($idioma, $artista['idioma_biografia']);
    }
}
