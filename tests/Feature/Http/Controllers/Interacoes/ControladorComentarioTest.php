<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a publicação, resposta, atualização e eliminação de comentários.
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

        $this
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
            )
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
     * Confirma que uma resposta a outra resposta é associada ao comentário
     * principal.
     *
     * @since 2.0.0
     */
    #[Test]
    public function resposta_a_uma_resposta_fica_associada_ao_comentario_principal(): void
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

        $this
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
            )
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
                $comentarioPrincipal->getKey(),
            );

        $this->assertDatabaseHas(
            'comentarios',
            [
                'utilizador_id' => $novoAutorResposta->getKey(),

                'tipo_comentavel' => $metalThursday->getMorphClass(),

                'comentavel_id' => $metalThursday->getKey(),

                'conteudo' => 'Segunda resposta.',

                'comentario_pai_id' => $comentarioPrincipal->getKey(),
            ],
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
     * Confirma que o autor pode eliminar logicamente o comentário.
     *
     * @since 2.0.0
     */
    #[Test]
    public function autor_elimina_comentario(): void
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
            ->assertNoContent();

        $this->assertSoftDeleted(
            'comentarios',
            [
                'id' => $comentario->getKey(),
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

        $comentario = $metalThursday
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
}
