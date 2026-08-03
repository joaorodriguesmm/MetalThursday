<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Interacoes;

use App\Enumeracoes\Interacoes\TipoEntidadeInteracao;
use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a atualização e a apresentação das avaliações.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ControladorAvaliacaoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Confirma que a média, a contagem e o indicador são construídos sem uma
     * consulta agregada adicional.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    #[Test]
    public function guarda_avaliacao_sem_consulta_agregada_redundante(): void
    {
        Notification::fake();

        $primeiroUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Primeiro avaliador',
            ]);

        $segundoUtilizador = Utilizador::factory()
            ->create([
                'nome' => 'Segundo avaliador',
            ]);

        $metalThursday = MetalThursday::factory()
            ->create();

        $metalThursday
            ->avaliacoes()
            ->create([
                'utilizador_id' => $primeiroUtilizador->getKey(),

                'pontuacao' => 6.5,
            ]);

        $consultas = [];

        DB::listen(
            static function (
                QueryExecuted $consulta,
            ) use (&$consultas): void {
                $consultas[] =
                    mb_strtolower(
                        $consulta->sql,
                    );
            },
        );

        $this
            ->actingAs(
                $segundoUtilizador,
                'sessao',
            )
            ->postJson(
                route(
                    'avaliacoes.guardar',
                    [
                        'tipoAvaliavel' => TipoEntidadeInteracao::MetalThursday->value,

                        'identificadorAvaliavel' => $metalThursday->getKey(),
                    ],
                ),
                [
                    'pontuacao' => '8,5',
                ],
            )
            ->assertOk()
            ->assertJsonPath(
                'media_avaliacoes',
                7.5,
            )
            ->assertJsonPath(
                'numero_avaliacoes',
                2,
            )
            ->assertJsonPath(
                'pontuacao_utilizador',
                8.5,
            )
            ->assertJsonPath(
                'conteudo_indicador_html',
                'Segundo avaliador: 8,5<br>Primeiro avaliador: 6,5',
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
                        'avaliacoes',
                    )
                        && (
                            str_contains(
                                $consulta,
                                'count(*)',
                            )
                            || str_contains(
                                $consulta,
                                'avg(',
                            )
                        ),
                ),
            ),
        );

        $this->assertDatabaseHas(
            'avaliacoes',
            [
                'utilizador_id' => $segundoUtilizador->getKey(),

                'tipo_avaliavel' => $metalThursday->getMorphClass(),

                'avaliavel_id' => $metalThursday->getKey(),

                'pontuacao' => 8.5,
            ],
        );
    }
}
