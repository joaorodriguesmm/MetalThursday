<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os endpoints utilizados pelo formulário para importar artistas.
 *
 * @since 2.0.0
 */
final class ControladorImportacaoArtistaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura as integrações utilizadas pelos endpoints.
     *
     * Os intervalos temporais são desativados porque os pedidos HTTP destes
     * testes são simulados e não atingem os serviços externos.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'musicbrainz.base_url',
            'https://musicbrainz.org',
        );

        config()->set(
            'musicbrainz.user_agent',
            'MetalThursdayTest/2.0',
        );

        config()->set(
            'musicbrainz.intervalo_repeticao_ms',
            0,
        );

        config()->set(
            'musicbrainz.intervalo_minimo_pedidos_ms',
            0,
        );

        config()->set(
            'theaudiodb.base_url',
            'https://www.theaudiodb.com',
        );

        config()->set(
            'theaudiodb.api_key',
            '123',
        );

        config()->set(
            'theaudiodb.intervalo_repeticao_ms',
            0,
        );

        config()->set(
            'theaudiodb.intervalo_minimo_pedidos_ms',
            0,
        );
    }

    /**
     * Confirma a pesquisa autenticada no MusicBrainz.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_artistas_para_importacao(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'email_verified_at' => now(),
                ]);

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/*' => Http::response(
                [
                    'artists' => [
                        [
                            'id' => '60235eed-ed13-405c-ae04-722f4386d174',

                            'name' => 'Process of Guilt',

                            'type' => 'Group',

                            'score' => 100,

                            'area' => [
                                'name' => 'Évora',
                            ],

                            'begin-area' => [
                                'name' => 'Évora',
                            ],

                            'life-span' => [
                                'begin' => '2002',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->getJson(
                route(
                    'artistas.importacao.pesquisar',
                    [
                        'pesquisa' => 'Process of Guilt',
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'resultados.0.mbid',
                '60235eed-ed13-405c-ae04-722f4386d174',
            )
            ->assertJsonPath(
                'resultados.0.nome',
                'Process of Guilt',
            )
            ->assertJsonPath(
                'resultados.0.area_inicio',
                'Évora',
            )
            ->assertJsonPath(
                'resultados.0.ano_inicio',
                2002,
            );
    }

    /**
     * Confirma a obtenção da proposta agregada e a correspondência da origem
     * geográfica externa com o registo local.
     *
     * @since 2.0.0
     */
    #[Test]
    public function obtem_proposta_de_preenchimento(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'email_verified_at' => now(),
                ]);

        $origem =
            OrigemGeografica::factory()
                ->create([
                    'nome' => 'Portugal',

                    'codigo' => 'PT',
                ]);

        Http::fake([
            'https://musicbrainz.org/ws/2/artist/c147ccdc-cf18-49e4-8276-aee8a2170dac*' => Http::response(
                [
                    'id' => 'c147ccdc-cf18-49e4-8276-aee8a2170dac',

                    'name' => 'Corpus Christii',

                    'type' => 'Group',

                    'country' => 'PT',

                    'area' => [
                        'name' => 'Portugal',
                    ],

                    'begin-area' => [
                        'name' => 'Lisboa',
                    ],

                    'life-span' => [
                        'begin' => '1998',
                    ],

                    'relations' => [],
                ],
                200,
            ),

            'https://www.theaudiodb.com/api/v1/json/123/artist-mb.php*' => Http::response(
                [
                    'artists' => null,
                ],
                200,
            ),
        ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->getJson(
                route(
                    'artistas.importacao.obter',
                    [
                        'mbid' => 'c147ccdc-cf18-49e4-8276-aee8a2170dac',
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'artista.musicbrainz_id',
                'c147ccdc-cf18-49e4-8276-aee8a2170dac',
            )
            ->assertJsonPath(
                'artista.nome',
                'Corpus Christii',
            )
            ->assertJsonPath(
                'artista.origem.codigo_pais',
                'PT',
            )
            ->assertJsonPath(
                'artista.origem.area_inicio',
                'Lisboa',
            )
            ->assertJsonPath(
                'artista.ano_inicio_atividade',
                1998,
            )
            ->assertJsonPath(
                'artista.fontes.musicbrainz',
                true,
            )
            ->assertJsonPath(
                'artista.fontes.theaudiodb',
                false,
            )
            ->assertJsonPath(
                'artista.origem_geografica_id',
                (int) $origem->getKey(),
            )
            ->assertJsonPath(
                'artista.origem_geografica.nome',
                'Portugal',
            )
            ->assertJsonPath(
                'artista.origem_geografica.codigo',
                'PT',
            );
    }
}
