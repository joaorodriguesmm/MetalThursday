<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa as operações HTTP de atualização e eliminação de comentários.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorComentarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que um utilizador que não é o autor é rejeitado antes de a
     * aplicação obter um bloqueio exclusivo sobre o comentário.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     *
     * @version 1.0.0
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
