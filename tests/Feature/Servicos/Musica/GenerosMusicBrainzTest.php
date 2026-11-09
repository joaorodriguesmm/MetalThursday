<?php

declare(strict_types=1);

namespace Tests\Feature\Servicos\Musica;

use App\Servicos\Musica\ServicoMusicBrainz;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GenerosMusicBrainzTest extends TestCase
{
    #[Test]
    public function normaliza_generos_sem_duplicados(): void
    {
        config()->set('musicbrainz.intervalo_minimo_pedidos_ms', 0);
        config()->set('musicbrainz.intervalo_repeticao_ms', 0);

        $mbid = '386b19c6-a3d2-414a-bef2-c178572b1cbe';

        Http::preventStrayRequests();

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/*' => Http::response([
                'id' => $mbid,
                'name' => 'Moonspell',
                'relations' => [],
                'genres' => [
                    ['name' => 'gothic metal', 'count' => 5],
                    ['name' => 'black metal', 'count' => 3],
                    ['name' => 'GOTHIC METAL', 'count' => 2],
                    ['name' => '', 'count' => 1],
                ],
            ]),
        ]);

        $artista = app(ServicoMusicBrainz::class)
            ->obterArtista($mbid);

        self::assertSame(
            ['gothic metal', 'black metal'],
            $artista['generos'],
        );

        Http::assertSent(
            fn ($pedido) => str_contains(
                urldecode($pedido->url()),
                'url-rels genres',
            ),
        );
    }
}
