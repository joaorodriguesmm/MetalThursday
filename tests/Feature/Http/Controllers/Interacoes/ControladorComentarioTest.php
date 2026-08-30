<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a publicação, consulta, resposta, atualização e eliminação de
 * comentários.
 *
 * @since 2.0.0
 */
final class ControladorComentarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma a publicação de um comentário numa secção.
     *
     * @since 2.0.0
     */
    #[Test]
    public function publica_comentario_numa_seccao(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()
            ->create();

        $seccao = SeccaoMetalThursday::factory()
            ->create();

        $resposta = $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->postJson(
                route(
                    'comentarios.guardar',
                    [
                        'tipoComentavel' => TipoEntidadeInteracao::SeccaoMetalThursday->value,

                        'identificadorComentavel' => $seccao->getKey(),
                    ],
                ),
                [
                    'conteudo' => 'Comentário publicado.',
                ],
            );

        $resposta
            ->assertCreated()
            ->assertJsonPath(
                'mensagem',
                'Comentário publicado com sucesso.',
            )
            ->assertJsonPath(
                'comentario.conteudo',
                'Comentário publicado.',
            )
            ->assertJsonPath(
                'comentario.comentario_pai_id',
                null,
            );

        $htmlComentario =
            $resposta->json(
                'comentario_html',
            );

        self::assertIsString(
            $htmlComentario,
        );

        self::assertStringContainsString(
            'Comentário publicado.',
            $htmlComentario,
        );

        self::assertStringContainsString(
            'Editar',
            $htmlComentario,
        );

        self::assertStringContainsString(
            'Eliminar',
            $htmlComentario,
        );

        $this->assertDatabaseHas(
            'comentarios',
            [
                'utilizador_id' => $autor->getKey(),

                'tipo_comentavel' => $seccao->getMorphClass(),

                'comentavel_id' => $seccao->getKey(),

                'conteudo' => 'Comentário publicado.',

                'comentario_pai_id' => null,
            ],
        );
    }

    /**
     * Confirma que uma resposta a outra resposta fica associada ao comentário
     * concretamente respondido.
     *
     * @since 2.0.0
     */
    #[Test]
    public function resposta_a_uma_resposta_preserva_o_pai_real(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()
            ->create();

        $autorResposta = Utilizador::factory()
            ->create();

        $novoAutorResposta = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $comentarioPrincipal =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Comentário principal.',

                    'comentario_pai_id' => null,
                ]);

        $respostaExistente =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autorResposta->getKey(),

                    'conteudo' => 'Primeira resposta.',

                    'comentario_pai_id' => $comentarioPrincipal->getKey(),
                ]);

        $resposta = $this
            ->actingAs(
                $novoAutorResposta,
                'sessao',
            )
            ->postJson(
                route(
                    'comentarios.respostas.guardar',
                    $respostaExistente,
                ),
                [
                    'conteudo' => 'Segunda resposta.',
                ],
            );

        $resposta
            ->assertCreated()
            ->assertJsonPath(
                'mensagem',
                'Resposta publicada com sucesso.',
            )
            ->assertJsonPath(
                'comentario.conteudo',
                'Segunda resposta.',
            )
            ->assertJsonPath(
                'comentario.comentario_pai_id',
                $respostaExistente->getKey(),
            );

        $htmlResposta =
            $resposta->json(
                'comentario_html',
            );

        self::assertIsString(
            $htmlResposta,
        );

        self::assertStringContainsString(
            'Segunda resposta.',
            $htmlResposta,
        );

        $this->assertDatabaseHas(
            'comentarios',
            [
                'utilizador_id' => $novoAutorResposta->getKey(),

                'tipo_comentavel' => $metalThursday->getMorphClass(),

                'comentavel_id' => $metalThursday->getKey(),

                'conteudo' => 'Segunda resposta.',

                'comentario_pai_id' => $respostaExistente->getKey(),
            ],
        );

        $this->assertDatabaseMissing(
            'comentarios',
            [
                'conteudo' => 'Segunda resposta.',

                'comentario_pai_id' => $comentarioPrincipal->getKey(),
            ],
        );
    }

    /**
     * Confirma que a consulta de respostas devolve apenas os filhos diretos
     * do comentário indicado e informa a existência de níveis seguintes.
     *
     * @since 2.0.0
     */
    #[Test]
    public function lista_apenas_respostas_diretas_de_um_comentario(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $comentarioPrincipal =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Comentário principal.',

                    'comentario_pai_id' => null,
                ]);

        $primeiraResposta =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Primeira resposta direta.',

                    'comentario_pai_id' => $comentarioPrincipal->getKey(),
                ]);

        $segundaResposta =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Segunda resposta direta.',

                    'comentario_pai_id' => $comentarioPrincipal->getKey(),
                ]);

        $respostaNeta =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Resposta de terceiro nível.',

                    'comentario_pai_id' => $primeiraResposta->getKey(),
                ]);

        $resposta = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->getJson(
                route(
                    'comentarios.respostas.indice',
                    $comentarioPrincipal,
                ),
            );

        $resposta
            ->assertOk()
            ->assertJsonPath(
                'comentario_id',
                $comentarioPrincipal->getKey(),
            )
            ->assertJsonPath(
                'numero_respostas',
                2,
            )
            ->assertJsonPath(
                'respostas.0.comentario.id',
                $primeiraResposta->getKey(),
            )
            ->assertJsonPath(
                'respostas.0.comentario.numero_respostas',
                1,
            )
            ->assertJsonPath(
                'respostas.1.comentario.id',
                $segundaResposta->getKey(),
            )
            ->assertJsonCount(
                2,
                'respostas',
            );

        $identificadores =
            collect(
                $resposta->json(
                    'respostas',
                ),
            )
                ->pluck(
                    'comentario.id',
                )
                ->all();

        self::assertNotContains(
            $respostaNeta->getKey(),
            $identificadores,
        );

        $htmlPrimeiraResposta =
            $resposta->json(
                'respostas.0.comentario_html',
            );

        self::assertIsString(
            $htmlPrimeiraResposta,
        );

        self::assertStringContainsString(
            'Primeira resposta direta.',
            $htmlPrimeiraResposta,
        );

        $respostasDoSegundoNivel = $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->getJson(
                route(
                    'comentarios.respostas.indice',
                    $primeiraResposta,
                ),
            );

        $respostasDoSegundoNivel
            ->assertOk()
            ->assertJsonPath(
                'numero_respostas',
                1,
            )
            ->assertJsonPath(
                'respostas.0.comentario.id',
                $respostaNeta->getKey(),
            );
    }

    /**
     * Confirma que o autor pode atualizar o conteúdo do comentário.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_atualiza_comentario(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $comentario = $this->criarComentario(
            $autor,
        );

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->patchJson(
                route(
                    'comentarios.atualizar',
                    $comentario,
                ),
                [
                    'conteudo' => 'Comentário atualizado.',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'mensagem',
                'Comentário atualizado com sucesso.',
            )
            ->assertJsonPath(
                'comentario.conteudo',
                'Comentário atualizado.',
            );

        $this->assertDatabaseHas(
            'comentarios',
            [
                'id' => $comentario->getKey(),

                'conteudo' => 'Comentário atualizado.',
            ],
        );
    }

    /**
     * Confirma que um utilizador que não é o autor é rejeitado antes de a
     * aplicação obter um bloqueio exclusivo sobre o comentário.
     *
     * @since 2.0.0
     */
    #[Test]
    public function utilizador_que_nao_e_autor_nao_bloqueia_nem_elimina_comentario(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $comentario = $this->criarComentario(
            $autor,
        );

        $utilizador = Utilizador::factory()
            ->create();

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] = mb_strtolower(
                    $consulta->sql,
                );
            },
        );

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->deleteJson(
                route(
                    'comentarios.eliminar',
                    $comentario,
                ),
            )
            ->assertForbidden();

        $this->assertNotSoftDeleted(
            'comentarios',
            [
                'id' => $comentario->getKey(),
            ],
        );

        self::assertSame(
            [],
            array_values(
                array_filter(
                    $consultas,
                    static fn (
                        string $consulta,
                    ): bool => str_contains(
                        $consulta,
                        'for update',
                    ),
                ),
            ),
        );
    }

    /**
     * Confirma que um comentário sem respostas é eliminado logicamente.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_elimina_comentario_sem_respostas(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $comentario = $this->criarComentario(
            $autor,
        );

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->deleteJson(
                route(
                    'comentarios.eliminar',
                    $comentario,
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'mensagem',
                'Comentário eliminado com sucesso.',
            )
            ->assertJsonPath(
                'modo_eliminacao',
                'remover',
            )
            ->assertJsonPath(
                'comentario_id',
                $comentario->getKey(),
            )
            ->assertJsonPath(
                'numero_conteudos_removidos',
                1,
            )
            ->assertJsonPath(
                'comentarios_removidos_ids',
                [
                    $comentario->getKey(),
                ],
            )
            ->assertJsonPath(
                'pai_atualizado',
                null,
            );

        $this->assertSoftDeleted(
            'comentarios',
            [
                'id' => $comentario->getKey(),
            ],
        );
    }

    /**
     * Confirma que a eliminação de um comentário com respostas preserva o nó
     * estrutural sem expor conteúdo, autor ou ações do comentário eliminado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_elimina_conteudo_de_comentario_com_respostas(): void
    {
        $autor = Utilizador::factory()
            ->create([
                'nome' => 'Autor do comentário eliminado',
            ]);

        $autorResposta = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Conteúdo que será eliminado.',

                    'comentario_pai_id' => null,
                ]);

        $resposta =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autorResposta->getKey(),

                    'conteudo' => 'Resposta que deve ser preservada.',

                    'comentario_pai_id' => $comentario->getKey(),
                ]);

        $respostaHttp = $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->deleteJson(
                route(
                    'comentarios.eliminar',
                    $comentario,
                ),
            );

        $respostaHttp
            ->assertOk()
            ->assertJsonPath(
                'mensagem',
                'Comentário eliminado com sucesso.',
            )
            ->assertJsonPath(
                'modo_eliminacao',
                'marcador',
            )
            ->assertJsonPath(
                'comentario.id',
                $comentario->getKey(),
            )
            ->assertJsonPath(
                'comentario.conteudo',
                'Comentário eliminado',
            )
            ->assertJsonPath(
                'comentario.conteudo_eliminado',
                true,
            )
            ->assertJsonPath(
                'comentario.numero_respostas',
                1,
            )
            ->assertJsonPath(
                'comentario.utilizador',
                null,
            )->assertJsonPath(
                'numero_conteudos_removidos',
                1,
            )
            ->assertJsonPath(
                'comentarios_removidos_ids',
                [],
            )
            ->assertJsonPath(
                'pai_atualizado',
                null,
            );

        $htmlComentario =
            $respostaHttp->json(
                'comentario_html',
            );

        self::assertIsString(
            $htmlComentario,
        );

        self::assertStringContainsString(
            'Comentário eliminado',
            $htmlComentario,
        );

        self::assertStringNotContainsString(
            'Conteúdo que será eliminado.',
            $htmlComentario,
        );

        self::assertStringNotContainsString(
            'Autor do comentário eliminado',
            $htmlComentario,
        );

        self::assertStringNotContainsString(
            'data-tipo-interacao="alternar-gosto"',
            $htmlComentario,
        );

        self::assertStringNotContainsString(
            'data-formulario-resposta-comentario',
            $htmlComentario,
        );

        self::assertStringNotContainsString(
            'data-tipo-interacao="iniciar-edicao-comentario"',
            $htmlComentario,
        );

        self::assertStringNotContainsString(
            'data-tipo-interacao="eliminar"',
            $htmlComentario,
        );

        self::assertStringContainsString(
            'Ver',
            $htmlComentario,
        );

        self::assertStringContainsString(
            '1',
            $htmlComentario,
        );

        self::assertStringContainsString(
            'resposta',
            $htmlComentario,
        );

        $comentario->refresh();

        $metalThursday->loadCount([
            'comentariosComConteudo as comentarios_count',
        ]);

        self::assertSame(
            1,
            $metalThursday->comentarios_count,
        );

        self::assertNull(
            $comentario->deleted_at,
        );

        self::assertNotNull(
            $comentario->conteudo_eliminado_em,
        );

        $this->assertNotSoftDeleted(
            'comentarios',
            [
                'id' => $resposta->getKey(),
            ],
        );
    }

    /**
     * Cria um comentário principal com conteúdo conhecido.
     *
     * @param  Utilizador  $autor  Autor do comentário.
     * @return Comentario Comentário criado.
     *
     * @since 2.0.0
     */
    private function criarComentario(
        Utilizador $autor,
    ): Comentario {
        $metalThursday = MetalThursday::factory()
            ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Comentário original.',

                    'comentario_pai_id' => null,
                ]);

        self::assertInstanceOf(
            Comentario::class,
            $comentario,
        );

        return $comentario;
    }

    /**
     * Confirma que eliminar uma resposta atualiza a quantidade do pai que
     * permanece na conversa.
     *
     * @since 2.0.0
     */
    #[Test]
    public function eliminar_resposta_atualiza_quantidade_do_pai(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $comentarioPrincipal =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Comentário principal.',

                    'comentario_pai_id' => null,
                ]);

        $primeiraResposta =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Primeira resposta.',

                    'comentario_pai_id' => $comentarioPrincipal->getKey(),
                ]);

        $metalThursday
            ->comentarios()
            ->create([
                'utilizador_id' => $autor->getKey(),

                'conteudo' => 'Segunda resposta.',

                'comentario_pai_id' => $comentarioPrincipal->getKey(),
            ]);

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->deleteJson(
                route(
                    'comentarios.eliminar',
                    $primeiraResposta,
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'modo_eliminacao',
                'remover',
            )
            ->assertJsonPath(
                'comentarios_removidos_ids',
                [
                    $primeiraResposta->getKey(),
                ],
            )
            ->assertJsonPath(
                'pai_atualizado.id',
                $comentarioPrincipal->getKey(),
            )
            ->assertJsonPath(
                'pai_atualizado.numero_respostas',
                1,
            );
    }

    /**
     * Confirma que a remoção da última folha elimina também tombstones ancestrais
     * que deixaram de ser necessários à estrutura.
     *
     * @since 2.0.0
     */
    #[Test]
    public function eliminar_ultima_resposta_remove_tombstones_ancestrais_vazios(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $comentarioPrincipal =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Comentário principal eliminado.',

                    'comentario_pai_id' => null,
                ]);

        $comentarioPrincipal
            ->forceFill([
                'conteudo_eliminado_em' => now(),
            ])
            ->saveOrFail();

        $respostaIntermedia =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Resposta intermédia eliminada.',

                    'comentario_pai_id' => $comentarioPrincipal->getKey(),
                ]);

        $respostaIntermedia
            ->forceFill([
                'conteudo_eliminado_em' => now(),
            ])
            ->saveOrFail();

        $ultimaResposta =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Última resposta ativa.',

                    'comentario_pai_id' => $respostaIntermedia->getKey(),
                ]);

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->deleteJson(
                route(
                    'comentarios.eliminar',
                    $ultimaResposta,
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'modo_eliminacao',
                'remover',
            )
            ->assertJsonPath(
                'numero_conteudos_removidos',
                1,
            )
            ->assertJsonPath(
                'comentarios_removidos_ids',
                [
                    $ultimaResposta->getKey(),
                    $respostaIntermedia->getKey(),
                    $comentarioPrincipal->getKey(),
                ],
            )
            ->assertJsonPath(
                'pai_atualizado',
                null,
            );

        foreach (
            [
                $ultimaResposta,
                $respostaIntermedia,
                $comentarioPrincipal,
            ] as $comentario
        ) {
            $this->assertSoftDeleted(
                'comentarios',
                [
                    'id' => $comentario->getKey(),
                ],
            );
        }
    }

    /**
     * Confirma que um comentário estrutural eliminado já não pode ser editado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_atualiza_comentario_com_conteudo_eliminado(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $comentario =
            $this->criarComentario(
                $autor,
            );

        $comentario
            ->forceFill([
                'conteudo_eliminado_em' => now(),
            ])
            ->saveOrFail();

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->patchJson(
                route(
                    'comentarios.atualizar',
                    $comentario,
                ),
                [
                    'conteudo' => 'Tentativa de recuperar o comentário.',
                ],
            )
            ->assertStatus(
                410,
            );

        $comentario->refresh();

        self::assertSame(
            'Comentário original.',
            $comentario->conteudo,
        );

        self::assertNotNull(
            $comentario->conteudo_eliminado_em,
        );
    }

    /**
     * Confirma que não é possível publicar novas respostas diretamente num
     * tombstone.
     *
     * As respostas que já existiam continuam acessíveis através da consulta do
     * ramo, mas o marcador estrutural deixa de aceitar novas interações.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_responde_a_comentario_com_conteudo_eliminado(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Comentário eliminado.',

                    'comentario_pai_id' => null,
                ]);

        $comentario
            ->forceFill([
                'conteudo_eliminado_em' => now(),
            ])
            ->saveOrFail();

        $metalThursday
            ->comentarios()
            ->create([
                'utilizador_id' => $autor->getKey(),

                'conteudo' => 'Resposta existente.',

                'comentario_pai_id' => $comentario->getKey(),
            ]);

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->postJson(
                route(
                    'comentarios.respostas.guardar',
                    $comentario,
                ),
                [
                    'conteudo' => 'Nova resposta indevida.',
                ],
            )
            ->assertStatus(
                410,
            );

        $this->assertDatabaseMissing(
            'comentarios',
            [
                'conteudo' => 'Nova resposta indevida.',
            ],
        );
    }

    /**
     * Confirma que as respostas existentes de um tombstone continuam
     * consultáveis.
     *
     * @since 2.0.0
     */
    #[Test]
    public function lista_respostas_de_comentario_com_conteudo_eliminado(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Comentário estrutural.',

                    'comentario_pai_id' => null,
                ]);

        $comentario
            ->forceFill([
                'conteudo_eliminado_em' => now(),
            ])
            ->saveOrFail();

        $resposta =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Resposta preservada.',

                    'comentario_pai_id' => $comentario->getKey(),
                ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->getJson(
                route(
                    'comentarios.respostas.indice',
                    $comentario,
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'numero_respostas',
                1,
            )
            ->assertJsonPath(
                'respostas.0.comentario.id',
                $resposta->getKey(),
            )
            ->assertJsonPath(
                'respostas.0.comentario.conteudo',
                'Resposta preservada.',
            );
    }

    #[Test]
    public function nao_lista_respostas_quando_entidade_comentada_foi_eliminada(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Comentário principal',
                ]);

        $metalThursday
            ->comentarios()
            ->create([
                'utilizador_id' => $utilizador->getKey(),

                'conteudo' => 'Resposta',

                'comentario_pai_id' => $comentario->getKey(),
            ]);

        $metalThursday->deleteOrFail();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->getJson(
                route(
                    'comentarios.respostas.indice',
                    [
                        'comentario' => $comentario->getKey(),
                    ],
                ),
            )
            ->assertNotFound();
    }

    /**
     * Confirma que submeter novamente o mesmo conteúdo não marca o comentário
     * como editado.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_marca_comentario_como_editado_quando_conteudo_nao_muda(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $comentario =
            $this->criarComentario(
                $autor,
            );

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->patchJson(
                route(
                    'comentarios.atualizar',
                    $comentario,
                ),
                [
                    'conteudo' => 'Comentário original.',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'comentario.editado_em',
                null,
            );

        $comentario->refresh();

        self::assertNull(
            $comentario->editado_em,
        );

        self::assertSame(
            'Comentário original.',
            $comentario->conteudo,
        );
    }

    /**
     * Confirma que o estado de edição não depende da precisão dos timestamps
     * gerais do comentário.
     *
     * @since 2.0.0
     */
    #[Test]
    public function indicador_editado_persiste_quando_edicao_ocorre_no_mesmo_segundo(): void
    {
        $this->freezeTime();

        $autor =
            Utilizador::factory()
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Comentário original.',

                    'comentario_pai_id' => null,
                ]);

        $resposta =
            $this
                ->actingAs(
                    $autor,
                    'sessao',
                )
                ->patchJson(
                    route(
                        'comentarios.atualizar',
                        $comentario,
                    ),
                    [
                        'conteudo' => 'Comentário atualizado.',
                    ],
                );

        $resposta
            ->assertOk()
            ->assertJsonPath(
                'comentario.editado_em',
                now()->toIso8601String(),
            );

        $comentario->refresh();

        self::assertNotNull(
            $comentario->editado_em,
        );

        self::assertNotNull(
            $comentario->created_at,
        );

        self::assertNotNull(
            $comentario->updated_at,
        );

        self::assertTrue(
            $comentario->created_at->equalTo(
                $comentario->updated_at,
            ),
        );

        $this
            ->get(
                route(
                    'metal-thursday.detalhes',
                    $metalThursday,
                ),
            )
            ->assertOk()
            ->assertSeeText(
                'editado',
            );
    }

    /**
     * Confirma que nem o autor pode publicar um comentário antes da publicação da
     * MetalThursday.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_nao_pode_comentar_metal_thursday_preparada(): void
    {
        Notification::fake();

        $autor =
            Utilizador::factory()
                ->create();

        $dataFutura =
            CarbonImmutable::now(
                config(
                    'app.timezone',
                ),
            )->addWeek();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    $dataFutura->startOfMonth(),
                    $dataFutura->endOfMonth(),
                )
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->comData(
                    $dataFutura,
                )
                ->comEdicao(
                    $edicao,
                )
                ->comAutor(
                    $autor,
                )
                ->create();

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->postJson(
                route(
                    'comentarios.guardar',
                    [
                        'tipoComentavel' => TipoEntidadeInteracao::MetalThursday->value,

                        'identificadorComentavel' => $metalThursday->getKey(),
                    ],
                ),
                [
                    'conteudo' => 'Comentário prematuro.',
                ],
            )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'comentarios',
            [
                'tipo_comentavel' => $metalThursday->getMorphClass(),

                'comentavel_id' => $metalThursday->getKey(),

                'conteudo' => 'Comentário prematuro.',
            ],
        );

        Notification::assertNothingSent();
    }

    /**
     * Confirma que um comentário já persistido numa MetalThursday preparada não
     * pode receber novas respostas antes da publicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_pode_responder_a_comentario_de_metal_thursday_preparada(): void
    {
        Notification::fake();

        $utilizador =
            Utilizador::factory()
                ->create();

        $dataFutura =
            CarbonImmutable::now(
                config(
                    'app.timezone',
                ),
            )->addWeek();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    $dataFutura->startOfMonth(),
                    $dataFutura->endOfMonth(),
                )
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->comData(
                    $dataFutura,
                )
                ->comEdicao(
                    $edicao,
                )
                ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Comentário existente.',

                    'comentario_pai_id' => null,
                ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'comentarios.respostas.guardar',
                    $comentario,
                ),
                [
                    'conteudo' => 'Resposta prematura.',
                ],
            )
            ->assertNotFound();

        $this->assertDatabaseMissing(
            'comentarios',
            [
                'comentario_pai_id' => $comentario->getKey(),

                'conteudo' => 'Resposta prematura.',
            ],
        );

        Notification::assertNothingSent();
    }

    /**
     * Confirma que as respostas de uma conversa pertencente a uma MetalThursday
     * preparada não podem ser consultadas antes da publicação.
     *
     * @since 2.0.0
     */
    #[Test]
    public function nao_lista_respostas_de_comentario_de_metal_thursday_preparada(): void
    {
        $utilizador =
            Utilizador::factory()
                ->create();

        $dataFutura =
            CarbonImmutable::now(
                config(
                    'app.timezone',
                ),
            )->addWeek();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    $dataFutura->startOfMonth(),
                    $dataFutura->endOfMonth(),
                )
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->comData(
                    $dataFutura,
                )
                ->comEdicao(
                    $edicao,
                )
                ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $utilizador->getKey(),

                    'conteudo' => 'Comentário futuro.',

                    'comentario_pai_id' => null,
                ]);

        $metalThursday
            ->comentarios()
            ->create([
                'utilizador_id' => $utilizador->getKey(),

                'conteudo' => 'Resposta futura.',

                'comentario_pai_id' => $comentario->getKey(),
            ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->getJson(
                route(
                    'comentarios.respostas.indice',
                    $comentario,
                ),
            )
            ->assertNotFound();
    }

    /**
     * Confirma que o autor não pode editar um comentário pertencente a uma
     * secção de uma MetalThursday preparada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_nao_pode_atualizar_comentario_de_seccao_de_metal_thursday_preparada(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $dataFutura =
            CarbonImmutable::now(
                config(
                    'app.timezone',
                ),
            )->addWeek();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    $dataFutura->startOfMonth(),
                    $dataFutura->endOfMonth(),
                )
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->comData(
                    $dataFutura,
                )
                ->comEdicao(
                    $edicao,
                )
                ->comAutor(
                    $autor,
                )
                ->create();

        $seccao =
            SeccaoMetalThursday::factory()
                ->paraMetalThursday(
                    $metalThursday,
                )
                ->create();

        $comentario =
            $seccao
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Comentário original futuro.',

                    'comentario_pai_id' => null,
                ]);

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->patchJson(
                route(
                    'comentarios.atualizar',
                    $comentario,
                ),
                [
                    'conteudo' => 'Alteração prematura.',
                ],
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'comentarios',
            [
                'id' => $comentario->getKey(),

                'conteudo' => 'Comentário original futuro.',

                'editado_em' => null,
            ],
        );
    }

    /**
     * Confirma que o autor não pode eliminar um comentário pertencente a uma
     * MetalThursday preparada.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_nao_pode_eliminar_comentario_de_metal_thursday_preparada(): void
    {
        $autor =
            Utilizador::factory()
                ->create();

        $dataFutura =
            CarbonImmutable::now(
                config(
                    'app.timezone',
                ),
            )->addWeek();

        $edicao =
            Edicao::factory()
                ->comPeriodo(
                    $dataFutura->startOfMonth(),
                    $dataFutura->endOfMonth(),
                )
                ->create();

        $metalThursday =
            MetalThursday::factory()
                ->comData(
                    $dataFutura,
                )
                ->comEdicao(
                    $edicao,
                )
                ->comAutor(
                    $autor,
                )
                ->create();

        $comentario =
            $metalThursday
                ->comentarios()
                ->create([
                    'utilizador_id' => $autor->getKey(),

                    'conteudo' => 'Comentário futuro preservado.',

                    'comentario_pai_id' => null,
                ]);

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->deleteJson(
                route(
                    'comentarios.eliminar',
                    $comentario,
                ),
            )
            ->assertNotFound();

        $this->assertNotSoftDeleted(
            'comentarios',
            [
                'id' => $comentario->getKey(),
            ],
        );

        $this->assertDatabaseHas(
            'comentarios',
            [
                'id' => $comentario->getKey(),

                'conteudo' => 'Comentário futuro preservado.',

                'conteudo_eliminado_em' => null,
            ],
        );
    }
}
