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
                'artista.rotulo_selecao',
                'Moonspell — Portugal · Heavy Metal',
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
     * Confirma que a criação JSON de um artista com nome repetido exige
     * confirmação explícita.
     *
     * Quando existe um artista ativo com o mesmo nome, nenhum novo registo deve
     * ser persistido e a resposta deve identificar o homónimo encontrado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_json_com_nome_repetido_exige_confirmacao(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemExistente = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemNova = $this->criarOrigemGeografica(
            'Estados Unidos',
            'US',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $artistaExistente = $this->criarArtista(
            $utilizador,
            $origemExistente,
            'Ghost',
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
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemNova->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            );

        $resposta
            ->assertStatus(
                409,
            )
            ->assertJsonPath(
                'codigo',
                'confirmacao_nome_repetido_necessaria',
            )
            ->assertJsonPath(
                'mensagem',
                'Já existem artistas com este nome. Confirma se pretendes criar um novo artista.',
            )
            ->assertJsonCount(
                1,
                'artistas_homonimos',
            )
            ->assertJsonPath(
                'artistas_homonimos.0.id',
                (int) $artistaExistente->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.0.nome',
                'Ghost',
            )
            ->assertJsonPath(
                'artistas_homonimos.0.origem_geografica.id',
                (int) $origemExistente->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.0.origem_geografica.nome',
                'Suécia',
            );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'nome' => 'Ghost',

                'origem_geografica_id' => $origemNova->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseCount(
            'artistas',
            1,
        );
    }

    /**
     * Confirma que todos os artistas ativos com o mesmo nome são devolvidos
     * quando a criação JSON exige confirmação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_json_com_nome_repetido_devolve_todos_os_homonimos(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemPrimeiroArtista = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemSegundoArtista = $this->criarOrigemGeografica(
            'Estados Unidos',
            'US',
        );

        $origemNovoArtista = $this->criarOrigemGeografica(
            'Canadá',
            'CA',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $primeiroArtista = $this->criarArtista(
            $utilizador,
            $origemPrimeiroArtista,
            'Ghost',
        );

        $segundoArtista = $this->criarArtista(
            $utilizador,
            $origemSegundoArtista,
            'Ghost',
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
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemNovoArtista->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            );

        $resposta
            ->assertStatus(
                409,
            )
            ->assertJsonCount(
                2,
                'artistas_homonimos',
            )
            ->assertJsonPath(
                'artistas_homonimos.0.id',
                (int) $primeiroArtista->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.0.nome',
                'Ghost',
            )
            ->assertJsonPath(
                'artistas_homonimos.0.origem_geografica.id',
                (int) $origemPrimeiroArtista->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.0.origem_geografica.nome',
                'Suécia',
            )
            ->assertJsonPath(
                'artistas_homonimos.1.id',
                (int) $segundoArtista->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.1.nome',
                'Ghost',
            )
            ->assertJsonPath(
                'artistas_homonimos.1.origem_geografica.id',
                (int) $origemSegundoArtista->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.1.origem_geografica.nome',
                'Estados Unidos',
            );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'nome' => 'Ghost',

                'origem_geografica_id' => $origemNovoArtista->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseCount(
            'artistas',
            2,
        );
    }

    /**
     * Confirma que a criação JSON de um artista homónimo é permitida quando o
     * utilizador confirma explicitamente que pretende criar um novo artista.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_json_com_nome_repetido_confirmado_cria_novo_artista(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemExistente = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemNova = $this->criarOrigemGeografica(
            'Estados Unidos',
            'US',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $artistaExistente = $this->criarArtista(
            $utilizador,
            $origemExistente,
            'Ghost',
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
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemNova->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],

                    'confirmar_nome_repetido' => 1,
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
                'Ghost',
            )
            ->assertJsonPath(
                'artista.origem_geografica.id',
                (int) $origemNova->getKey(),
            )
            ->assertJsonPath(
                'artista.origem_geografica.nome',
                'Estados Unidos',
            )
            ->assertJsonPath(
                'artista.generos.0.id',
                (int) $genero->getKey(),
            )
            ->assertJsonPath(
                'artista.generos.0.nome',
                'Heavy Metal',
            );

        $identificadorNovoArtista =
            $resposta->json(
                'artista.id',
            );

        self::assertIsInt(
            $identificadorNovoArtista,
        );

        self::assertNotSame(
            (int) $artistaExistente->getKey(),
            $identificadorNovoArtista,
        );

        $this->assertDatabaseHas(
            'artistas',
            [
                'id' => $artistaExistente->getKey(),

                'nome' => 'Ghost',

                'origem_geografica_id' => $origemExistente->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'artistas',
            [
                'id' => $identificadorNovoArtista,

                'nome' => 'Ghost',

                'origem_geografica_id' => $origemNova->getKey(),

                'criado_por_id' => $utilizador->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'artista_genero',
            [
                'artista_id' => $identificadorNovoArtista,

                'genero_id' => $genero->getKey(),
            ],
        );

        $this->assertDatabaseCount(
            'artistas',
            2,
        );
    }

    /**
     * Confirma que um sinal inválido de confirmação de nome repetido é rejeitado.
     *
     * Um valor presente mas não aceite não representa uma confirmação explícita
     * e não pode permitir a criação do artista homónimo.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_json_com_confirmacao_de_nome_repetido_invalida_e_rejeitada(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemExistente = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemNova = $this->criarOrigemGeografica(
            'Estados Unidos',
            'US',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $this->criarArtista(
            $utilizador,
            $origemExistente,
            'Ghost',
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
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemNova->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],

                    'confirmar_nome_repetido' => 0,
                ],
            );

        $resposta
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'confirmar_nome_repetido',
            ])
            ->assertJsonPath(
                'errors.confirmar_nome_repetido.0',
                'A confirmação da criação de um artista com nome repetido não é válida.',
            );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'nome' => 'Ghost',

                'origem_geografica_id' => $origemNova->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseCount(
            'artistas',
            1,
        );
    }

    /**
     * Confirma que a deteção de homónimos utiliza o nome normalizado.
     *
     * Espaços exteriores e sequências de espaços interiores não podem permitir
     * contornar a confirmação obrigatória de um artista com o mesmo nome.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_json_deteta_homonimo_apos_normalizar_nome(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemExistente = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemNova = $this->criarOrigemGeografica(
            'Estados Unidos',
            'US',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $artistaExistente = $this->criarArtista(
            $utilizador,
            $origemExistente,
            'Ghost Band',
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
                    'nome' => '  Ghost   Band  ',

                    'origem_geografica_id' => (int) $origemNova->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            );

        $resposta
            ->assertStatus(
                409,
            )
            ->assertJsonPath(
                'codigo',
                'confirmacao_nome_repetido_necessaria',
            )
            ->assertJsonCount(
                1,
                'artistas_homonimos',
            )
            ->assertJsonPath(
                'artistas_homonimos.0.id',
                (int) $artistaExistente->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.0.nome',
                'Ghost Band',
            );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'nome' => 'Ghost Band',

                'origem_geografica_id' => $origemNova->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseCount(
            'artistas',
            1,
        );
    }

    /**
     * Confirma que os dados de homónimos enviados pelo cliente não influenciam
     * a deteção efetuada pelo servidor.
     *
     * A lista de candidatos deve ser sempre reconstruída a partir dos registos
     * ativos persistidos, não sendo possível contornar a confirmação através de
     * identificadores ou dados manipulados no pedido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function dados_de_homonimos_enviados_pelo_cliente_nao_contornam_confirmacao(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemExistente = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemNova = $this->criarOrigemGeografica(
            'Estados Unidos',
            'US',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $artistaExistente = $this->criarArtista(
            $utilizador,
            $origemExistente,
            'Ghost',
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
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemNova->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],

                    'artistas_homonimos' => [
                        [
                            'id' => 999999,

                            'nome' => 'Artista Manipulado',

                            'origem_geografica' => [
                                'id' => 999999,

                                'nome' => 'Origem Manipulada',
                            ],
                        ],
                    ],
                ],
            );

        $resposta
            ->assertStatus(
                409,
            )
            ->assertJsonCount(
                1,
                'artistas_homonimos',
            )
            ->assertJsonPath(
                'artistas_homonimos.0.id',
                (int) $artistaExistente->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.0.nome',
                'Ghost',
            )
            ->assertJsonPath(
                'artistas_homonimos.0.origem_geografica.id',
                (int) $origemExistente->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.0.origem_geografica.nome',
                'Suécia',
            );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'nome' => 'Ghost',

                'origem_geografica_id' => $origemNova->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseCount(
            'artistas',
            1,
        );
    }

    /**
     * Confirma que o formulário normal apresenta os homónimos encontrados e
     * permite confirmar explicitamente a criação de um novo artista.
     *
     * O pedido inicial deve regressar ao formulário sem persistir outro artista,
     * mantendo os dados introduzidos e apresentando a informação necessária para
     * o utilizador tomar uma decisão consciente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_normal_com_nome_repetido_apresenta_confirmacao(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemExistente = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemNova = $this->criarOrigemGeografica(
            'Estados Unidos',
            'US',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $artistaExistente = $this->criarArtista(
            $utilizador,
            $origemExistente,
            'Ghost',
        );

        $respostaSubmissao = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->from(
                route(
                    'artistas.criar',
                ),
            )
            ->post(
                route(
                    'artistas.guardar',
                ),
                [
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemNova->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            );

        $respostaSubmissao
            ->assertRedirect(
                route(
                    'artistas.criar',
                ),
            )
            ->assertSessionHas(
                'confirmacao_nome_repetido',
                static function (
                    array $dadosConfirmacao,
                ) use (
                    $artistaExistente,
                    $origemExistente,
                ): bool {
                    return
                        $dadosConfirmacao['codigo']
                        === 'confirmacao_nome_repetido_necessaria'
                        && $dadosConfirmacao['artistas_homonimos'][0]['id']
                        === (int) $artistaExistente->getKey()
                        && $dadosConfirmacao['artistas_homonimos'][0]['nome']
                        === 'Ghost'
                        && $dadosConfirmacao['artistas_homonimos'][0]['origem_geografica']['id']
                        === (int) $origemExistente->getKey()
                        && $dadosConfirmacao['artistas_homonimos'][0]['origem_geografica']['nome']
                        === 'Suécia';
                },
            );

        $this->assertDatabaseMissing(
            'artistas',
            [
                'nome' => 'Ghost',

                'origem_geografica_id' => $origemNova->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseCount(
            'artistas',
            1,
        );

        $respostaFormulario = $this->get(
            route(
                'artistas.criar',
            ),
        );

        $respostaFormulario
            ->assertOk()
            ->assertSeeHtml(
                'class="aviso-artista-homonimo"',
            )
            ->assertDontSeeHtml(
                'class="alert alert-warning mb-4"',
            )
            ->assertSee(
                'Artista com o mesmo nome',
            )
            ->assertSee(
                'Já existem artistas com este nome. Confirma se pretendes criar um novo artista.',
            )
            ->assertSee(
                'Ghost',
            )
            ->assertSee(
                'Suécia',
            )
            ->assertSee(
                'Ano de início desconhecido',
            )
            ->assertSee(
                'Se for um artista diferente, volta a confirmar a criação.',
            )
            ->assertSee(
                'Criar artista mesmo assim',
            )
            ->assertSee(
                'name="confirmar_nome_repetido"',
                false,
            );
    }

    /**
     * Confirma que a criação normal de um artista homónimo é concluída depois
     * de o utilizador confirmar explicitamente a operação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_normal_com_nome_repetido_confirmado_cria_novo_artista(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemExistente = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $origemNova = $this->criarOrigemGeografica(
            'Estados Unidos',
            'US',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $artistaExistente = $this->criarArtista(
            $utilizador,
            $origemExistente,
            'Ghost',
        );

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->from(
                route(
                    'artistas.criar',
                ),
            )
            ->post(
                route(
                    'artistas.guardar',
                ),
                [
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemNova->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            )
            ->assertRedirect(
                route(
                    'artistas.criar',
                ),
            );

        $resposta = $this
            ->post(
                route(
                    'artistas.guardar',
                ),
                [
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemNova->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],

                    'confirmar_nome_repetido' => 1,
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
                'Artista criado com sucesso.',
            )
            ->assertSessionHasNoErrors();

        $novoArtista = Artista::query()
            ->where(
                'nome',
                'Ghost',
            )
            ->where(
                'origem_geografica_id',
                $origemNova->getKey(),
            )
            ->firstOrFail();

        self::assertNotSame(
            (int) $artistaExistente->getKey(),
            (int) $novoArtista->getKey(),
        );

        $this->assertDatabaseHas(
            'artistas',
            [
                'id' => $artistaExistente->getKey(),

                'nome' => 'Ghost',

                'origem_geografica_id' => $origemExistente->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'artistas',
            [
                'id' => $novoArtista->getKey(),

                'nome' => 'Ghost',

                'origem_geografica_id' => $origemNova->getKey(),

                'criado_por_id' => $utilizador->getKey(),

                'deleted_at' => null,
            ],
        );

        $this->assertDatabaseHas(
            'artista_genero',
            [
                'artista_id' => $novoArtista->getKey(),

                'genero_id' => $genero->getKey(),
            ],
        );

        $this->assertDatabaseCount(
            'artistas',
            2,
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
     * Confirma que um artista eliminado logicamente não exige confirmação para
     * reutilizar o mesmo nome numa nova criação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function criacao_json_ignora_homonimo_eliminado_logicamente(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Suécia',
            'SE',
        );

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $artistaEliminado = $this->criarArtista(
            $utilizador,
            $origemGeografica,
            'Ghost',
        );

        $artistaEliminado->deleteOrFail();

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
                    'nome' => 'Ghost',

                    'origem_geografica_id' => (int) $origemGeografica->getKey(),

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            );

        $resposta
            ->assertCreated()
            ->assertJsonPath(
                'artista.nome',
                'Ghost',
            );

        self::assertSame(
            1,
            Artista::query()
                ->where(
                    'nome',
                    'Ghost',
                )
                ->count(),
        );

        self::assertSame(
            2,
            Artista::withTrashed()
                ->where(
                    'nome',
                    'Ghost',
                )
                ->count(),
        );
    }

    /**
     * Confirma que a criação JSON permite um artista sem origem geográfica.
     *
     * @since 2.0.0
     */
    #[Test]
    public function cria_artista_em_json_sem_origem_geografica(): void
    {
        $utilizador = $this->criarUtilizador();

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

                    'generos' => [
                        (int) $genero->getKey(),
                    ],
                ],
            );

        $resposta
            ->assertCreated()
            ->assertJsonStructure([
                'artista' => [
                    'origem_geografica_id',
                    'origem_geografica',
                ],
            ])
            ->assertJsonPath(
                'artista.nome',
                'Moonspell',
            )
            ->assertJsonPath(
                'artista.rotulo_selecao',
                'Moonspell · Heavy Metal',
            )
            ->assertJsonPath(
                'artista.origem_geografica_id',
                null,
            )
            ->assertJsonPath(
                'artista.origem_geografica',
                null,
            );

        $identificadorArtista =
            $resposta->json(
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

                'origem_geografica_id' => null,

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
     * Confirma que a atualização permite remover a origem geográfica existente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function atualiza_artista_removendo_origem_geografica(): void
    {
        $utilizador = $this->criarUtilizador();

        $origemGeografica = $this->criarOrigemGeografica(
            'Finlândia',
            'FI',
        );

        $genero = $this->criarGenero(
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
                $origemGeografica,
            )
            ->create();

        $artista
            ->generos()
            ->attach(
                $genero->getKey(),
            );

        $resposta = $this->patchJson(
            route(
                'artistas.atualizar',
                $artista,
            ),
            [
                'nome' => 'Insomnium',

                'origem_geografica_id' => null,

                'generos' => [
                    (int) $genero->getKey(),
                ],
            ],
        );

        $resposta
            ->assertOk()
            ->assertJsonStructure([
                'artista' => [
                    'origem_geografica_id',
                    'origem_geografica',
                ],
            ])
            ->assertJsonPath(
                'artista.rotulo_selecao',
                'Insomnium · Melodic Death Metal',
            )
            ->assertJsonPath(
                'artista.origem_geografica_id',
                null,
            )
            ->assertJsonPath(
                'artista.origem_geografica',
                null,
            );

        $this->assertDatabaseHas(
            'artistas',
            [
                'id' => $artista->getKey(),

                'origem_geografica_id' => null,

                'atualizado_por_id' => $utilizador->getKey(),

                'deleted_at' => null,
            ],
        );
    }

    /**
     * Confirma que um homónimo sem origem geográfica é serializado sem inventar
     * uma origem.
     *
     * @since 2.0.0
     */
    #[Test]
    public function confirmacao_de_homonimo_suporta_artista_sem_origem_geografica(): void
    {
        $utilizador = $this->criarUtilizador();

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artistaExistente = Artista::factory()
            ->comNome(
                'Ghost',
            )
            ->create([
                'origem_geografica_id' => null,
            ]);

        $resposta = $this->postJson(
            route(
                'artistas.guardar',
            ),
            [
                'nome' => 'Ghost',

                'generos' => [
                    (int) $genero->getKey(),
                ],
            ],
        );

        $resposta
            ->assertStatus(
                409,
            )
            ->assertJsonStructure([
                'artistas_homonimos' => [
                    [
                        'id',
                        'nome',
                        'origem_geografica',
                    ],
                ],
            ])
            ->assertJsonPath(
                'artistas_homonimos.0.id',
                (int) $artistaExistente->getKey(),
            )
            ->assertJsonPath(
                'artistas_homonimos.0.nome',
                'Ghost',
            )
            ->assertJsonPath(
                'artistas_homonimos.0.origem_geografica',
                null,
            );

        $this->assertDatabaseCount(
            'artistas',
            1,
        );
    }

    /**
     * Confirma que a listagem e os detalhes apresentam corretamente um artista
     * sem origem geográfica.
     *
     * @since 2.0.0
     */
    #[Test]
    public function apresenta_artista_sem_origem_geografica(): void
    {
        $utilizador = $this->criarUtilizador();

        $genero = $this->criarGenero(
            'Heavy Metal',
        );

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista = Artista::factory()
            ->comNome(
                'Ghost',
            )
            ->create([
                'origem_geografica_id' => null,
            ]);

        $artista
            ->generos()
            ->attach(
                $genero->getKey(),
            );

        $this
            ->get(
                route(
                    'artistas.indice',
                ),
            )
            ->assertOk()
            ->assertSee(
                'Ghost',
            )
            ->assertSee(
                'Não indicada',
            );

        $this
            ->get(
                route(
                    'artistas.detalhes',
                    $artista,
                ),
            )
            ->assertOk()
            ->assertSee(
                'Ghost',
            )
            ->assertSee(
                'Heavy Metal',
            );
    }

    /**
     * Confirma que a origem geográfica é apresentada como opcional nos
     * formulários de criação e edição.
     *
     * @since 2.0.0
     */
    #[Test]
    public function formularios_apresentam_origem_geografica_como_opcional(): void
    {
        $utilizador = $this->criarUtilizador();

        $this->actingAs(
            $utilizador,
            'sessao',
        );

        $artista = Artista::factory()
            ->comNome(
                'Ghost',
            )
            ->create([
                'origem_geografica_id' => null,
            ]);

        $respostas = [
            $this->get(
                route(
                    'artistas.criar',
                ),
            ),

            $this->get(
                route(
                    'artistas.editar',
                    $artista,
                ),
            ),
        ];

        foreach ($respostas as $resposta) {
            $resposta
                ->assertOk()
                ->assertSee(
                    'Origem geográfica',
                )
                ->assertSee(
                    '(opcional)',
                );

            self::assertDoesNotMatchRegularExpression(
                '/<select\b(?=[^>]*\bid="origem-geografica-artista")[^>]*\brequired\b[^>]*>/s',
                $resposta->getContent(),
            );
        }
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
