<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados aos artistas.
 *
 * @since 2.0.0
 */
final class ControladorArtistaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste sem depender dos ficheiros produzidos pelo Vite.
     *
     * @since 2.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Confirma que a listagem possui os dados necessários para avaliar as
     * permissões dependentes do criador.
     *
     * O criador deve visualizar as ações de edição e eliminação. Outro
     * utilizador deve conseguir consultar a mesma listagem sem essas ações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function listagem_respeita_as_permissoes_dependentes_do_criador(): void
    {
        $criador = $this->criarUtilizador();

        $outroUtilizador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Portugal',
            'PT',
        );

        $artista = $this->criarArtista(
            $criador,
            $origemGeografica,
            'Moonspell',
        );

        $enderecoEdicao = route(
            'artistas.editar',
            $artista,
        );

        $enderecoEliminacao = route(
            'artistas.eliminar',
            $artista,
        );

        $atributoEnderecoEliminacao = sprintf(
            'data-endereco="%s"',
            $enderecoEliminacao,
        );

        $this
            ->actingAs(
                $criador,
                'sessao',
            )
            ->get(
                route(
                    'artistas.indice',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Moonspell',
            )
            ->assertSee(
                $enderecoEdicao,
                false,
            )
            ->assertSee(
                $atributoEnderecoEliminacao,
                false,
            );

        $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->get(
                route(
                    'artistas.indice',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Moonspell',
            )
            ->assertDontSee(
                $enderecoEdicao,
                false,
            )
            ->assertDontSee(
                $atributoEnderecoEliminacao,
                false,
            );
    }

    /**
     * Confirma que a criação JSON associa explicitamente a origem e os
     * géneros, respeitando o contrato de atribuição em massa do modelo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_artista_em_json_com_as_relacoes_esperadas(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Portugal',
            'PT',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'artistas.guardar',
                ),
                [
                    'nome' => 'Moonspell',

                    'origem_geografica_id' => (int) $origemGeografica->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            );

        $resposta
            ->assertCreated()
            ->assertJsonPath(
                'mensagem',
                'Artista criado com sucesso.',
            )
            ->assertJsonPath(
                'artista.nome',
                'Moonspell',
            )
            ->assertJsonPath(
                'artista.origem_geografica.id',
                (int) $origemGeografica->getKey(),
            )
            ->assertJsonPath(
                'artista.origem_geografica.nome',
                'Portugal',
            )
            ->assertJsonPath(
                'artista.generos.0.id',
                (int) $genero->getKey(),
            )
            ->assertJsonPath(
                'artista.generos.0.nome',
                'Heavy Metal',
            );

        $identificadorArtista = $resposta->json(
            'artista.id',
        );

        self::assertIsInt(
            $identificadorArtista,
        );

        $this->assertDatabaseHas(
            'artistas',
            [
                'id' => $identificadorArtista,

                'nome' => 'Moonspell',

                'origem_geografica_id' => $origemGeografica->getKey(),

                'criado_por_id' => $utilizador->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'artista_genero',
            [
                'artista_id' => $identificadorArtista,

                'genero_id' => $genero->getKey(),
            ],
        );
    }

    /**
     * Confirma que o criador pode atualizar os dados e as relações do artista.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criador_atualiza_artista(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemInicial = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemNova = $this->criarOrigemGeografica(
            'Finlândia',
            'FI',
        );

        $generoInicial = $this->criarGenero(
            'Death Metal',
        );

        $generoNovo = $this->criarGenero(
            'Melodic Death Metal',
        );

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista = Artista::factory()
            ->comNome(
                'Insomnium',
            )
            ->deOrigemGeografica(
                $origemInicial,
            )
            ->create();

        $artista
            ->generos()
            ->attach(
                $generoInicial->getKey(),
            );

        $resposta = $this->patch(
            route(
                'artistas.atualizar',
                $artista,
            ),
            [
                'nome' => 'Insomnium Atualizada',

                'origem_geografica_id' => (int) $origemNova->getKey(),

                'generos' => [
                    (int) $generoNovo->getKey(),
                ],
            ],
        );

        $resposta
            ->assertRedirect(
                route(
                    'artistas.indice',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                'Artista atualizado com sucesso.',
            );

        $this->assertDatabaseHas(
            'artistas',
            [
                'id' => $artista->getKey(),

                'nome' => 'Insomnium Atualizada',

                'origem_geografica_id' => $origemNova->getKey(),

                'atualizado_por_id' => $utilizador->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'artista_genero',
            [
                'artista_id' => $artista->getKey(),

                'genero_id' => $generoInicial->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'artista_genero',
            [
                'artista_id' => $artista->getKey(),

                'genero_id' => $generoNovo->getKey(),
            ],
        );
    }

    /**
     * Confirma que a autorização antecede as consultas de validação.
     *
     * Mesmo com dados inválidos, um utilizador que não criou o artista deve
     * receber uma resposta de proibição, e não uma resposta de validação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_sem_autorizacao_e_rejeitado_antes_da_validacao(): void
    {
        $criador = $this->criarUtilizador();

        $outroUtilizador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Noruega',
            'NO',
        );

        $artista = $this->criarArtista(
            $criador,
            $origemGeografica,
            'Emperor',
        );

        $resposta = $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->patchJson(
                route(
                    'artistas.atualizar',
                    $artista,
                ),
                [
                    'nome' => '',

                    'origem_geografica_id' => 'invalida',

                    'generos' => [
                        'invalido',
                    ],
                ],
            );

        $resposta->assertForbidden();

        $this->assertDatabaseHas(
            'artistas',
            [
                'id' => $artista->getKey(),

                'nome' => 'Emperor',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que apenas o criador pode eliminar o artista.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_sem_autorizacao_nao_elimina_artista(): void
    {
        $criador = $this->criarUtilizador();

        $outroUtilizador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Reino Unido',
            'GB',
        );

        $artista = $this->criarArtista(
            $criador,
            $origemGeografica,
            'Paradise Lost',
        );

        $resposta = $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'artistas.eliminar',
                    $artista,
                ),
            );

        $resposta->assertForbidden();

        $this->assertNotSoftDeleted(
            'artistas',
            [
                'id' => $artista->getKey(),
            ],
        );
    }

    /**
     * Confirma que o criador pode eliminar logicamente o artista.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criador_elimina_artista(): void
    {
        $criador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Polónia',
            'PL',
        );

        $artista = $this->criarArtista(
            $criador,
            $origemGeografica,
            'Behemoth',
        );

        $this
            ->actingAs(
                $criador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'artistas.eliminar',
                    $artista,
                ),
            )
            ->assertNoContent();

        $this->assertSoftDeleted(
            'artistas',
            [
                'id' => $artista->getKey(),
            ],
        );
    }

    /**
     * Cria um utilizador autenticável e verificado.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     */
    private function criarUtilizador(): Utilizador
    {
        return Utilizador::factory()
            ->create();
    }

    /**
     * Cria uma origem geográfica persistida.
     *
     * @param  string  $nome  Nome da origem.
     * @param  string  $codigo  Código da origem.
     * @return OrigemGeografica Origem criada.
     *
     * @since 2.0.0
     */
    private function criarOrigemGeografica(
        string $nome,
        string $codigo,
    ): OrigemGeografica {
        return OrigemGeografica::factory()
            ->create([
                'nome' => $nome,

                'codigo' => $codigo,
            ]);
    }

    /**
     * Cria um género persistido.
     *
     * @param  string  $nome  Nome do género.
     * @return Genero Género criado.
     *
     * @since 2.0.0
     */
    private function criarGenero(
        string $nome,
    ): Genero {
        return Genero::factory()
            ->comNome(
                $nome,
            )
            ->create();
    }

    /**
     * Cria um artista atribuído ao utilizador indicado.
     *
     * @param  Utilizador  $criador  Utilizador criador.
     * @param  OrigemGeografica  $origemGeografica  Origem do artista.
     * @param  string  $nome  Nome do artista.
     * @return Artista Artista criado.
     *
     * @since 2.0.0
     */
    private function criarArtista(
        Utilizador $criador,
        OrigemGeografica $origemGeografica,
        string $nome,
    ): Artista {
        $this->actingAs(
            $criador,
            'sessao',
        );

        return Artista::factory()
            ->comNome(
                $nome,
            )
            ->deOrigemGeografica(
                $origemGeografica,
            )
            ->create();
    }
}
