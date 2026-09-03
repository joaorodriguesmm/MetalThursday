<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa o perfil enriquecido dos artistas através dos fluxos HTTP reais.
 *
 * @since 2.0.0
 */
final class PerfilArtistaControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function cria_perfil_completo_do_artista(): void
    {
        $utilizador = $this->criarUtilizador();

        $origem = OrigemGeografica::factory()->create([
            'nome' => 'Portugal',
            'codigo' => 'PT',
        ]);

        $genero = Genero::factory()->create([
            'nome' => 'Gothic Metal',
        ]);

        $resposta = $this
            ->actingAs($utilizador, 'sessao')
            ->postJson(
                route('artistas.guardar'),
                [
                    'nome' => 'Moonspell',
                    'origem_geografica_id' => (int) $origem->getKey(),
                    'ano_inicio_atividade' => 1992,
                    'ano_fim_atividade' => null,
                    'estado_atividade' => 'ativo',
                    'biografia' => 'Banda portuguesa de metal.',
                    'imagem' => 'https://static.example.com/moonspell.jpg',
                    'discogs_id' => 12345,
                    'ligacoes' => [
                        [
                            'titulo' => 'Site oficial',
                            'url' => 'https://www.moonspell.com',
                        ],
                        [
                            'titulo' => 'Bandcamp',
                            'url' => 'https://moonspell.bandcamp.com',
                        ],
                    ],
                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            );

        $resposta
            ->assertCreated()
            ->assertJsonPath('artista.nome', 'Moonspell')
            ->assertJsonPath('artista.ano_inicio_atividade', 1992)
            ->assertJsonPath('artista.ano_fim_atividade', null)
            ->assertJsonPath('artista.estado_atividade', 'ativo')
            ->assertJsonPath('artista.biografia', 'Banda portuguesa de metal.')
            ->assertJsonPath('artista.imagem', 'https://static.example.com/moonspell.jpg')
            ->assertJsonPath('artista.url_imagem', 'https://static.example.com/moonspell.jpg')
            ->assertJsonPath('artista.discogs_id', 12345)
            ->assertJsonPath('artista.url_discogs', 'https://www.discogs.com/artist/12345')
            ->assertJsonPath('artista.ligacoes.0.titulo', 'Site oficial')
            ->assertJsonPath('artista.ligacoes.0.url', 'https://www.moonspell.com')
            ->assertJsonPath('artista.ligacoes.1.titulo', 'Bandcamp');

        $this->assertDatabaseHas(
            'artistas',
            [
                'nome' => 'Moonspell',
                'ano_inicio_atividade' => 1992,
                'ano_fim_atividade' => null,
                'estado_atividade' => 'ativo',
                'imagem' => 'https://static.example.com/moonspell.jpg',
                'discogs_id' => 12345,
            ],
        );

        $identificadorArtista = $resposta->json('artista.id');

        $this->assertDatabaseHas(
            'ligacoes',
            [
                'tipo_ligavel' => 'artista',
                'ligavel_id' => $identificadorArtista,
                'titulo' => 'Site oficial',
                'url' => 'https://www.moonspell.com',
                'ordem' => 1,
            ],
        );

        $this->assertDatabaseHas(
            'ligacoes',
            [
                'tipo_ligavel' => 'artista',
                'ligavel_id' => $identificadorArtista,
                'titulo' => 'Bandcamp',
                'url' => 'https://moonspell.bandcamp.com',
                'ordem' => 2,
            ],
        );
    }

    #[Test]
    public function atualiza_e_remove_metadados_do_perfil(): void
    {
        $utilizador = $this->criarUtilizador();
        $genero = Genero::factory()->create();

        $this->actingAs($utilizador, 'sessao');

        $artista = Artista::factory()->create([
            'ano_inicio_atividade' => 1990,
            'ano_fim_atividade' => 2000,
            'estado_atividade' => 'terminado',
            'biografia' => 'Biografia antiga.',
            'imagem' => 'https://static.example.com/antiga.jpg',
            'discogs_id' => 12345,
        ]);

        $artista->generos()->attach($genero->getKey());

        $artista->ligacoes()->create([
            'titulo' => 'Site antigo',
            'url' => 'https://example.com',
            'ordem' => 1,
        ]);

        $this
            ->patchJson(
                route('artistas.atualizar', $artista),
                [
                    'nome' => $artista->nome,
                    'origem_geografica_id' => null,
                    'ano_inicio_atividade' => null,
                    'ano_fim_atividade' => null,
                    'estado_atividade' => null,
                    'biografia' => null,
                    'imagem' => null,
                    'discogs_id' => null,
                    'ligacoes' => [],
                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            )
            ->assertOk()
            ->assertJsonPath('artista.ano_inicio_atividade', null)
            ->assertJsonPath('artista.ano_fim_atividade', null)
            ->assertJsonPath('artista.estado_atividade', null)
            ->assertJsonPath('artista.biografia', null)
            ->assertJsonPath('artista.imagem', null)
            ->assertJsonPath('artista.url_imagem', null)
            ->assertJsonPath('artista.discogs_id', null)
            ->assertJsonCount(0, 'artista.ligacoes');

        $this->assertDatabaseMissing(
            'ligacoes',
            [
                'tipo_ligavel' => 'artista',
                'ligavel_id' => $artista->getKey(),
            ],
        );
    }

    #[Test]
    public function guarda_url_externa_da_imagem_sem_ficheiro_local(): void
    {
        $utilizador = $this->criarUtilizador();
        $genero = Genero::factory()->create();

        $this
            ->actingAs($utilizador, 'sessao')
            ->postJson(
                route('artistas.guardar'),
                [
                    'nome' => 'Artista com imagem externa',
                    'imagem' => 'https://cdn.example.com/artista.jpg',
                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            )
            ->assertCreated()
            ->assertJsonPath(
                'artista.imagem',
                'https://cdn.example.com/artista.jpg',
            )
            ->assertJsonPath(
                'artista.url_imagem',
                'https://cdn.example.com/artista.jpg',
            );

        $this->assertDatabaseHas(
            'artistas',
            [
                'nome' => 'Artista com imagem externa',
                'imagem' => 'https://cdn.example.com/artista.jpg',
            ],
        );
    }

    #[Test]
    public function detalhes_apresentam_perfil_enriquecido(): void
    {
        $utilizador = $this->criarUtilizador();
        $this->actingAs($utilizador, 'sessao');

        $artista = Artista::factory()->create([
            'ano_inicio_atividade' => 1989,
            'ano_fim_atividade' => 2017,
            'estado_atividade' => 'terminado',
            'biografia' => 'Biografia local do artista.',
            'imagem' => 'https://cdn.example.com/artista.jpg',
            'discogs_id' => 12345,
            'musicbrainz_id' => '65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
        ]);

        $artista->ligacoes()->create([
            'titulo' => 'Site oficial',
            'url' => 'https://example.com',
            'ordem' => 1,
        ]);

        $this
            ->get(route('artistas.detalhes', $artista))
            ->assertOk()
            ->assertSee('1989')
            ->assertSee('2017')
            ->assertSee('Atividade terminada')
            ->assertSee('Biografia local do artista.')
            ->assertSee('Site oficial')
            ->assertSee('https://example.com', false)
            ->assertSee('https://cdn.example.com/artista.jpg', false)
            ->assertSee(
                'MusicBrainz',
            )
            ->assertSee(
                'Abrir perfil MusicBrainz',
            )
            ->assertSee(
                'https://musicbrainz.org/artist/65f4f0c5-ef9e-490c-aee3-909e7ae6b2ab',
                false,
            )
            ->assertSee('Discogs');
    }

    #[Test]
    public function formularios_escondem_metadados_atras_de_toggle(): void
    {
        $utilizador = $this->criarUtilizador();
        $this->actingAs($utilizador, 'sessao');

        $artista = Artista::factory()->create();

        foreach (
            [
                route('artistas.criar'),
                route('artistas.editar', $artista),
            ] as $endereco
        ) {
            $conteudo = $this->get($endereco)->assertOk()->getContent();

            foreach (
                [
                    'name="nome"',
                    'name="origem_geografica_id"',
                    'name="generos[]"',
                    'data-alternar-campos-adicionais',
                    'Apresentar campos adicionais',
                    'data-campos-adicionais-artista',
                    'name="ano_inicio_atividade"',
                    'name="ano_fim_atividade"',
                    'name="estado_atividade"',
                    'name="biografia"',
                    'type="url"',
                    'name="imagem"',
                    'URL externa, opcional',
                    'name="discogs_id"',
                    'name="ligacoes[0][titulo]"',
                    'name="ligacoes[0][url]"',
                    'data-importacao-artista',
                ] as $fragmento
            ) {
                self::assertStringContainsString($fragmento, $conteudo);
            }

            self::assertStringNotContainsString(
                'type="file"',
                $conteudo,
            );
        }
    }

    #[Test]
    public function modal_metal_thursday_esconde_metadados_atras_de_toggle(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::Administrador)
            ->create([
                'email_verified_at' => now(),
            ]);

        $conteudo = $this
            ->actingAs($administrador, 'sessao')
            ->get(route('metal-thursday.criar'))
            ->assertOk()
            ->getContent();

        foreach (
            [
                'name="nome"',
                'name="origem_geografica_id"',
                'name="generos[]"',
                'data-alternar-campos-adicionais',
                'Apresentar campos adicionais',
                'data-campos-adicionais-artista',
                'name="ano_inicio_atividade"',
                'name="ano_fim_atividade"',
                'name="estado_atividade"',
                'name="biografia"',
                'name="imagem"',
                'URL externa, opcional',
                'name="discogs_id"',
                'data-importacao-artista',
            ] as $fragmento
        ) {
            self::assertStringContainsString($fragmento, $conteudo);
        }

        self::assertStringNotContainsString(
            'type="file"',
            $conteudo,
        );
    }

    /**
     * Confirma que criação e edição apresentam o perfil completo e a importação
     * externa antes dos restantes campos opcionais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formularios_apresentam_perfil_completo_e_importacao(): void
    {
        $utilizador =
            $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista =
            Artista::factory()
                ->create();

        foreach (
            [
                route(
                    'artistas.criar',
                ),

                route(
                    'artistas.editar',
                    $artista,
                ),
            ] as $endereco
        ) {
            $conteudo =
                $this
                    ->get(
                        $endereco,
                    )
                    ->assertOk()
                    ->getContent();

            foreach (
                [
                    'name="ano_inicio_atividade"',
                    'name="ano_fim_atividade"',
                    'name="estado_atividade"',
                    'name="biografia"',
                    'name="imagem"',
                    'name="musicbrainz_id"',
                    'name="discogs_id"',
                    'name="ligacoes[0][titulo]"',
                    'name="ligacoes[0][url]"',
                    'data-importacao-artista',
                    'data-acao-pesquisar-importacao',
                ] as $fragmento
            ) {
                self::assertStringContainsString(
                    $fragmento,
                    $conteudo,
                );
            }

            self::assertStringNotContainsString(
                'data-pesquisa-discogs-artista',
                $conteudo,
            );

            $posicaoImportacao =
                strpos(
                    $conteudo,
                    'data-importacao-artista',
                );

            $posicaoAno =
                strpos(
                    $conteudo,
                    'name="ano_inicio_atividade"',
                );

            self::assertIsInt(
                $posicaoImportacao,
            );

            self::assertIsInt(
                $posicaoAno,
            );

            self::assertLessThan(
                $posicaoAno,
                $posicaoImportacao,
            );
        }
    }

    /**
     * Confirma que a criação rápida oferece o mesmo perfil enriquecido e a
     * importação externa antes dos restantes campos opcionais.
     *
     * @since 2.0.0
     */
    #[Test]
    public function modal_metal_thursday_apresenta_perfil_completo_do_artista(): void
    {
        $administrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create([
                    'email_verified_at' => now(),
                ]);

        $conteudo =
            $this
                ->actingAs(
                    $administrador,
                    'sessao',
                )
                ->get(
                    route(
                        'metal-thursday.criar',
                    ),
                )
                ->assertOk()
                ->getContent();

        foreach (
            [
                'name="ano_inicio_atividade"',
                'name="ano_fim_atividade"',
                'name="estado_atividade"',
                'name="biografia"',
                'name="imagem"',
                'name="musicbrainz_id"',
                'name="discogs_id"',
                'data-importacao-artista',
                'data-acao-pesquisar-importacao',
            ] as $fragmento
        ) {
            self::assertStringContainsString(
                $fragmento,
                $conteudo,
            );
        }

        self::assertStringNotContainsString(
            'data-pesquisa-discogs-artista',
            $conteudo,
        );

        $posicaoImportacao =
            strpos(
                $conteudo,
                'data-importacao-artista',
            );

        $posicaoAno =
            strpos(
                $conteudo,
                'name="ano_inicio_atividade"',
            );

        self::assertIsInt(
            $posicaoImportacao,
        );

        self::assertIsInt(
            $posicaoAno,
        );

        self::assertLessThan(
            $posicaoAno,
            $posicaoImportacao,
        );
    }

    /**
     * Confirma que o modal de criação rápida utiliza a estrutura rolável esperada
     * pelo Bootstrap.
     *
     * O formulário constitui diretamente o conteúdo do modal para que a zona
     * central possa limitar a altura e apresentar deslocamento vertical quando os
     * campos adicionais tornam o formulário superior à altura disponível.
     *
     * @since 2.0.0
     */
    #[Test]
    public function modal_metal_thursday_permite_deslocamento_vertical(): void
    {
        $administrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create([
                    'email_verified_at' => now(),
                ]);

        $conteudo =
            $this
                ->actingAs(
                    $administrador,
                    'sessao',
                )
                ->get(
                    route(
                        'metal-thursday.criar',
                    ),
                )
                ->assertOk()
                ->getContent();

        self::assertStringContainsString(
            'modal-dialog-scrollable',
            $conteudo,
        );

        self::assertMatchesRegularExpression(
            '/<form\b(?=[^>]*id="formulario-criar-artista")(?=[^>]*class="[^"]*modal-content[^"]*")[^>]*>/',
            $conteudo,
        );

        self::assertMatchesRegularExpression(
            '/<form\b(?=[^>]*id="formulario-criar-artista")(?=[^>]*autocomplete="off")[^>]*>/',
            $conteudo,
        );

        self::assertStringContainsString(
            'class="modal-body"',
            $conteudo,
        );

        self::assertStringContainsString(
            'class="modal-footer border-secondary"',
            $conteudo,
        );
    }

    private function criarUtilizador(): Utilizador
    {
        return Utilizador::factory()->create([
            'email_verified_at' => now(),
        ]);
    }
}
