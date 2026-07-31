<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a autorização dos pedidos de comentários.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class PedidosComentarioRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que um utilizador autenticado alcança a validação ao publicar
     * um comentário.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_autenticado_recebe_erros_de_validacao_ao_publicar(): void
    {
        $utilizador = Utilizador::factory()
            ->create();

        $metalThursday = MetalThursday::factory()
            ->create();

        $this
            ->actingAs(
                $utilizador,
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
                    'conteudo' => [],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'conteudo',
            ]);
    }

    /**
     * Confirma que um utilizador autenticado alcança a validação ao responder
     * a um comentário.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_autenticado_recebe_erros_de_validacao_ao_responder(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $comentario = $this->criarComentario(
            $autor,
        );

        $utilizador = Utilizador::factory()
            ->create();

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
                    'conteudo' => [],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'conteudo',
            ]);
    }

    /**
     * Confirma que um utilizador que não é o autor é rejeitado antes da
     * validação de uma atualização.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function utilizador_que_nao_e_autor_e_rejeitado_antes_da_validacao(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $comentario = $this->criarComentario(
            $autor,
        );

        $utilizador = Utilizador::factory()
            ->create();

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->patchJson(
                route(
                    'comentarios.atualizar',
                    $comentario,
                ),
                [
                    'conteudo' => [],
                ],
            )
            ->assertForbidden();

        self::assertSame(
            'Comentário original.',
            $comentario
                ->refresh()
                ->conteudo,
        );
    }

    /**
     * Confirma que o autor alcança a validação da atualização.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function autor_recebe_erros_de_validacao_na_atualizacao(): void
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
                    'conteudo' => [],
                ],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'conteudo',
            ]);
    }

    /**
     * Cria um comentário principal com conteúdo conhecido.
     *
     * @param  Utilizador  $autor  Autor do comentário.
     * @return Comentario Comentário criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
