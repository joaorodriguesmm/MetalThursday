<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados aos géneros musicais.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorGeneroTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prepara cada teste sem depender dos ficheiros produzidos pelo Vite.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Confirma que a criação JSON devolve as relações no contrato público.
     *
     * Um género acabado de criar nunca possui géneros filhos, pelo que a
     * resposta deve apresentar explicitamente uma lista vazia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function cria_genero_em_json_com_as_relacoes_esperadas(): void
    {
        $utilizador = $this->criarUtilizador();

        $generoPai = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->criarGenero(
                'Metal',
            );

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'generos.guardar',
                ),
                [
                    'nome' => 'Heavy Metal',

                    'generos_pai' => [
                        (int) $generoPai->getKey(),
                    ],
                ],
            );

        $resposta
            ->assertCreated()
            ->assertJsonPath(
                'mensagem',
                'Género criado com sucesso.',
            )
            ->assertJsonPath(
                'genero.nome',
                'Heavy Metal',
            )
            ->assertJsonPath(
                'genero.generos_pai.0.id',
                (int) $generoPai->getKey(),
            )
            ->assertJsonPath(
                'genero.generos_pai.0.nome',
                'Metal',
            )
            ->assertJsonPath(
                'genero.generos_filhos',
                [],
            );
    }

    /**
     * Confirma que o criador pode atualizar o género.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function criador_atualiza_genero(): void
    {
        $utilizador = $this->criarUtilizador();

        $genero = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->criarGenero(
                'Doom Metal',
            );

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patch(
                route(
                    'generos.atualizar',
                    $genero,
                ),
                [
                    'nome' => 'Epic Doom Metal',

                    'generos_pai' => [],
                ],
            );

        $resposta
            ->assertRedirect(
                route(
                    'generos.indice',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                'Género atualizado com sucesso.',
            );

        $this->assertDatabaseHas(
            'generos',
            [
                'id' => $genero->getKey(),

                'nome' => 'Epic Doom Metal',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que a autorização antecede as consultas de validação.
     *
     * Mesmo com dados inválidos, um utilizador que não criou o género deve
     * receber uma resposta de proibição, e não uma resposta de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_sem_autorizacao_e_rejeitado_antes_da_validacao(): void
    {
        $criador = $this->criarUtilizador();
        $outroUtilizador = $this->criarUtilizador();

        $genero = $this
            ->actingAs(
                $criador,
                'sessao',
            )
            ->criarGenero(
                'Black Metal',
            );

        $resposta = $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->patchJson(
                route(
                    'generos.atualizar',
                    $genero,
                ),
                [
                    'nome' => '',

                    'generos_pai' => [
                        'invalido',
                    ],
                ],
            );

        $resposta->assertForbidden();

        $this->assertDatabaseHas(
            'generos',
            [
                'id' => $genero->getKey(),

                'nome' => 'Black Metal',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que apenas o criador pode eliminar o género.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_sem_autorizacao_nao_elimina_genero(): void
    {
        $criador = $this->criarUtilizador();
        $outroUtilizador = $this->criarUtilizador();

        $genero = $this
            ->actingAs(
                $criador,
                'sessao',
            )
            ->criarGenero(
                'Death Metal',
            );

        $resposta = $this
            ->actingAs(
                $outroUtilizador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'generos.eliminar',
                    $genero,
                ),
            );

        $resposta->assertForbidden();

        $this->assertNotSoftDeleted(
            'generos',
            [
                'id' => $genero->getKey(),
            ],
        );
    }

    /**
     * Cria um utilizador autenticável e verificado.
     *
     * @return Utilizador Utilizador criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarUtilizador(): Utilizador
    {
        return Utilizador::factory()
            ->create();
    }

    /**
     * Cria um género atribuído ao utilizador autenticado no teste.
     *
     * @param  string  $nome  Nome do género.
     * @return Genero Género criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
}
