<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Enumeracoes\PapelUtilizador;
use App\Enumeracoes\TipoLancamento;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\ReservaMetalThursday;
use App\Models\Musica\Lancamento;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Testa os endpoints utilizados para importar lançamentos do Discogs.
 *
 * @since 2.0.0
 */
final class ControladorImportacaoLancamentoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configura a integração Discogs utilizada pelos testes.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'discogs.base_url',
            'https://api.discogs.com',
        );

        config()->set(
            'discogs.user_agent',
            'MetalThursdayTest/2.0',
        );

        config()->set(
            'discogs.token',
            'token-de-teste',
        );

        config()->set(
            'discogs.intervalo_repeticao_ms',
            0,
        );

        config()->set(
            'discogs.intervalo_minimo_pedidos_ms',
            0,
        );
    }

    /**
     * Confirma a pesquisa autenticada de Releases no Discogs.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_lancamentos_para_importacao(): void
    {
        $superAdministrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create([
                    'email_verified_at' => now(),
                ]);

        Http::fake([
            'https://api.discogs.com/database/search*' => Http::response(
                [
                    'results' => [
                        [
                            'id' => 249504,
                            'type' => 'release',
                            'title' => 'Metallica - Master Of Puppets',
                            'year' => 1986,
                            'country' => 'US',

                            'format' => [
                                'Vinyl',
                                'LP',
                                'Album',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $this
            ->actingAs(
                $superAdministrador,
                'sessao',
            )
            ->getJson(
                route(
                    'lancamentos.importacao.pesquisar',
                    [
                        'pesquisa' => 'Master of Puppets',
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'resultados.0.discogs_release_id',
                249504,
            )
            ->assertJsonPath(
                'resultados.0.titulo',
                'Metallica - Master Of Puppets',
            )
            ->assertJsonPath(
                'resultados.0.ano',
                1986,
            )
            ->assertJsonPath(
                'resultados.0.pais',
                'US',
            );
    }

    /**
     * A identidade contextual do artista é opcional, mas não pode apontar
     * para um registo inexistente ou eliminado logicamente.
     */
    #[Test]
    public function rejeita_artista_de_contexto_inexistente(): void
    {
        $administrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create(['email_verified_at' => now()]);

        Http::fake();

        $this->actingAs($administrador, 'sessao')
            ->postJson(
                route('lancamentos.importacao.importar', [
                    'identificadorDiscogs' => 249504,
                ]),
                ['artista_id' => 99999999],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('artista_id');

        Http::assertNothingSent();
    }

    /**
     * Confirma que um utilizador comum com reserva pendente pode pesquisar
     * lançamentos durante a preparação da MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_com_reserva_pode_pesquisar_lancamentos(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'email_verified_at' => now(),
                ]);

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        Http::fake([
            'https://api.discogs.com/database/search*' => Http::response(
                [
                    'results' => [
                        [
                            'id' => 249504,
                            'type' => 'release',
                            'title' => 'Metallica - Master Of Puppets',
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
                    'lancamentos.importacao.pesquisar',
                    [
                        'pesquisa' => 'Master of Puppets',
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'resultados.0.discogs_release_id',
                249504,
            );
    }

    /**
     * Confirma que uma edição já completa é autorizada antes de consultar
     * o Discogs para obter ligações complementares.
     *
     * @since 2.0.0
     */
    #[Test]
    public function reutiliza_lancamento_completo_e_obtem_ligacoes_complementares(): void
    {
        $superAdministrador = Utilizador::factory()
            ->comPapel(PapelUtilizador::SuperAdministrador)
            ->create(['email_verified_at' => now()]);

        $lancamento = Lancamento::factory()->create([
            'titulo' => 'Master Of Puppets',
            'tipo' => TipoLancamento::AlbumEstudio,
            'ano_original' => 1986,
            'discogs_release_id' => 249504,
        ]);

        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response([
                'id' => 249504,
                'title' => 'Master Of Puppets',
                'artists' => [],
                'tracklist' => [],
                'videos' => [
                    ['uri' => 'https://www.youtube.com/watch?v=abcdefghijk'],
                ],
            ], 200),
        ]);

        $this->actingAs($superAdministrador, 'sessao')
            ->postJson(route('lancamentos.importacao.importar', [
                'identificadorDiscogs' => 249504,
            ]))
            ->assertOk()
            ->assertJsonPath('lancamento.id', $lancamento->getKey())
            ->assertJsonPath(
                'sugestoes.ligacoes.1.url',
                'https://www.youtube.com/watch?v=abcdefghijk',
            );

        self::assertSame(1, Lancamento::withTrashed()
            ->where('discogs_release_id', 249504)
            ->count());

        Http::assertSentCount(1);
    }

    /**
     * Confirma a importação da Release Discogs selecionada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function importa_lancamento_selecionado(): void
    {
        $superAdministrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create([
                    'email_verified_at' => now(),
                ]);

        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'year' => 1991,
                    'master_id' => 12345,
                    'notes' => 'Notas editoriais da edição.',
                    'videos' => [
                        ['uri' => 'https://www.youtube.com/watch?v=abcdefghijk'],
                    ],

                    'formats' => [
                        [
                            'name' => 'CD',

                            'descriptions' => [
                                'Album',
                                'Reissue',
                            ],
                        ],
                    ],

                    'artists' => [
                        [
                            'id' => 18839,
                            'name' => 'Metallica',
                        ],
                    ],

                    'tracklist' => [
                        [
                            'position' => 'A1',
                            'type_' => 'track',
                            'title' => 'Battery',
                        ],
                    ],
                ],
                200,
            ),

            'https://api.discogs.com/masters/12345' => Http::response(
                [
                    'id' => 12345,
                    'year' => 1986,
                ],
                200,
            ),
        ]);

        $resposta =
            $this
                ->actingAs(
                    $superAdministrador,
                    'sessao',
                )
                ->postJson(
                    route(
                        'lancamentos.importacao.importar',
                        [
                            'identificadorDiscogs' => 249504,
                        ],
                    ),
                );

        $resposta
            ->assertOk()
            ->assertJsonPath(
                'sugestoes.notas_edicao',
                'Notas editoriais da edição.',
            )
            ->assertJsonPath(
                'sugestoes.ligacoes.0.url',
                'https://www.discogs.com/release/249504',
            )
            ->assertJsonPath(
                'sugestoes.ligacoes.1.url',
                'https://www.youtube.com/watch?v=abcdefghijk',
            )
            ->assertJsonPath(
                'lancamento.discogs_release_id',
                249504,
            )
            ->assertJsonPath(
                'lancamento.titulo',
                'Master Of Puppets',
            )
            ->assertJsonPath(
                'lancamento.tipo',
                TipoLancamento::AlbumEstudio->value,
            )
            ->assertJsonPath(
                'lancamento.ano_original',
                1986,
            )
            ->assertJsonPath(
                'lancamento.faixas.0.titulo',
                'Battery',
            )
            ->assertJsonPath(
                'lancamento.faixas.0.posicao',
                'A1',
            )
            ->assertJsonPath(
                'lancamento.faixas.0.ordem',
                1,
            );

        $this->assertDatabaseHas(
            'lancamentos',
            [
                'discogs_release_id' => 249504,
                'titulo' => 'Master Of Puppets',
            ],
        );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'discogs_id' => 18839,
            ],
        );

        $this->assertDatabaseHas(
            'musicas',
            [
                'titulo' => 'Battery',
            ],
        );
    }

    /**
     * Confirma que um utilizador comum com reserva pendente pode importar
     * a Release selecionada durante a preparação da MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_comum_com_reserva_pode_importar_lancamento(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'email_verified_at' => now(),
                ]);

        ReservaMetalThursday::factory()
            ->comData(
                CarbonImmutable::parse(
                    '2026-01-15',
                ),
            )
            ->comResponsavel(
                $utilizador,
            )
            ->create();

        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'artists' => [],
                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'lancamentos.importacao.importar',
                    [
                        'identificadorDiscogs' => 249504,
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'lancamento.discogs_release_id',
                249504,
            );

        $this->assertDatabaseHas(
            'lancamentos',
            [
                'discogs_release_id' => 249504,
                'titulo' => 'Master Of Puppets',
            ],
        );
    }

    /**
     * Confirma que um identificador Discogs não positivo não corresponde à rota
     * de importação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function rejeita_identificador_discogs_nao_positivo(): void
    {
        $administrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::Administrador,
                )
                ->create([
                    'email_verified_at' => now(),
                ]);

        $this
            ->actingAs(
                $administrador,
                'sessao',
            )
            ->postJson(
                '/lancamentos/importacao/0',
            )
            ->assertNotFound();

        Http::assertNothingSent();
    }

    /**
     * Confirma que o autor pode pesquisar lançamentos ao editar a sua
     * MetalThursday, mesmo sem possuir uma reserva pendente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_sem_reserva_pode_pesquisar_lancamentos_durante_edicao(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'email_verified_at' => now(),
                ]);

        $metalThursday =
            MetalThursday::factory()
                ->create([
                    'autor_id' => $utilizador->getKey(),
                    'criado_por_id' => $utilizador->getKey(),
                ]);

        Http::fake([
            'https://api.discogs.com/database/search*' => Http::response(
                [
                    'results' => [
                        [
                            'id' => 249504,
                            'type' => 'release',
                            'title' => 'Metallica - Master Of Puppets',
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
                    'lancamentos.importacao.pesquisar',
                    [
                        'pesquisa' => 'Master of Puppets',
                        'metal_thursday_id' => $metalThursday->getKey(),
                    ],
                ),
            )
            ->assertOk();
    }

    /**
     * Confirma que o autor pode importar um lançamento ao editar a sua
     * MetalThursday, mesmo sem possuir uma reserva pendente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_sem_reserva_pode_importar_lancamento_durante_edicao(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create([
                    'email_verified_at' => now(),
                ]);

        $metalThursday =
            MetalThursday::factory()
                ->create([
                    'autor_id' => $utilizador->getKey(),
                    'criado_por_id' => $utilizador->getKey(),
                ]);

        Http::fake([
            'https://api.discogs.com/releases/249504' => Http::response(
                [
                    'id' => 249504,
                    'title' => 'Master Of Puppets',
                    'artists' => [],
                    'tracklist' => [],
                ],
                200,
            ),
        ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'lancamentos.importacao.importar',
                    [
                        'identificadorDiscogs' => 249504,
                        'metal_thursday_id' => $metalThursday->getKey(),
                    ],
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'lancamento.discogs_release_id',
                249504,
            );

        $this->assertDatabaseHas(
            'lancamentos',
            [
                'discogs_release_id' => 249504,
                'titulo' => 'Master Of Puppets',
            ],
        );
    }

    /**
     * Confirma que uma falha inesperada do Discogs não
     * expõe mensagens sensíveis na resposta da pesquisa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function pesquisa_nao_expoe_mensagem_sensivel_de_falha_inesperada_discogs(): void
    {
        $segredoFicticio =
            'AUDITORIA_SEGREDO_FICTICIO_B19_21_PESQUISA_LANCAMENTOS';

        $superAdministrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create([
                    'email_verified_at' => now(),
                ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://api.discogs.com/database/search*' => static function () use ($segredoFicticio): never {
                throw new RuntimeException(
                    'Falha inesperada no Discogs: '.$segredoFicticio,
                );
            },
        ]);

        $resposta =
            $this
                ->actingAs(
                    $superAdministrador,
                    'sessao',
                )
                ->getJson(
                    route(
                        'lancamentos.importacao.pesquisar',
                        [
                            'pesquisa' => 'Master of Puppets',
                        ],
                    ),
                );

        $resposta->assertStatus(503);

        self::assertIsString(
            $resposta->json('mensagem'),
        );

        self::assertStringNotContainsString(
            $segredoFicticio,
            $resposta->getContent(),
        );
    }

    /**
     * Confirma que uma falha inesperada do Discogs não
     * expõe mensagens sensíveis na resposta da importação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function importar_nao_expoe_mensagem_sensivel_de_falha_inesperada_discogs(): void
    {
        $segredoFicticio =
            'AUDITORIA_SEGREDO_FICTICIO_B19_21_IMPORTAR_LANCAMENTO';

        $superAdministrador =
            Utilizador::factory()
                ->comPapel(
                    PapelUtilizador::SuperAdministrador,
                )
                ->create([
                    'email_verified_at' => now(),
                ]);

        Http::preventStrayRequests();

        Http::fake([
            'https://api.discogs.com/releases/249504' => static function () use ($segredoFicticio): never {
                throw new RuntimeException(
                    'Falha inesperada na importação Discogs: '.$segredoFicticio,
                );
            },
        ]);

        $resposta =
            $this
                ->actingAs(
                    $superAdministrador,
                    'sessao',
                )
                ->postJson(
                    route(
                        'lancamentos.importacao.importar',
                        [
                            'identificadorDiscogs' => 249504,
                        ],
                    ),
                );

        $resposta->assertStatus(503);

        self::assertIsString(
            $resposta->json('mensagem'),
        );

        self::assertStringNotContainsString(
            $segredoFicticio,
            $resposta->getContent(),
        );

        $this->assertDatabaseMissing(
            'lancamentos',
            [
                'discogs_release_id' => 249504,
            ],
        );
    }
}
