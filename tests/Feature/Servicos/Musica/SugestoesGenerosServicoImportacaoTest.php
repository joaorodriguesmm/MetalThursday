<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoImportacaoArtista;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SugestoesGenerosServicoImportacaoTest extends TestCase
{
    public static function casosDeImportacao(): array
    {
        return [
            'ambas as fontes' => [true, 4],
            'sem dados TheAudioDB' => [false, 3],
        ];
    }

    #[Test]
    #[DataProvider('casosDeImportacao')]
    public function agrega_generos_sem_duplicados(
        bool $audioDisponivel,
        int $totalEsperado,
    ): void {
        config()->set('musicbrainz.base_url', 'https://musicbrainz.org');
        config()->set('musicbrainz.user_agent', 'MetalThursdayTest/2.0');
        config()->set('musicbrainz.intervalo_minimo_pedidos_ms', 0);
        config()->set('musicbrainz.intervalo_repeticao_ms', 0);

        config()->set('theaudiodb.base_url', 'https://www.theaudiodb.com');
        config()->set('theaudiodb.api_key', '123');
        config()->set('theaudiodb.intervalo_minimo_pedidos_ms', 0);
        config()->set('theaudiodb.intervalo_repeticao_ms', 0);

        $mbid = '386b19c6-a3d2-414a-bef2-c178572b1cbe';

        Http::preventStrayRequests();

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/*' => Http::response([
                'id' => $mbid,
                'name' => 'Moonspell',
                'relations' => [],
                'genres' => [
                    ['name' => 'black metal'],
                    ['name' => 'gothic metal'],
                    ['name' => 'metal'],
                ],
            ]),
            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response([
                'artists' => $audioDisponivel
                    ? [[
                        'strArtist' => 'Moonspell',
                        'strMusicBrainzID' => $mbid,
                        'strGenre' => 'Metal',
                        'strStyle' => 'Rock/Pop',
                    ]]
                    : [],
            ]),
        ]);

        $proposta = app(ServicoImportacaoArtista::class)
            ->obterProposta($mbid);

        $esperado = [
            [
                'nome' => 'black metal',
                'tipo' => 'genero',
                'fontes' => ['musicbrainz'],
            ],
            [
                'nome' => 'gothic metal',
                'tipo' => 'genero',
                'fontes' => ['musicbrainz'],
            ],
            [
                'nome' => 'metal',
                'tipo' => 'genero',
                'fontes' => $audioDisponivel
                    ? ['musicbrainz', 'theaudiodb']
                    : ['musicbrainz'],
            ],
        ];

        if ($audioDisponivel) {
            $esperado[] = [
                'nome' => 'Rock/Pop',
                'tipo' => 'estilo',
                'fontes' => ['theaudiodb'],
            ];
        }

        self::assertSame(
            $totalEsperado,
            count($proposta['sugestoes_generos']),
        );

        self::assertSame(
            $esperado,
            $proposta['sugestoes_generos'],
        );

        self::assertArrayNotHasKey('generos', $proposta);
    }
}
