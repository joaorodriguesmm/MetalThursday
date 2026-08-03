<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a alternância e a apresentação dos gostos dos comentários.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorGostoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a adição reutiliza a consulta dos nomes para determinar o
     * número de gostos, sem executar uma contagem adicional.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function adiciona_gosto_sem_consulta_de_contagem_redundante(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()
            ->create([
                'nome' => 'Autor',
            ]);

        $utilizador = Utilizador::factory()
            ->create([
                'nome' => 'Utilizador do gosto',
            ]);

        $comentario = $this->criarComentario(
            $autor,
        );

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
            ->postJson(
                route(
                    'gostos.alternar',
                    $comentario,
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'adicionado',
                true,
            )
            ->assertJsonPath(
                'numero_gostos',
                1,
            )
            ->assertJsonPath(
                'mensagem',
                'Gosto adicionado.',
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Utilizador do gosto',
            );

        $consultasDaOperacao =
            $consultas;

        self::assertSame(
            [],
            array_values(
                array_filter(
                    $consultasDaOperacao,
                    static fn (
                        string $consulta,
                    ): bool => str_contains(
                        $consulta,
                        'count(*)',
                    )
                        && str_contains(
                            $consulta,
                            'gostos',
                        ),
                ),
            ),
        );

        $this->assertDatabaseHas(
            'gostos',
            [
                'comentario_id' => $comentario->getKey(),

                'utilizador_id' => $utilizador->getKey(),
            ],
        );
    }

    /**
     * Confirma que a segunda alternância remove o gosto e devolve o indicador
     * vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function remove_gosto_e_devolve_contagem_zero(): void
    {
        Notification::fake();

        $autor = Utilizador::factory()
            ->create();

        $utilizador = Utilizador::factory()
            ->create();

        $comentario = $this->criarComentario(
            $autor,
        );

        $comentario
            ->gostos()
            ->create([
                'utilizador_id' => $utilizador->getKey(),
            ]);

        $this
            ->actingAs(
                $utilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'gostos.alternar',
                    $comentario,
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'adicionado',
                false,
            )
            ->assertJsonPath(
                'numero_gostos',
                0,
            )
            ->assertJsonPath(
                'mensagem',
                'Gosto removido.',
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Ainda não há gostos.',
            );

        $this->assertDatabaseMissing(
            'gostos',
            [
                'comentario_id' => $comentario->getKey(),

                'utilizador_id' => $utilizador->getKey(),
            ],
        );
    }

    /**
     * Confirma que a lista preserva contas diferentes com o mesmo nome.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function lista_utilizadores_sem_remover_nomes_repetidos(): void
    {
        $autor = Utilizador::factory()
            ->create();

        $primeiroUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Nome repetido',
            ]);

        $segundoUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Nome repetido',
            ]);

        $comentario = $this->criarComentario(
            $autor,
        );

        $comentario
            ->gostos()
            ->createMany([
                [
                    'utilizador_id' => $primeiroUtilizador->getKey(),
                ],
                [
                    'utilizador_id' => $segundoUtilizador->getKey(),
                ],
            ]);

        $this
            ->actingAs(
                $autor,
                'sessao',
            )
            ->getJson(
                route(
                    'comentarios.utilizadores-gosto',
                    $comentario,
                ),
            )
            ->assertOk()
            ->assertJsonPath(
                'nomes',
                [
                    'Nome repetido',
                    'Nome repetido',
                ],
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Nome repetido<br>Nome repetido',
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
