<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SugestoesGenerosImportacaoArtistaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function apresenta_correspondencias_e_pais_sem_selecionar_generos(): void
    {
        config()->set('musicbrainz.base_url', 'https://musicbrainz.org');
        config()->set('musicbrainz.user_agent', 'MetalThursdayTest/2.0');
        config()->set('musicbrainz.intervalo_minimo_pedidos_ms', 0);
        config()->set('musicbrainz.intervalo_repeticao_ms', 0);
        config()->set('theaudiodb.base_url', 'https://www.theaudiodb.com');
        config()->set('theaudiodb.api_key', '123');
        config()->set('theaudiodb.intervalo_minimo_pedidos_ms', 0);
        config()->set('theaudiodb.intervalo_repeticao_ms', 0);

        $metal = Genero::factory()->create(['nome' => 'Metal']);
        $rock = Genero::factory()->create(['nome' => 'Rock']);
        $paiEliminado = Genero::factory()->create(['nome' => 'Outro Pai']);
        $black = Genero::factory()->create(['nome' => 'Black Metal']);
        $gothic = Genero::factory()->create(['nome' => 'Gothic Metal']);
        $estiloEliminado = Genero::factory()->create(['nome' => 'Rock/Pop']);

        $black->generosPais()->attach($metal->getKey());

        $gothic->generosPais()->attach([
            $metal->getKey(),
            $rock->getKey(),
            $paiEliminado->getKey(),
        ]);

        $paiEliminado->delete();
        $estiloEliminado->delete();

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
                'artists' => [[
                    'strArtist' => 'Moonspell',
                    'strMusicBrainzID' => $mbid,
                    'strGenre' => 'Metal',
                    'strStyle' => 'Rock/Pop',
                ]],
            ]),
        ]);

        $utilizador = Utilizador::factory()->create([
            'email_verified_at' => now(),
        ]);

        $resposta = $this
            ->actingAs($utilizador, 'sessao')
            ->getJson(route('artistas.importacao.obter', [
                'mbid' => $mbid,
            ]));

        $resposta
            ->assertOk()
            ->assertJsonCount(4, 'artista.sugestoes_generos')
            ->assertJsonPath('artista.sugestoes_generos.0.genero_local.id', $black->getKey())
            ->assertJsonPath('artista.sugestoes_generos.1.genero_local.id', $gothic->getKey())
            ->assertJsonPath('artista.sugestoes_generos.2.genero_local.id', $metal->getKey())
            ->assertJsonPath('artista.sugestoes_generos.2.fontes', [
                'musicbrainz',
                'theaudiodb',
            ])
            ->assertJsonPath('artista.sugestoes_generos.3.tipo', 'estilo')
            ->assertJsonPath('artista.sugestoes_generos.3.disponivel', false)
            ->assertJsonPath('artista.sugestoes_generos.3.genero_local', null)
            ->assertJsonPath('artista.sugestoes_generos.3.generos_pais', []);

        $pais = $resposta->json(
            'artista.sugestoes_generos.1.generos_pais',
        );

        self::assertSame(
            ['Metal', 'Rock'],
            array_column($pais, 'nome'),
        );

        self::assertSame(
            [$metal->getKey(), $rock->getKey()],
            array_column($pais, 'id'),
        );

        self::assertArrayNotHasKey(
            'generos',
            $resposta->json('artista'),
        );

        $this->assertDatabaseCount('artista_genero', 0);
    }
}
