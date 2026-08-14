<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Banda;
use App\Models\Musica\Genero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa os pedidos HTTP associados às bandas.
 *
 * @since 2.0.0
 */
final class ControladorBandaTest extends TestCase
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
     * Confirma que a criação JSON associa explicitamente a origem e os
     * géneros, respeitando o contrato de atribuição em massa do modelo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_banda_em_json_com_as_relacoes_esperadas(): void
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
                    'bandas.guardar',
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
                'Banda criada com sucesso.',
            )
            ->assertJsonPath(
                'banda.nome',
                'Moonspell',
            )
            ->assertJsonPath(
                'banda.origem_geografica.id',
                (int) $origemGeografica->getKey(),
            )
            ->assertJsonPath(
                'banda.origem_geografica.nome',
                'Portugal',
            )
            ->assertJsonPath(
                'banda.generos.0.id',
                (int) $genero->getKey(),
            )
            ->assertJsonPath(
                'banda.generos.0.nome',
                'Heavy Metal',
            );

        $identificadorBanda = $resposta->json(
            'banda.id',
        );

        self::assertIsInt(
            $identificadorBanda,
        );

        $this->assertDatabaseHas(
            'bandas',
            [
                'id' => $identificadorBanda,

                'nome' => 'Moonspell',

                'origem_geografica_id' => $origemGeografica->getKey(),

                'criado_por_id' => $utilizador->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'banda_genero',
            [
                'banda_id' => $identificadorBanda,

                'genero_id' => $genero->getKey(),
            ],
        );
    }

    /**
     * Confirma que o criador pode atualizar os dados e as relações da banda.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criador_atualiza_banda(): void
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

        $banda = Banda::factory()
            ->comNome(
                'Insomnium',
            )
            ->deOrigemGeografica(
                $origemInicial,
            )
            ->create();

        $banda
            ->generos()
            ->attach(
                $generoInicial->getKey(),
            );

        $resposta = $this->patch(
            route(
                'bandas.atualizar',
                $banda,
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
                    'bandas.indice',
                ),
            )
            ->assertSessionHas(
                'sucesso',
                'Banda atualizada com sucesso.',
            );

        $this->assertDatabaseHas(
            'bandas',
            [
                'id' => $banda->getKey(),

                'nome' => 'Insomnium Atualizada',

                'origem_geografica_id' => $origemNova->getKey(),

                'atualizado_por_id' => $utilizador->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseMissing(
            'banda_genero',
            [
                'banda_id' => $banda->getKey(),

                'genero_id' => $generoInicial->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'banda_genero',
            [
                'banda_id' => $banda->getKey(),

                'genero_id' => $generoNovo->getKey(),
            ],
        );
    }

    /**
     * Confirma que a autorização antecede as consultas de validação.
     *
     * Mesmo com dados inválidos, um utilizador que não criou a banda deve
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

        $banda = $this->criarBanda(
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
                    'bandas.atualizar',
                    $banda,
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
            'bandas',
            [
                'id' => $banda->getKey(),

                'nome' => 'Emperor',

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que apenas o criador pode eliminar a banda.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_sem_autorizacao_nao_elimina_banda(): void
    {
        $criador = $this->criarUtilizador();

        $outroUtilizador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Reino Unido',
            'GB',
        );

        $banda = $this->criarBanda(
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
                    'bandas.eliminar',
                    $banda,
                ),
            );

        $resposta->assertForbidden();

        $this->assertNotSoftDeleted(
            'bandas',
            [
                'id' => $banda->getKey(),
            ],
        );
    }

    /**
     * Confirma que o criador pode eliminar logicamente a banda.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criador_elimina_banda(): void
    {
        $criador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Polónia',
            'PL',
        );

        $banda = $this->criarBanda(
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
                    'bandas.eliminar',
                    $banda,
                ),
            )
            ->assertNoContent();

        $this->assertSoftDeleted(
            'bandas',
            [
                'id' => $banda->getKey(),
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
     * Cria uma banda atribuída ao utilizador indicado.
     *
     * @param  Utilizador  $criador  Utilizador criador.
     * @param  OrigemGeografica  $origemGeografica  Origem da banda.
     * @param  string  $nome  Nome da banda.
     * @return Banda Banda criada.
     *
     * @since 2.0.0
     */
    private function criarBanda(
        Utilizador $criador,
        OrigemGeografica $origemGeografica,
        string $nome,
    ): Banda {
        $this->actingAs(
            $criador,
            'sessao',
        );

        return Banda::factory()
            ->comNome(
                $nome,
            )
            ->deOrigemGeografica(
                $origemGeografica,
            )
            ->create();
    }
}
