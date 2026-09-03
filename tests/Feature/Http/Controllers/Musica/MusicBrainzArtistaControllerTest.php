<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a persistência da associação MusicBrainz dos artistas.
 *
 * @since 2.0.0
 */
final class MusicBrainzArtistaControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que o MBID selecionado é persistido e serializado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function guarda_identificador_musicbrainz(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'email_verified_at' => now(),
                ]);

        $genero =
            Genero::factory()
                ->create([
                    'nome' => 'Heavy Metal',
                ]);

        $mbid =
            '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        $resposta =
            $this
                ->actingAs(
                    $utilizador,
                    'sessao',
                )
                ->postJson(
                    route(
                        'artistas.guardar',
                    ),
                    [
                        'nome' => 'Metallica',

                        'musicbrainz_id' => $mbid,

                        'discogs_id' => 18839,

                        'generos' => [
                            (int) $genero->getKey(),
                        ],
                    ],
                );

        $resposta
            ->assertCreated()
            ->assertJsonPath(
                'artista.musicbrainz_id',
                $mbid,
            )
            ->assertJsonPath(
                'artista.url_musicbrainz',
                'https://musicbrainz.org/artist/'.$mbid,
            )
            ->assertJsonPath(
                'artista.discogs_id',
                18839,
            );

        $this->assertDatabaseHas(
            'artistas',
            [
                'nome' => 'Metallica',

                'musicbrainz_id' => $mbid,

                'discogs_id' => 18839,
            ],
        );
    }

    /**
     * Confirma que o mesmo perfil MusicBrainz não pode identificar dois
     * artistas locais diferentes.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_musicbrainz_ja_associado(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'email_verified_at' => now(),
                ]);

        $genero =
            Genero::factory()
                ->create();

        $mbid =
            '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab';

        Artista::factory()
            ->create([
                'musicbrainz_id' => $mbid,
            ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'artistas.guardar',
                ),
                [
                    'nome' => 'Outro artista',

                    'musicbrainz_id' => $mbid,

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'musicbrainz_id',
            ]);
    }
}
